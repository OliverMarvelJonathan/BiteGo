<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    private const CODE_ATTEMPTS = 20;

    private const MAX_QUANTITY_PER_MENU = 10;

    /**
     * @param  array<int, array{menu_id: int, quantity: int}>  $lines
     */
    public function place(
        User $customer,
        array $lines,
        string $pickupSlot,
        ?string $notes = null,
        ?string $idempotencyKey = null,
    ): Order {
        $this->validateInput($lines, $pickupSlot, $notes);

        $quantities = [];

        foreach ($lines as $line) {
            $menuId = (int) $line['menu_id'];
            $quantities[$menuId] = ($quantities[$menuId] ?? 0) + (int) $line['quantity'];
        }

        ksort($quantities);

        foreach ($quantities as $quantity) {
            if ($quantity > self::MAX_QUANTITY_PER_MENU) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah pesanan per menu maksimal '.self::MAX_QUANTITY_PER_MENU.' porsi.',
                ]);
            }
        }

        return retry(self::CODE_ATTEMPTS, function () use ($customer, $quantities, $pickupSlot, $notes, $idempotencyKey) {
            try {
                return DB::transaction(function () use ($customer, $quantities, $pickupSlot, $notes, $idempotencyKey) {
                    if ($idempotencyKey !== null) {
                        $existing = Order::where('user_id', $customer->id)
                            ->where('idempotency_key', $idempotencyKey)
                            ->first();

                        if ($existing) {
                            return $existing;
                        }
                    }

                    // Lock in a stable order so concurrent orders touching the same menus cannot deadlock.
                    $menus = Menu::whereIn('id', array_keys($quantities))
                        ->with('stall')
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    $stallId = null;
                    $totalAmount = 0;
                    $totalQty = 0;

                    foreach ($quantities as $menuId => $quantity) {
                        $menu = $menus->get($menuId);

                        if (! $menu || ! $menu->is_available || ! $menu->stall?->is_active) {
                            throw ValidationException::withMessages([
                                'menu' => 'Menu ini sudah tidak tersedia.',
                            ]);
                        }

                        if ($stallId !== null && $menu->stall_id !== $stallId) {
                            throw ValidationException::withMessages([
                                'menu' => 'Semua item dalam satu pesanan harus berasal dari stand yang sama.',
                            ]);
                        }

                        if ($quantity > $menu->stock) {
                            throw ValidationException::withMessages([
                                'quantity' => "Stok {$menu->name} tidak mencukupi. Sisa stok: {$menu->stock}.",
                            ]);
                        }

                        $stallId = $menu->stall_id;
                        $totalAmount += $menu->price * $quantity;
                        $totalQty += $quantity;
                    }

                    $pickupDate = now()->toDateString();

                    $order = Order::create([
                        'order_number' => 'BG-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),
                        'idempotency_key' => $idempotencyKey,
                        'user_id' => $customer->id,
                        'stall_id' => $stallId,
                        'pickup_slot' => $pickupSlot,
                        'pickup_code' => $this->generatePickupCode($stallId, $pickupDate),
                        'pickup_date' => $pickupDate,
                        'status' => 'menunggu',
                        'stock_deducted' => true,
                        'total_amount' => $totalAmount,
                        'total_qty' => $totalQty,
                        'notes' => $notes,
                    ]);

                    foreach ($quantities as $menuId => $quantity) {
                        $menu = $menus->get($menuId);

                        $order->items()->create([
                            'menu_id' => $menu->id,
                            'menu_name' => $menu->name,
                            'menu_image' => $menu->image,
                            'price' => $menu->price,
                            'quantity' => $quantity,
                            'subtotal' => $menu->price * $quantity,
                        ]);

                        $this->deductStock($menu->id, $quantity);
                    }

                    $order->statusLogs()->create([
                        'status' => 'menunggu',
                    ]);

                    return $order;
                }, 3);
            } catch (UniqueConstraintViolationException $e) {
                // A concurrent request may have committed the same idempotency key first.
                if ($idempotencyKey !== null) {
                    $existing = Order::where('user_id', $customer->id)
                        ->where('idempotency_key', $idempotencyKey)
                        ->first();

                    if ($existing) {
                        return $existing;
                    }
                }

                throw $e;
            }
        }, 50, fn ($e) => $e instanceof UniqueConstraintViolationException);
    }

    /**
     * @param  array<int, array{menu_id: int, quantity: int}>  $lines
     */
    private function validateInput(array $lines, string $pickupSlot, ?string $notes): void
    {
        if ($lines === []) {
            throw ValidationException::withMessages([
                'menu' => 'Pesanan harus berisi minimal satu menu.',
            ]);
        }

        if (! in_array($pickupSlot, ['istirahat_1', 'istirahat_2'], true)) {
            throw ValidationException::withMessages([
                'pickup_slot' => 'Jadwal pengambilan tidak valid.',
            ]);
        }

        if ($notes !== null && mb_strlen($notes) > 255) {
            throw ValidationException::withMessages([
                'notes' => 'Catatan maksimal 255 karakter.',
            ]);
        }

        foreach ($lines as $line) {
            $valid = is_array($line)
                && isset($line['menu_id'], $line['quantity'])
                && ctype_digit((string) $line['menu_id'])
                && ctype_digit((string) $line['quantity'])
                && (int) $line['menu_id'] >= 1
                && (int) $line['quantity'] >= 1;

            if (! $valid) {
                throw ValidationException::withMessages([
                    'menu' => 'Item pesanan tidak valid.',
                ]);
            }
        }
    }

    /**
     * Move an order to the next status, restoring stock exactly once on cancellation.
     */
    public function transition(Order $order, string $status): Order
    {
        return DB::transaction(function () use ($order, $status) {
            // Re-read under a row lock: a concurrent cancel/advance must not both act on the same state.
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($status === 'dibatalkan') {
                if (! $locked->canBeCancelled()) {
                    throw ValidationException::withMessages([
                        'status' => 'Pesanan sudah diproses dan tidak dapat dibatalkan.',
                    ]);
                }
            } elseif (! $locked->canTransitionTo($status)) {
                $target = Order::STATUS_LABELS[$status] ?? $status;

                throw ValidationException::withMessages([
                    'status' => "Status tidak dapat diubah dari \"{$locked->status_label}\" ke \"{$target}\".",
                ]);
            }

            $locked->update(['status' => $status]);

            $locked->statusLogs()->create([
                'status' => $status,
            ]);

            if ($status === 'dibatalkan' && $locked->stock_deducted) {
                $items = $locked->items()->orderBy('menu_id')->get();

                foreach ($items as $item) {
                    if ($item->menu_id !== null) {
                        Menu::whereKey($item->menu_id)->increment('stock', $item->quantity);
                    }
                }

                $locked->update(['stock_deducted' => false]);
            }

            return $locked;
        }, 3);
    }

    private function deductStock(int $menuId, int $quantity): void
    {
        $affected = Menu::whereKey($menuId)->where('stock', '>=', $quantity)->decrement('stock', $quantity);

        if ($affected !== 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Stok tidak mencukupi.',
            ]);
        }
    }

    private function generatePickupCode(int $stallId, string $pickupDate): string
    {
        for ($attempt = 0; $attempt < self::CODE_ATTEMPTS; $attempt++) {
            $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $taken = Order::where('stall_id', $stallId)
                ->where('pickup_date', $pickupDate)
                ->where('pickup_code', $code)
                ->exists();

            if (! $taken) {
                return $code;
            }
        }

        throw ValidationException::withMessages([
            'pickup_code' => 'Kode pengambilan untuk hari ini sedang penuh. Silakan coba lagi.',
        ]);
    }
}

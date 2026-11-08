<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Stall;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const SESSION_KEY = 'bitego_cart';

    public const MAX_QUANTITY_PER_ITEM = 10;

    /**
     * Get the full cart data with hydrated database models and fresh prices.
     *
     * @return array{
     *     stall: ?Stall,
     *     items: Collection<int, array{
     *         menu: Menu,
     *         quantity: int,
     *         unit_price: int,
     *         subtotal: int,
     *         is_available: bool,
     *         is_stock_sufficient: bool,
     *         max_available: int
     *     }>,
     *     subtotal: int,
     *     total: int,
     *     count: int,
     *     has_errors: bool
     * }
     */
    public function get(): array
    {
        $raw = session()->get(self::SESSION_KEY, [
            'stall_id' => null,
            'items' => [],
        ]);

        $rawItems = (array) ($raw['items'] ?? []);

        if (empty($rawItems)) {
            return [
                'stall' => null,
                'items' => collect(),
                'subtotal' => 0,
                'total' => 0,
                'count' => 0,
                'has_errors' => false,
            ];
        }

        $menuIds = array_map('intval', array_keys($rawItems));
        $menus = Menu::whereIn('id', $menuIds)
            ->with(['stall', 'category'])
            ->get()
            ->keyBy('id');

        $items = collect();
        $subtotal = 0;
        $count = 0;
        $hasErrors = false;
        $activeStall = null;

        foreach ($rawItems as $menuId => $quantity) {
            $menuId = (int) $menuId;
            $quantity = (int) $quantity;
            $menu = $menus->get($menuId);

            if (! $menu) {
                // Menu was deleted from DB; remove it from cart session
                unset($rawItems[$menuId]);
                $hasErrors = true;

                continue;
            }

            if ($activeStall === null && $menu->stall) {
                $activeStall = $menu->stall;
            }

            $isAvailable = $menu->is_available && ($menu->stall?->is_active ?? false);
            $maxAvailable = min(self::MAX_QUANTITY_PER_ITEM, max(0, $menu->stock));
            $isStockSufficient = $isAvailable && $quantity <= $menu->stock;

            if (! $isAvailable || ! $isStockSufficient) {
                $hasErrors = true;
            }

            // Price ALWAYS comes from database
            $unitPrice = (int) $menu->price;
            $lineSubtotal = $unitPrice * $quantity;

            $items->push([
                'menu' => $menu,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $lineSubtotal,
                'is_available' => $isAvailable,
                'is_stock_sufficient' => $isStockSufficient,
                'max_available' => $maxAvailable,
            ]);

            $subtotal += $lineSubtotal;
            $count += $quantity;
        }

        if (empty($rawItems)) {
            session()->put(self::SESSION_KEY, ['stall_id' => null, 'items' => []]);
            $activeStall = null;
        } else {
            session()->put(self::SESSION_KEY, [
                'stall_id' => $activeStall?->id,
                'items' => $rawItems,
            ]);
        }

        return [
            'stall' => $activeStall,
            'items' => $items,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'count' => $count,
            'has_errors' => $hasErrors,
        ];
    }

    /**
     * Add a menu item to the cart.
     *
     * @return array{
     *     status: 'success'|'conflict',
     *     current_stall?: ?Stall,
     *     new_stall?: Stall,
     *     menu?: Menu,
     *     quantity?: int,
     *     cart?: array
     * }
     */
    public function add(int $menuId, int $quantity = 1): array
    {
        $quantity = max(1, min(self::MAX_QUANTITY_PER_ITEM, $quantity));

        $menu = Menu::with('stall')->findOrFail($menuId);

        if (! $menu->stall || ! $menu->stall->is_active) {
            throw ValidationException::withMessages([
                'menu' => 'Stand kantin untuk menu ini sedang tidak aktif.',
            ]);
        }

        if (! $menu->is_available || $menu->stock < 1) {
            throw ValidationException::withMessages([
                'menu' => 'Menu ini sedang tidak tersedia.',
            ]);
        }

        $raw = session()->get(self::SESSION_KEY, [
            'stall_id' => null,
            'items' => [],
        ]);

        $currentStallId = $raw['stall_id'] ?? null;
        $rawItems = (array) ($raw['items'] ?? []);

        // Cross-stall conflict check
        if (! empty($rawItems) && $currentStallId !== null && (int) $currentStallId !== (int) $menu->stall_id) {
            $currentStall = Stall::find($currentStallId);

            return [
                'status' => 'conflict',
                'current_stall' => $currentStall,
                'new_stall' => $menu->stall,
                'menu' => $menu,
                'quantity' => $quantity,
            ];
        }

        $currentQty = (int) ($rawItems[$menu->id] ?? 0);
        $newQty = min(self::MAX_QUANTITY_PER_ITEM, min($menu->stock, $currentQty + $quantity));

        if ($newQty < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Stok menu ini sudah habis.',
            ]);
        }

        $rawItems[$menu->id] = $newQty;

        session()->put(self::SESSION_KEY, [
            'stall_id' => $menu->stall_id,
            'items' => $rawItems,
        ]);

        return [
            'status' => 'success',
            'cart' => $this->get(),
        ];
    }

    /**
     * Update quantity for a menu item in cart.
     */
    public function update(int $menuId, int $quantity): void
    {
        $raw = session()->get(self::SESSION_KEY, [
            'stall_id' => null,
            'items' => [],
        ]);

        $rawItems = (array) ($raw['items'] ?? []);

        if (! isset($rawItems[$menuId])) {
            return;
        }

        if ($quantity <= 0) {
            $this->remove($menuId);

            return;
        }

        $menu = Menu::find($menuId);

        if (! $menu || ! $menu->is_available || $menu->stock < 1) {
            $this->remove($menuId);

            return;
        }

        $validQty = max(1, min(self::MAX_QUANTITY_PER_ITEM, min($menu->stock, $quantity)));
        $rawItems[$menuId] = $validQty;

        session()->put(self::SESSION_KEY, [
            'stall_id' => $raw['stall_id'],
            'items' => $rawItems,
        ]);
    }

    /**
     * Remove an item from the cart.
     */
    public function remove(int $menuId): void
    {
        $raw = session()->get(self::SESSION_KEY, [
            'stall_id' => null,
            'items' => [],
        ]);

        $rawItems = (array) ($raw['items'] ?? []);

        if (isset($rawItems[$menuId])) {
            unset($rawItems[$menuId]);
        }

        if (empty($rawItems)) {
            session()->put(self::SESSION_KEY, [
                'stall_id' => null,
                'items' => [],
            ]);
        } else {
            session()->put(self::SESSION_KEY, [
                'stall_id' => $raw['stall_id'],
                'items' => $rawItems,
            ]);
        }
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(): void
    {
        session()->put(self::SESSION_KEY, [
            'stall_id' => null,
            'items' => [],
        ]);
    }

    /**
     * Replace cart contents with a new item from another stall (conflict resolution).
     */
    public function replaceWith(int $menuId, int $quantity = 1): array
    {
        $this->clear();

        return $this->add($menuId, $quantity);
    }

    /**
     * Total count of all items in cart.
     */
    public function count(): int
    {
        $raw = session()->get(self::SESSION_KEY, [
            'items' => [],
        ]);

        $total = 0;
        foreach ((array) ($raw['items'] ?? []) as $qty) {
            $total += (int) $qty;
        }

        return $total;
    }

    /**
     * Validate cart for checkout and format lines for OrderService::place().
     *
     * @return array<int, array{menu_id: int, quantity: int}>
     *
     * @throws ValidationException
     */
    public function validateForCheckout(): array
    {
        $cart = $this->get();

        if ($cart['items']->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Keranjang pesanan masih kosong.',
            ]);
        }

        if (! $cart['stall'] || ! $cart['stall']->is_active) {
            throw ValidationException::withMessages([
                'cart' => 'Stand kantin sedang tidak aktif.',
            ]);
        }

        $lines = [];

        foreach ($cart['items'] as $item) {
            /** @var Menu $menu */
            $menu = $item['menu'];
            $qty = $item['quantity'];

            if (! $item['is_available']) {
                throw ValidationException::withMessages([
                    'cart' => "Menu \"{$menu->name}\" sudah tidak tersedia.",
                ]);
            }

            if ($qty > $menu->stock) {
                throw ValidationException::withMessages([
                    'cart' => "Stok \"{$menu->name}\" tidak mencukupi (sisa: {$menu->stock}).",
                ]);
            }

            $lines[] = [
                'menu_id' => $menu->id,
                'quantity' => $qty,
            ];
        }

        return $lines;
    }
}

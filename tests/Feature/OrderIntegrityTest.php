<?php

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Stall;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->siswa = User::where('role', 'siswa')->first();
    $this->vendor = User::where('role', 'vendor')->first();
    $this->stall = $this->vendor->stall;
    $this->menu = $this->stall->menus()->where('is_available', true)->first();
});

function placeOrder(User $customer, Menu $menu, int $quantity = 1): Order
{
    return app(OrderService::class)->place(
        $customer,
        [['menu_id' => $menu->id, 'quantity' => $quantity]],
        'istirahat_1',
    );
}

// ─── Stock consistency ────────────────────────────────────────────

it('mengurangi stok saat pesanan dibuat', function () {
    $before = $this->menu->stock;

    $order = placeOrder($this->siswa, $this->menu, 3);

    expect($this->menu->fresh()->stock)->toBe($before - 3)
        ->and($order->fresh()->stock_deducted)->toBeTrue();
});

it('memakai stok terbaru meski model menu sudah usang', function () {
    $this->menu->update(['stock' => 1]);
    $stale = Menu::findOrFail($this->menu->id);
    $ordersBefore = Order::count();

    placeOrder($this->siswa, $stale, 1);

    $other = User::where('role', 'siswa')->where('id', '!=', $this->siswa->id)->first();

    try {
        placeOrder($other, $stale, 1);
        $this->fail('Pesanan kedua seharusnya ditolak.');
    } catch (ValidationException $e) {
        //
    }

    expect($this->menu->fresh()->stock)->toBe(0)
        ->and(Order::count())->toBe($ordersBefore + 1);
});

it('menolak pesanan melebihi stok tanpa membuat pesanan', function () {
    $this->menu->update(['stock' => 2]);
    $ordersBefore = Order::count();

    $this->actingAs($this->siswa)
        ->post('/order/'.$this->menu->slug, [
            'quantity' => 5,
            'pickup_slot' => 'istirahat_1',
        ])
        ->assertSessionHasErrors('quantity');

    expect(Order::count())->toBe($ordersBefore)
        ->and($this->menu->fresh()->stock)->toBe(2);
});

it('tidak mengizinkan stok habis dipesan dua kali', function () {
    $this->menu->update(['stock' => 1]);

    placeOrder($this->siswa, $this->menu, 1);

    expect($this->menu->fresh()->stock)->toBe(0);

    $this->expectException(ValidationException::class);
    placeOrder($this->siswa, $this->menu, 1);
});

it('tidak pernah membuat stok menjadi negatif pada tingkat database', function () {
    $this->menu->update(['stock' => 2]);

    $affected = Menu::whereKey($this->menu->id)->where('stock', '>=', 5)->decrement('stock', 5);

    expect($affected)->toBe(0)
        ->and($this->menu->fresh()->stock)->toBe(2);
});

it('membatalkan seluruh transaksi bila penulisan item gagal', function () {
    $before = $this->menu->stock;
    $ordersBefore = Order::count();

    OrderItem::creating(function () {
        throw new RuntimeException('Gagal menyimpan item.');
    });

    try {
        placeOrder($this->siswa, $this->menu, 2);
    } catch (RuntimeException $e) {
        // expected
    }

    expect(Order::count())->toBe($ordersBefore)
        ->and($this->menu->fresh()->stock)->toBe($before);
});

it('menolak menu yang tidak tersedia', function () {
    $this->menu->update(['is_available' => false]);
    $ordersBefore = Order::count();

    $this->actingAs($this->siswa)
        ->post('/order/'.$this->menu->slug, [
            'quantity' => 1,
            'pickup_slot' => 'istirahat_1',
        ])
        ->assertSessionHasErrors('menu');

    expect(Order::count())->toBe($ordersBefore);
});

it('menolak item dari stand yang berbeda dalam satu pesanan', function () {
    $otherMenu = Menu::where('stall_id', '!=', $this->stall->id)->first();
    $ordersBefore = Order::count();

    $this->expectException(ValidationException::class);

    app(OrderService::class)->place($this->siswa, [
        ['menu_id' => $this->menu->id, 'quantity' => 1],
        ['menu_id' => $otherMenu->id, 'quantity' => 1],
    ], 'istirahat_1');

    expect(Order::count())->toBe($ordersBefore);
});

// ─── Status transitions ───────────────────────────────────────────

it('mengizinkan rantai status yang sah sampai selesai', function () {
    $order = placeOrder($this->siswa, $this->menu);

    foreach (['dimasak', 'siap_ambil', 'selesai'] as $status) {
        $this->actingAs($this->vendor)
            ->post("/vendor/orders/{$order->id}/status", ['status' => $status])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect($order->fresh()->status)->toBe($status);
    }
});

it('menolak lompatan status ke depan', function () {
    $order = placeOrder($this->siswa, $this->menu);

    $this->actingAs($this->vendor)
        ->post("/vendor/orders/{$order->id}/status", ['status' => 'siap_ambil'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe('menunggu');
});

it('menolak status mundur', function () {
    $order = placeOrder($this->siswa, $this->menu);
    app(OrderService::class)->transition($order, 'dimasak');

    $this->actingAs($this->vendor)
        ->post("/vendor/orders/{$order->id}/status", ['status' => 'menunggu'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe('dimasak');
});

it('tidak dapat membuka kembali pesanan yang sudah selesai', function () {
    $order = placeOrder($this->siswa, $this->menu);
    $service = app(OrderService::class);
    $service->transition($order, 'dimasak');
    $service->transition($order, 'siap_ambil');
    $service->transition($order, 'selesai');

    $this->actingAs($this->vendor)
        ->post("/vendor/orders/{$order->id}/status", ['status' => 'dimasak'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe('selesai');
});

it('menolak nilai status yang tidak dikenal', function () {
    $order = placeOrder($this->siswa, $this->menu);

    $this->actingAs($this->vendor)
        ->post("/vendor/orders/{$order->id}/status", ['status' => 'tidak_ada'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe('menunggu');
});

// ─── Cancellation ─────────────────────────────────────────────────

it('pembatalan oleh siswa mengembalikan stok tepat satu kali', function () {
    $before = $this->menu->stock;
    $order = placeOrder($this->siswa, $this->menu, 3);

    $this->actingAs($this->siswa)
        ->post("/orders/{$order->id}/cancel")
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($order->fresh()->status)->toBe('dibatalkan')
        ->and($this->menu->fresh()->stock)->toBe($before);
});

it('pembatalan oleh penjual mengembalikan stok', function () {
    $before = $this->menu->stock;
    $order = placeOrder($this->siswa, $this->menu, 2);

    expect($this->menu->fresh()->stock)->toBe($before - 2);

    $this->actingAs($this->vendor)
        ->post("/vendor/orders/{$order->id}/status", ['status' => 'dibatalkan'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($order->fresh()->status)->toBe('dibatalkan')
        ->and($this->menu->fresh()->stock)->toBe($before);
});

it('pembatalan berulang tidak mengembalikan stok dua kali', function () {
    $before = $this->menu->stock;
    $order = placeOrder($this->siswa, $this->menu, 2);

    $this->actingAs($this->siswa)->post("/orders/{$order->id}/cancel");

    $this->actingAs($this->siswa)
        ->post("/orders/{$order->id}/cancel")
        ->assertSessionHasErrors('status');

    $this->actingAs($this->vendor)
        ->post("/vendor/orders/{$order->id}/status", ['status' => 'dibatalkan'])
        ->assertSessionHasErrors('status');

    expect($this->menu->fresh()->stock)->toBe($before);
});

it('tidak dapat membatalkan pesanan yang sudah dimasak', function () {
    $order = placeOrder($this->siswa, $this->menu, 1);
    app(OrderService::class)->transition($order, 'dimasak');
    $afterCooking = $this->menu->fresh()->stock;

    $this->actingAs($this->siswa)
        ->post("/orders/{$order->id}/cancel")
        ->assertSessionHasErrors('status');

    $this->actingAs($this->vendor)
        ->post("/vendor/orders/{$order->id}/status", ['status' => 'dibatalkan'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe('dimasak')
        ->and($this->menu->fresh()->stock)->toBe($afterCooking);
});

it('tidak mengembalikan stok untuk pesanan yang stoknya tidak pernah dikurangi', function () {
    $before = $this->menu->stock;

    $order = Order::create([
        'order_number' => 'BG-LEGACY-TEST',
        'user_id' => $this->siswa->id,
        'stall_id' => $this->stall->id,
        'pickup_slot' => 'istirahat_1',
        'pickup_code' => '0000',
        'pickup_date' => '2000-01-01',
        'status' => 'menunggu',
        'stock_deducted' => false,
        'total_amount' => $this->menu->price,
        'total_qty' => 1,
    ]);

    $order->items()->create([
        'menu_id' => $this->menu->id,
        'menu_name' => $this->menu->name,
        'menu_image' => $this->menu->image,
        'price' => $this->menu->price,
        'quantity' => 1,
        'subtotal' => $this->menu->price,
    ]);

    $this->actingAs($this->siswa)
        ->post("/orders/{$order->id}/cancel")
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($order->fresh()->status)->toBe('dibatalkan')
        ->and($order->fresh()->stock_deducted)->toBeFalse()
        ->and($this->menu->fresh()->stock)->toBe($before);
});

it('mengembalikan stok semua item pada pesanan multi item', function () {
    $second = $this->stall->menus()->where('id', '!=', $this->menu->id)->first();
    $firstBefore = $this->menu->stock;
    $secondBefore = $second->stock;

    $order = app(OrderService::class)->place($this->siswa, [
        ['menu_id' => $this->menu->id, 'quantity' => 2],
        ['menu_id' => $second->id, 'quantity' => 3],
    ], 'istirahat_1');

    expect($order->items()->count())->toBe(2)
        ->and($this->menu->fresh()->stock)->toBe($firstBefore - 2)
        ->and($second->fresh()->stock)->toBe($secondBefore - 3);

    app(OrderService::class)->transition($order, 'dibatalkan');

    expect($this->menu->fresh()->stock)->toBe($firstBefore)
        ->and($second->fresh()->stock)->toBe($secondBefore);
});

// ─── Pickup code uniqueness ───────────────────────────────────────

it('memberikan kode pengambilan 4 digit dan tanggal ambil', function () {
    $order = placeOrder($this->siswa, $this->menu);

    expect($order->pickup_code)->toMatch('/^\d{4}$/')
        ->and($order->pickup_date->toDateString())->toBe(now()->toDateString());
});

it('menolak kode duplikat untuk stand dan tanggal yang sama di database', function () {
    $order = placeOrder($this->siswa, $this->menu);

    $this->expectException(UniqueConstraintViolationException::class);

    DB::table('orders')->insert([
        'order_number' => 'BG-DUPLICATE-TEST',
        'user_id' => $this->siswa->id,
        'stall_id' => $order->stall_id,
        'pickup_slot' => 'istirahat_1',
        'pickup_code' => $order->pickup_code,
        'pickup_date' => $order->pickup_date->toDateString(),
        'status' => 'menunggu',
        'total_amount' => 1000,
        'total_qty' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('mengizinkan kode sama pada stand yang berbeda', function () {
    $order = placeOrder($this->siswa, $this->menu);
    $otherStall = Stall::where('id', '!=', $this->stall->id)->first();

    DB::table('orders')->insert([
        'order_number' => 'BG-OTHER-STALL-TEST',
        'user_id' => $this->siswa->id,
        'stall_id' => $otherStall->id,
        'pickup_slot' => 'istirahat_1',
        'pickup_code' => $order->pickup_code,
        'pickup_date' => $order->pickup_date->toDateString(),
        'status' => 'menunggu',
        'total_amount' => 1000,
        'total_qty' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('orders')->where('pickup_code', $order->pickup_code)->count())->toBe(2);
});

// ─── Order authorization ──────────────────────────────────────────

it('siswa tidak dapat membatalkan pesanan siswa lain', function () {
    $other = User::where('role', 'siswa')->where('id', '!=', $this->siswa->id)->first();
    $order = placeOrder($other, $this->menu);
    $before = $this->menu->fresh()->stock;

    $this->actingAs($this->siswa)
        ->post("/orders/{$order->id}/cancel")
        ->assertForbidden();

    expect($order->fresh()->status)->toBe('menunggu')
        ->and($this->menu->fresh()->stock)->toBe($before);
});

it('siswa tidak dapat melihat pesanan siswa lain', function () {
    $other = User::where('role', 'siswa')->where('id', '!=', $this->siswa->id)->first();
    $order = placeOrder($other, $this->menu);

    $this->actingAs($this->siswa)->get("/orders/{$order->id}")->assertForbidden();
    $this->actingAs($this->siswa)->get("/orders/{$order->id}/success")->assertForbidden();
});

it('penjual tidak dapat mengubah status pesanan stand lain', function () {
    $otherVendor = User::where('role', 'vendor')->where('id', '!=', $this->vendor->id)->first();
    $order = placeOrder($this->siswa, $this->menu);

    $this->actingAs($otherVendor)
        ->post("/vendor/orders/{$order->id}/status", ['status' => 'dimasak'])
        ->assertForbidden();

    expect($order->fresh()->status)->toBe('menunggu');
});

it('penjual tidak dapat memesan sebagai siswa', function () {
    $this->actingAs($this->vendor)
        ->post('/order/'.$this->menu->slug, ['quantity' => 1, 'pickup_slot' => 'istirahat_1'])
        ->assertForbidden();
});

it('dashboard penjual menampilkan catatan dan nomor pesanan', function () {
    $order = app(OrderService::class)->place(
        $this->siswa,
        [['menu_id' => $this->menu->id, 'quantity' => 1]],
        'istirahat_1',
        'Sambal dipisah dan tidak pedas',
    );

    $this->actingAs($this->vendor)
        ->get('/vendor?status=menunggu')
        ->assertOk()
        ->assertSee('Sambal dipisah dan tidak pedas')
        ->assertSee($order->order_number)
        ->assertSee($this->siswa->name);
});

// ─── Defensive input validation ───────────────────────────────────

it('menolak input pesanan yang tidak valid tanpa membuat data', function () {
    $service = app(OrderService::class);
    $ordersBefore = Order::count();
    $stockBefore = $this->menu->fresh()->stock;

    expect(fn () => $service->place($this->siswa, [], 'istirahat_1'))
        ->toThrow(ValidationException::class);

    expect(fn () => $service->place($this->siswa, [['menu_id' => $this->menu->id, 'quantity' => 0]], 'istirahat_1'))
        ->toThrow(ValidationException::class);

    expect(fn () => $service->place($this->siswa, [['menu_id' => $this->menu->id]], 'istirahat_1'))
        ->toThrow(ValidationException::class);

    expect(fn () => $service->place($this->siswa, [['menu_id' => $this->menu->id, 'quantity' => 11]], 'istirahat_1'))
        ->toThrow(ValidationException::class);

    expect(fn () => $service->place($this->siswa, [['menu_id' => $this->menu->id, 'quantity' => 1]], 'istirahat_9'))
        ->toThrow(ValidationException::class);

    expect(fn () => $service->place($this->siswa, [['menu_id' => $this->menu->id, 'quantity' => 1]], 'istirahat_1', str_repeat('a', 256)))
        ->toThrow(ValidationException::class);

    expect(Order::count())->toBe($ordersBefore)
        ->and($this->menu->fresh()->stock)->toBe($stockBefore);
});

// ─── Inactive stalls ──────────────────────────────────────────────

it('menyembunyikan dan menolak pesanan dari stand yang tidak aktif', function () {
    $this->stall->update(['is_active' => false]);
    $menu = $this->menu->fresh();
    $ordersBefore = Order::count();

    $this->get('/menu')->assertOk()->assertDontSee($menu->name);
    $this->get('/')->assertOk()->assertDontSee($menu->name);
    $this->get('/stall/'.$this->stall->slug)->assertNotFound();

    $this->actingAs($this->siswa)
        ->get('/order/'.$menu->slug.'/create')
        ->assertNotFound();

    $this->actingAs($this->siswa)
        ->post('/order/'.$menu->slug, ['quantity' => 1, 'pickup_slot' => 'istirahat_1'])
        ->assertNotFound();

    expect(Order::count())->toBe($ordersBefore);
});

// ─── Double submit protection ─────────────────────────────────────

it('mencegah pesanan ganda dengan kunci idempotensi yang sama', function () {
    $this->menu->update(['stock' => 10]);
    $key = (string) Str::uuid();

    $payload = [
        'quantity' => 2,
        'pickup_slot' => 'istirahat_1',
        'idempotency_key' => $key,
    ];

    $this->actingAs($this->siswa)->post('/order/'.$this->menu->slug, $payload)->assertRedirect();
    $this->actingAs($this->siswa)->post('/order/'.$this->menu->slug, $payload)->assertRedirect();

    expect(Order::where('user_id', $this->siswa->id)->where('idempotency_key', $key)->count())->toBe(1)
        ->and($this->menu->fresh()->stock)->toBe(8);
});

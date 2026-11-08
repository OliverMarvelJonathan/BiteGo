<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Order;
use App\Models\Stall;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $siswa;

    private User $otherSiswa;

    private Stall $stall;

    private Menu $menu1;

    private Menu $menu2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $siswas = User::where('role', 'siswa')->get();
        $this->siswa = $siswas[0];
        $this->otherSiswa = $siswas[1];

        $this->stall = Stall::where('is_active', true)->first();
        $menus = $this->stall->menus()->where('is_available', true)->where('stock', '>=', 10)->get();
        $this->menu1 = $menus[0];
        $this->menu2 = $menus[1];
    }

    public function test_guest_is_redirected_from_checkout(): void
    {
        $this->get('/checkout/details')->assertRedirect('/login');
        $this->get('/checkout/confirm')->assertRedirect('/login');
    }

    public function test_empty_cart_is_redirected_to_cart_page(): void
    {
        $this->actingAs($this->siswa)
            ->get('/checkout/details')
            ->assertRedirect(route('cart.index'));
    }

    public function test_successful_three_step_checkout_creates_order_and_clears_cart(): void
    {
        // Step 1: Add items to cart
        $this->actingAs($this->siswa)
            ->post('/cart/add', ['menu_id' => $this->menu1->id, 'quantity' => 2]);
        $this->actingAs($this->siswa)
            ->post('/cart/add', ['menu_id' => $this->menu2->id, 'quantity' => 1]);

        $stock1Before = $this->menu1->fresh()->stock;
        $stock2Before = $this->menu2->fresh()->stock;

        // Step 2: Fill order details
        $this->actingAs($this->siswa)
            ->get('/checkout/details')
            ->assertOk()
            ->assertSee($this->menu1->name);

        $this->actingAs($this->siswa)
            ->post('/checkout/details', [
                'pickup_slot' => 'istirahat_1',
                'notes' => 'Sambal dipisah',
            ])
            ->assertRedirect(route('checkout.confirm'));

        // Step 3: View confirmation and submit order
        $this->actingAs($this->siswa)
            ->get('/checkout/confirm')
            ->assertOk()
            ->assertSee('Istirahat I')
            ->assertSee('Sambal dipisah');

        $idempotencyKey = (string) Str::uuid();

        $response = $this->actingAs($this->siswa)
            ->post('/checkout/confirm', [
                'idempotency_key' => $idempotencyKey,
            ]);

        $order = Order::where('user_id', $this->siswa->id)->latest('id')->first();
        expect($order)->not->toBeNull();
        expect($order->pickup_code)->toMatch('/^\d{4}$/');
        expect($order->items()->count())->toBe(2);
        expect($order->total_qty)->toBe(3);
        expect($order->notes)->toBe('Sambal dipisah');

        $response->assertRedirect(route('orders.success', $order));

        // Verify stock deducted
        expect($this->menu1->fresh()->stock)->toBe($stock1Before - 2);
        expect($this->menu2->fresh()->stock)->toBe($stock2Before - 1);

        // Verify cart is cleared after successful checkout
        $cart = app(CartService::class)->get();
        expect($cart['items']->count())->toBe(0);
    }

    public function test_checkout_uses_database_price_preventing_price_manipulation(): void
    {
        $this->actingAs($this->siswa)
            ->post('/cart/add', ['menu_id' => $this->menu1->id, 'quantity' => 1]);

        $this->actingAs($this->siswa)
            ->post('/checkout/details', ['pickup_slot' => 'istirahat_1']);

        // Tampered request attempting to pass cheap price
        $this->actingAs($this->siswa)
            ->post('/checkout/confirm', [
                'total' => 100, // client attempts to forge total
            ]);

        $order = Order::where('user_id', $this->siswa->id)->latest('id')->first();
        expect($order->total_amount)->toBe($this->menu1->price);
    }

    public function test_failed_checkout_due_to_insufficient_stock_preserves_cart(): void
    {
        $this->actingAs($this->siswa)
            ->post('/cart/add', ['menu_id' => $this->menu1->id, 'quantity' => 3]);

        $this->actingAs($this->siswa)
            ->post('/checkout/details', ['pickup_slot' => 'istirahat_1']);

        // Another customer purchases stock concurrently
        $this->menu1->update(['stock' => 1]);

        $response = $this->actingAs($this->siswa)
            ->post('/checkout/confirm', []);

        $response->assertRedirect(route('cart.index'));

        // Cart is preserved!
        $cart = app(CartService::class)->get();
        expect($cart['items']->count())->toBe(1);
        expect($cart['items']->first()['quantity'])->toBe(3);
    }

    public function test_duplicate_submission_protection_via_idempotency_key(): void
    {
        $this->actingAs($this->siswa)
            ->post('/cart/add', ['menu_id' => $this->menu1->id, 'quantity' => 2]);

        $this->actingAs($this->siswa)
            ->post('/checkout/details', ['pickup_slot' => 'istirahat_1']);

        $key = (string) Str::uuid();
        $stockBefore = $this->menu1->fresh()->stock;

        // First submit
        $this->actingAs($this->siswa)
            ->post('/checkout/confirm', ['idempotency_key' => $key])
            ->assertRedirect();

        // Second duplicate submit with same key
        $this->actingAs($this->siswa)
            ->post('/checkout/confirm', ['idempotency_key' => $key]);

        // Only one order created, stock deducted once
        expect(Order::where('user_id', $this->siswa->id)->where('idempotency_key', $key)->count())->toBe(1);
        expect($this->menu1->fresh()->stock)->toBe($stockBefore - 2);
    }

    public function test_order_ownership_and_authorization(): void
    {
        $this->actingAs($this->siswa)
            ->post('/cart/add', ['menu_id' => $this->menu1->id, 'quantity' => 1]);

        $this->actingAs($this->siswa)
            ->post('/checkout/details', ['pickup_slot' => 'istirahat_1']);

        $this->actingAs($this->siswa)
            ->post('/checkout/confirm', []);

        $order = Order::where('user_id', $this->siswa->id)->latest('id')->first();

        // Owner can access
        $this->actingAs($this->siswa)->get("/orders/{$order->id}")->assertOk();
        $this->actingAs($this->siswa)->get("/orders/{$order->id}/success")->assertOk();

        // Other student cannot access
        $this->actingAs($this->otherSiswa)->get("/orders/{$order->id}")->assertForbidden();
        $this->actingAs($this->otherSiswa)->get("/orders/{$order->id}/success")->assertForbidden();
    }
}

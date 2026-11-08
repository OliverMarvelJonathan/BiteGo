<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Stall;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private User $siswa;

    private Stall $stallA;

    private Stall $stallB;

    private Menu $menuA1;

    private Menu $menuA2;

    private Menu $menuB1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->siswa = User::where('role', 'siswa')->first();

        $stalls = Stall::with('menus')->get();
        $this->stallA = $stalls[0];
        $this->stallB = $stalls[1];

        $this->menuA1 = $this->stallA->menus()->where('is_available', true)->where('stock', '>=', 5)->first();
        $this->menuA2 = $this->stallA->menus()->where('is_available', true)->where('id', '!=', $this->menuA1->id)->first();
        $this->menuB1 = $this->stallB->menus()->where('is_available', true)->first();
    }

    public function test_guest_is_redirected_from_cart(): void
    {
        $this->get('/cart')->assertRedirect('/login');
    }

    public function test_can_add_an_item_to_cart(): void
    {
        $this->actingAs($this->siswa)
            ->post('/cart/add', [
                'menu_id' => $this->menuA1->id,
                'quantity' => 2,
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success');

        $cart = app(CartService::class)->get();

        expect($cart['items']->count())->toBe(1);
        expect($cart['count'])->toBe(2);
        expect($cart['total'])->toBe($this->menuA1->price * 2);
        expect($cart['stall']->id)->toBe($this->stallA->id);
    }

    public function test_can_add_multiple_items_from_same_stall(): void
    {
        $this->actingAs($this->siswa)
            ->post('/cart/add', ['menu_id' => $this->menuA1->id, 'quantity' => 1]);

        $this->actingAs($this->siswa)
            ->post('/cart/add', ['menu_id' => $this->menuA2->id, 'quantity' => 2]);

        $cart = app(CartService::class)->get();

        expect($cart['items']->count())->toBe(2);
        expect($cart['count'])->toBe(3);
        expect($cart['total'])->toBe(($this->menuA1->price * 1) + ($this->menuA2->price * 2));
    }

    public function test_can_update_item_quantity(): void
    {
        $this->actingAs($this->siswa)
            ->post('/cart/add', ['menu_id' => $this->menuA1->id, 'quantity' => 1]);

        $this->actingAs($this->siswa)
            ->patch("/cart/items/{$this->menuA1->id}", ['quantity' => 4])
            ->assertRedirect();

        $cart = app(CartService::class)->get();
        expect($cart['items']->first()['quantity'])->toBe(4);
        expect($cart['total'])->toBe($this->menuA1->price * 4);
    }

    public function test_can_remove_individual_item(): void
    {
        $this->actingAs($this->siswa)
            ->post('/cart/add', ['menu_id' => $this->menuA1->id, 'quantity' => 2]);

        $this->actingAs($this->siswa)
            ->delete("/cart/items/{$this->menuA1->id}")
            ->assertRedirect();

        $cart = app(CartService::class)->get();
        expect($cart['items']->count())->toBe(0);
        expect($cart['count'])->toBe(0);
        expect($cart['stall'])->toBeNull();
    }

    public function test_detects_cross_stall_conflict(): void
    {
        // Add item from stall A
        $this->actingAs($this->siswa)
            ->post('/cart/add', ['menu_id' => $this->menuA1->id, 'quantity' => 1]);

        // Attempt adding item from stall B
        $response = $this->actingAs($this->siswa)
            ->post('/cart/add', ['menu_id' => $this->menuB1->id, 'quantity' => 1]);

        $response->assertRedirect(route('cart.index'))
            ->assertSessionHas('cart_conflict');

        // Cart still contains only stall A items
        $cart = app(CartService::class)->get();
        expect($cart['stall']->id)->toBe($this->stallA->id);
        expect($cart['items']->first()['menu']->id)->toBe($this->menuA1->id);
    }

    public function test_can_replace_cart_on_conflict(): void
    {
        $this->actingAs($this->siswa)
            ->post('/cart/add', ['menu_id' => $this->menuA1->id, 'quantity' => 1]);

        $this->actingAs($this->siswa)
            ->post('/cart/replace', [
                'menu_id' => $this->menuB1->id,
                'quantity' => 2,
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionMissing('cart_conflict');

        $cart = app(CartService::class)->get();
        expect($cart['stall']->id)->toBe($this->stallB->id);
        expect($cart['items']->first()['menu']->id)->toBe($this->menuB1->id);
        expect($cart['count'])->toBe(2);
    }

    public function test_caps_quantity_at_maximum_limit(): void
    {
        $this->actingAs($this->siswa)
            ->post('/cart/add', [
                'menu_id' => $this->menuA1->id,
                'quantity' => 15,
            ]);

        $cart = app(CartService::class)->get();
        expect($cart['items']->first()['quantity'])->toBeLessThanOrEqual(10);
    }

    public function test_caps_quantity_at_available_stock(): void
    {
        $this->menuA1->update(['stock' => 3]);

        $this->actingAs($this->siswa)
            ->post('/cart/add', [
                'menu_id' => $this->menuA1->id,
                'quantity' => 5,
            ]);

        $cart = app(CartService::class)->get();
        expect($cart['items']->first()['quantity'])->toBe(3);
    }

    public function test_flags_unavailable_menu_in_cart(): void
    {
        $this->actingAs($this->siswa)
            ->post('/cart/add', ['menu_id' => $this->menuA1->id, 'quantity' => 1]);

        // Seller makes menu unavailable
        $this->menuA1->update(['is_available' => false]);

        $cart = app(CartService::class)->get();
        expect($cart['has_errors'])->toBeTrue();
        expect($cart['items']->first()['is_available'])->toBeFalse();
    }
}

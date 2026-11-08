<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Order;
use App\Models\Stall;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $siswa;

    private User $otherSiswa;

    private User $vendor;

    private Stall $stall;

    private Menu $menu;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $siswas = User::where('role', 'siswa')->get();
        $this->siswa = $siswas[0];
        $this->otherSiswa = $siswas[1];

        $this->vendor = User::where('role', 'vendor')->first();
        $this->stall = $this->vendor->stall;
        $this->menu = $this->stall->menus()->where('is_available', true)->first();
    }

    public function test_order_creation_records_initial_status_log(): void
    {
        $order = app(OrderService::class)->place(
            $this->siswa,
            [['menu_id' => $this->menu->id, 'quantity' => 1]],
            'istirahat_1',
        );

        expect($order->statusLogs()->count())->toBe(1);

        $log = $order->statusLogs()->first();
        expect($log->status)->toBe('menunggu');
        expect($log->created_at)->not->toBeNull();
    }

    public function test_status_transitions_record_chronological_logs(): void
    {
        $order = app(OrderService::class)->place(
            $this->siswa,
            [['menu_id' => $this->menu->id, 'quantity' => 1]],
            'istirahat_1',
        );

        $service = app(OrderService::class);
        $service->transition($order, 'dimasak');
        $service->transition($order, 'siap_ambil');
        $service->transition($order, 'selesai');

        $logs = $order->fresh()->statusLogs()->get();

        expect($logs->count())->toBe(4);
        expect($logs[0]->status)->toBe('menunggu');
        expect($logs[1]->status)->toBe('dimasak');
        expect($logs[2]->status)->toBe('siap_ambil');
        expect($logs[3]->status)->toBe('selesai');
    }

    public function test_order_cancellation_records_cancellation_log(): void
    {
        $order = app(OrderService::class)->place(
            $this->siswa,
            [['menu_id' => $this->menu->id, 'quantity' => 1]],
            'istirahat_1',
        );

        app(OrderService::class)->transition($order, 'dibatalkan');

        $logs = $order->fresh()->statusLogs()->get();

        expect($logs->count())->toBe(2);
        expect($logs[0]->status)->toBe('menunggu');
        expect($logs[1]->status)->toBe('dibatalkan');
    }

    public function test_tracking_page_displays_timeline_and_timestamps(): void
    {
        $order = app(OrderService::class)->place(
            $this->siswa,
            [['menu_id' => $this->menu->id, 'quantity' => 1]],
            'istirahat_1',
        );

        app(OrderService::class)->transition($order, 'dimasak');

        $this->actingAs($this->siswa)
            ->get("/orders/{$order->id}")
            ->assertOk()
            ->assertSee('Lacak Status Pesanan')
            ->assertSee('Menunggu')
            ->assertSee('Dimasak')
            ->assertSee('Terkini')
            ->assertSee('WIB');
    }

    public function test_tracking_page_renders_cancelled_order_state(): void
    {
        $order = app(OrderService::class)->place(
            $this->siswa,
            [['menu_id' => $this->menu->id, 'quantity' => 1]],
            'istirahat_1',
        );

        app(OrderService::class)->transition($order, 'dibatalkan');

        $this->actingAs($this->siswa)
            ->get("/orders/{$order->id}")
            ->assertOk()
            ->assertSee('Pesanan Dibatalkan')
            ->assertSee('Waktu Pembatalan');
    }

    public function test_older_order_without_logs_displays_graceful_fallback(): void
    {
        // Legacy order with no status logs (created directly in DB)
        $order = Order::create([
            'order_number' => 'BG-LEGACY-TIMELINE',
            'user_id' => $this->siswa->id,
            'stall_id' => $this->stall->id,
            'pickup_slot' => 'istirahat_1',
            'pickup_code' => '9999',
            'pickup_date' => now()->toDateString(),
            'status' => 'dimasak',
            'stock_deducted' => true,
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

        expect($order->statusLogs()->count())->toBe(0);

        // Timeline still renders safely without crashing
        $this->actingAs($this->siswa)
            ->get("/orders/{$order->id}")
            ->assertOk()
            ->assertSee('Dimasak')
            ->assertSee('Terkini');
    }

    public function test_unauthorized_user_cannot_view_order_tracking(): void
    {
        $order = app(OrderService::class)->place(
            $this->siswa,
            [['menu_id' => $this->menu->id, 'quantity' => 1]],
            'istirahat_1',
        );

        $this->actingAs($this->otherSiswa)
            ->get("/orders/{$order->id}")
            ->assertForbidden();
    }
}

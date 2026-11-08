<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Langkah 1 & 2 : form pemesanan satu menu.
     */
    public function create(Menu $menu)
    {
        $menu->loadMissing('stall');

        if (! $menu->stall || ! $menu->stall->is_active) {
            abort(404);
        }

        if (! $menu->is_available || $menu->stock < 1) {
            return back()->with('error', 'Menu ini sudah tidak tersedia.');
        }

        return view('orders.create', [
            'menu' => $menu,
            'pickupSlots' => ['istirahat_1' => 'Istirahat I', 'istirahat_2' => 'Istirahat II'],
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    /**
     * Simpan pesanan baru + buat kode unik 4 digit.
     */
    public function store(Request $request, Menu $menu, OrderService $orders)
    {
        $menu->loadMissing('stall');

        if (! $menu->stall || ! $menu->stall->is_active) {
            abort(404);
        }

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'pickup_slot' => ['required', 'in:istirahat_1,istirahat_2'],
            'notes' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        $idempotencyKey = $data['idempotency_key'] ?? null;

        if ($idempotencyKey === '') {
            $idempotencyKey = null;
        }

        $order = $orders->place(
            $request->user(),
            [['menu_id' => $menu->id, 'quantity' => (int) $data['quantity']]],
            $data['pickup_slot'],
            $data['notes'] ?? null,
            $idempotencyKey,
        );

        return redirect()->route('orders.success', $order)
            ->with('success', 'Pesanan berhasil dibuat!');
    }

    /**
     * Halaman sukses / bukti kode unik pengambilan.
     */
    public function success(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load(['items', 'stall']);

        return view('orders.success', compact('order'));
    }

    /**
     * Halaman daftar pesanan user.
     */
    public function index()
    {
        $orders = auth()->user()->orders()->with(['stall', 'items'])
            ->latest('id')
            ->get();

        return view('orders.index', compact('orders'));
    }

    /**
     * Halaman detail / lacak pesanan.
     */
    public function show(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load(['items', 'stall']);

        return view('orders.show', compact('order'));
    }

    /**
     * Batalkan pesanan yang belum diproses penjual.
     */
    public function cancel(Order $order, OrderService $orders)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $orders->transition($order, 'dibatalkan');

        return back()->with('success', 'Pesanan dibatalkan.');
    }
}

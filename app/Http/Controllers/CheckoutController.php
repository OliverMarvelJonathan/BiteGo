<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    /**
     * Langkah 2: Form Detail Pengambilan & Catatan.
     */
    public function details(CartService $cart)
    {
        $cartData = $cart->get();

        if ($cartData['items']->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Keranjang Anda masih kosong. Silakan pilih menu terlebih dahulu.');
        }

        if ($cartData['has_errors']) {
            return redirect()->route('cart.index')
                ->with('error', 'Terdapat menu yang stoknya tidak mencukupi atau sudah tidak tersedia. Harap periksa kembali keranjang Anda.');
        }

        $savedDetails = session('checkout_details', [
            'pickup_slot' => 'istirahat_1',
            'notes' => '',
        ]);

        return view('checkout.details', [
            'cart' => $cartData,
            'details' => $savedDetails,
            'pickupSlots' => [
                'istirahat_1' => ['label' => 'Istirahat I', 'time' => 'Pagi · 09.30 - 10.00', 'icon' => 'fa-sun'],
                'istirahat_2' => ['label' => 'Istirahat II', 'time' => 'Siang · 12.00 - 12.30', 'icon' => 'fa-cloud-sun'],
            ],
        ]);
    }

    /**
     * Simpan detail pengambilan & lanjut ke konfirmasi (Langkah 3).
     */
    public function processDetails(Request $request, CartService $cart)
    {
        $cartData = $cart->get();

        if ($cartData['items']->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Keranjang Anda masih kosong.');
        }

        $data = $request->validate([
            'pickup_slot' => ['required', 'in:istirahat_1,istirahat_2'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        session(['checkout_details' => [
            'pickup_slot' => $data['pickup_slot'],
            'notes' => $data['notes'] ?? null,
        ]]);

        return redirect()->route('checkout.confirm');
    }

    /**
     * Langkah 3: Tinjauan Akhir & Konfirmasi Pesanan.
     */
    public function confirm(CartService $cart)
    {
        $cartData = $cart->get();

        if ($cartData['items']->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Keranjang Anda masih kosong.');
        }

        $details = session('checkout_details');

        if (! $details || empty($details['pickup_slot'])) {
            return redirect()->route('checkout.details')
                ->with('error', 'Silakan pilih jadwal pengambilan terlebih dahulu.');
        }

        return view('checkout.confirm', [
            'cart' => $cartData,
            'details' => $details,
            'idempotencyKey' => (string) Str::uuid(),
            'pickupSlotLabel' => $details['pickup_slot'] === 'istirahat_2' ? 'Istirahat II (12.00 - 12.30)' : 'Istirahat I (09.30 - 10.00)',
        ]);
    }

    /**
     * Eksekusi final pesanan melalui OrderService.
     */
    public function placeOrder(Request $request, CartService $cart, OrderService $orders)
    {
        $cartData = $cart->get();

        if ($cartData['items']->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Keranjang Anda kosong.');
        }

        $details = session('checkout_details');

        if (! $details) {
            return redirect()->route('checkout.details')
                ->with('error', 'Informasi jadwal pengambilan diperlukan.');
        }

        $data = $request->validate([
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        $idempotencyKey = $data['idempotency_key'] ?? null;
        if ($idempotencyKey === '') {
            $idempotencyKey = null;
        }

        try {
            // Re-validate cart stock, active stall, and build lines
            $lines = $cart->validateForCheckout();

            $order = $orders->place(
                $request->user(),
                $lines,
                $details['pickup_slot'],
                $details['notes'] ?? null,
                $idempotencyKey,
            );

            // Clear cart & checkout session ONLY after successful order creation
            $cart->clear();
            session()->forget('checkout_details');
            session()->forget('cart_conflict');

            return redirect()->route('orders.success', $order)
                ->with('success', 'Pesanan berhasil dibuat!');
        } catch (ValidationException $e) {
            // Cart contents are preserved! Redirect back with error
            return redirect()->route('cart.index')
                ->withErrors($e->validator);
        }
    }
}

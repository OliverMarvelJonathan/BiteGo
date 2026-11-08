<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Tampilkan keranjang belanja (Langkah 1).
     */
    public function index(CartService $cart)
    {
        $cartData = $cart->get();
        $conflict = session('cart_conflict');

        return view('cart.index', [
            'cart' => $cartData,
            'conflict' => $conflict,
        ]);
    }

    /**
     * Tambah item ke keranjang.
     */
    public function add(Request $request, CartService $cart)
    {
        $data = $request->validate([
            'menu_id' => ['required', 'integer', 'exists:menus,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $menuId = (int) $data['menu_id'];
        $quantity = (int) ($data['quantity'] ?? 1);

        $result = $cart->add($menuId, $quantity);

        if ($result['status'] === 'conflict') {
            return redirect()->route('cart.index')
                ->with('cart_conflict', [
                    'current_stall' => $result['current_stall']?->name ?? 'Stand Lain',
                    'new_stall' => $result['new_stall']?->name ?? 'Stand Baru',
                    'menu_id' => $menuId,
                    'menu_name' => $result['menu']->name,
                    'quantity' => $quantity,
                ])
                ->with('error', "Keranjang Anda berisi menu dari {$result['current_stall']?->name}. Ganti stand untuk memesan dari {$result['new_stall']?->name}.");
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Menu berhasil ditambahkan ke keranjang!',
                'count' => $cart->count(),
            ]);
        }

        if ($request->has('buy_now')) {
            return redirect()->route('checkout.details');
        }

        return redirect()->route('cart.index')
            ->with('success', 'Menu berhasil ditambahkan ke keranjang!');
    }

    /**
     * Ubah jumlah item di keranjang.
     */
    public function update(Request $request, Menu $menu, CartService $cart)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:10'],
        ]);

        $cart->update($menu->id, (int) $data['quantity']);

        return back()->with('success', 'Jumlah pesanan diperbarui.');
    }

    /**
     * Hapus item dari keranjang.
     */
    public function remove(Menu $menu, CartService $cart)
    {
        $cart->remove($menu->id);

        return back()->with('success', 'Item berhasil dihapus dari keranjang.');
    }

    /**
     * Kosongkan seluruh keranjang.
     */
    public function clear(CartService $cart)
    {
        $cart->clear();
        session()->forget('cart_conflict');

        return redirect()->route('cart.index')
            ->with('success', 'Keranjang berhasil dikosongkan.');
    }

    /**
     * Resolusi konflik beda stand: kosongkan keranjang lama dan ganti dengan stand baru.
     */
    public function replace(Request $request, CartService $cart)
    {
        $data = $request->validate([
            'menu_id' => ['required', 'integer', 'exists:menus,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $cart->replaceWith((int) $data['menu_id'], (int) ($data['quantity'] ?? 1));
        session()->forget('cart_conflict');

        return redirect()->route('cart.index')
            ->with('success', 'Keranjang berhasil diganti dengan menu dari stand baru!');
    }
}

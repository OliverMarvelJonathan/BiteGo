<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VendorController extends Controller
{
    /**
     * Dashboard penjual: rekap pesanan real-time per status.
     */
    public function dashboard()
    {
        $stall = Auth::user()->stall;

        if (! $stall) {
            return view('vendor.dashboard', ['stall' => null]);
        }

        $orders = $stall->orders()->with(['items', 'user'])->latest('id')->get();

        $counts = [
            'menunggu' => $orders->where('status', 'menunggu')->count(),
            'dimasak' => $orders->where('status', 'dimasak')->count(),
            'siap_ambil' => $orders->where('status', 'siap_ambil')->count(),
            'selesai' => $orders->where('status', 'selesai')->count(),
            'dibatalkan' => $orders->where('status', 'dibatalkan')->count(),
            'revenue' => $orders->whereIn('status', ['selesai', 'siap_ambil', 'dimasak', 'menunggu'])
                ->sum('total_amount'),
        ];

        return view('vendor.dashboard', compact('stall', 'orders', 'counts'));
    }

    /**
     * Update status pesanan (Menunggu → Dimasak → Siap Ambil → Selesai).
     */
    public function updateStatus(Request $request, Order $order, OrderService $orders)
    {
        if ($order->stall->user_id !== Auth::id()) {
            abort(403);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUS_LABELS))],
        ]);

        $updated = $orders->transition($order, $data['status']);

        return back()->with('success', "Status pesanan {$updated->order_number} diperbarui menjadi \"{$updated->status_label}\".");
    }

    /**
     * Kelola menu & stok milik stand penjual yang sedang login.
     */
    public function menus()
    {
        $stall = Auth::user()->stall;

        if (! $stall) {
            return view('vendor.menu', ['stall' => null, 'menus' => collect(), 'categories' => collect(), 'stats' => []]);
        }

        $menus = $stall->menus()->with('category')->orderBy('category_id')->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        $stats = [
            'total' => $menus->count(),
            'stock' => $menus->sum('stock'),
            'available' => $menus->where('is_available', true)->count(),
            'popular' => $menus->where('is_popular', true)->count(),
        ];

        return view('vendor.menu', compact('stall', 'menus', 'categories', 'stats'));
    }

    /**
     * Tambah menu baru ke stand penjual.
     */
    public function menuStore(Request $request)
    {
        $stall = Auth::user()->stall;

        if (! $stall) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('menus')->where(fn ($q) => $q->where('stall_id', $stall->id))],
            'description' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'price' => ['required', 'integer', 'min:100', 'max:10000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'image' => ['nullable', 'url', 'max:255'],
        ]);

        $stall->menus()->create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'image' => $data['image'] ?? null,
            'is_available' => true,
            'is_popular' => false,
        ]);

        return redirect()->route('vendor.menu.index')
            ->with('success', "Menu \"{$data['name']}\" berhasil ditambahkan ke stand Anda.");
    }

    /**
     * Perbarui stok & harga menu milik penjual.
     */
    public function menuUpdate(Request $request, Menu $menu)
    {
        $this->authorizeMenuOwner($menu);

        $data = $request->validate([
            'price' => ['required', 'integer', 'min:100', 'max:10000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'stock_baseline' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        // Treat the submitted stock as a delta from the stock shown when the form was
        // rendered. This keeps concurrent order deductions from being overwritten by a
        // stale absolute value.
        $baseline = (int) ($data['stock_baseline'] ?? $menu->stock);
        $delta = (int) $data['stock'] - $baseline;
        $isAvailable = $request->boolean('is_available');
        $isPopular = $request->boolean('is_popular');

        $updated = DB::transaction(function () use ($menu, $data, $delta, $isAvailable, $isPopular) {
            $locked = Menu::whereKey($menu->getKey())->lockForUpdate()->firstOrFail();
            $newStock = $locked->stock + $delta;

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'stock' => "Stok tidak dapat dikurangi melebihi stok saat ini ({$locked->stock}).",
                ]);
            }

            $locked->update([
                'price' => $data['price'],
                'stock' => $newStock,
                'is_available' => $isAvailable,
                'is_popular' => $isPopular,
            ]);

            return $locked;
        }, 3);

        return back()->with('success', "Menu \"{$updated->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus menu milik penjual.
     */
    public function menuDestroy(Menu $menu)
    {
        $this->authorizeMenuOwner($menu);

        $menu->delete();

        return back()->with('success', "Menu \"{$menu->name}\" telah dihapus.");
    }

    /**
     * Pastikan menu benar-benar milik penjual yang sedang login.
     */
    private function authorizeMenuOwner(Menu $menu): void
    {
        if ($menu->stall->user_id !== Auth::id()) {
            abort(403);
        }
    }
}

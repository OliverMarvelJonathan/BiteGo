<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Stall;
use Illuminate\Http\Request;

class CanteenController extends Controller
{
    /**
     * Halaman utama BiteGo.
     */
    public function index()
    {
        $stalls = Stall::withCount('menus')->where('is_active', true)->get();
        $categories = Category::orderBy('id')->get();
        $popularMenus = Menu::with('stall')
            ->whereHas('stall', fn ($s) => $s->where('is_active', true))
            ->where('is_available', true)
            ->where('is_popular', true)
            ->take(8)
            ->get();

        return view('canteen.index', compact('stalls', 'categories', 'popularMenus'));
    }

    /**
     * Halaman daftar menu lengkap (dengan filter kategori & pencarian).
     */
    public function menu(Request $request)
    {
        $activeCategory = $request->query('category', 'makanan-utama');

        $categories = Category::orderBy('id')->get();

        $query = Menu::with(['stall', 'category'])
            ->whereHas('stall', fn ($s) => $s->where('is_active', true))
            ->where('is_available', true);

        if ($request->filled('q')) {
            $search = $request->query('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('stall', fn ($s) => $s->where('name', 'like', "%{$search}%"));
            });
        } else {
            $query->whereHas('category', fn ($c) => $c->where('slug', $activeCategory));
        }

        $menus = $query->get();

        return view('canteen.menu', compact('menus', 'categories', 'activeCategory'));
    }

    /**
     * Halaman detail satu stand kantin beserta daftar menunya.
     */
    public function stall(Stall $stall)
    {
        if (! $stall->is_active) {
            abort(404);
        }

        $stall->load(['menus.category', 'owner']);
        $menus = $stall->menus()->where('is_available', true)->get();

        return view('canteen.stall', compact('stall', 'menus'));
    }
}

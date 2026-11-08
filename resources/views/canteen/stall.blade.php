@extends('layouts.app')

@section('title', $stall->name)

@section('content')
    {{-- Breadcrumb --}}
    <nav class="text-xs text-gray-400 mb-5 flex items-center gap-2">
        <a href="{{ route('canteen.index') }}" class="hover:text-emerald-600 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-[8px]"></i>
        <a href="{{ route('canteen.menu') }}" class="hover:text-emerald-600 transition">Menu</a>
        <i class="fa-solid fa-chevron-right text-[8px]"></i>
        <span class="text-gray-600 font-semibold">{{ $stall->name }}</span>
    </nav>

    {{-- Stall header --}}
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mb-8 flex flex-col sm:flex-row items-start sm:items-center gap-5">
        <div class="w-20 h-20 rounded-2xl overflow-hidden shrink-0 bg-gray-100 shadow-md">
            <img src="{{ $stall->image }}" alt="{{ $stall->name }}" class="w-full h-full object-cover">
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <h2 class="text-2xl font-bold text-gray-900">{{ $stall->name }}</h2>
                <span class="bg-emerald-500/10 text-emerald-700 text-[10px] font-bold px-2.5 py-1 rounded-full">Buka</span>
            </div>
            <p class="text-sm text-gray-500 mt-1.5">{{ $stall->description }}</p>
            <div class="flex items-center gap-4 mt-3 text-xs text-gray-400">
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-location-dot text-emerald-400"></i> {{ $stall->location }}</span>
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-utensils text-emerald-400"></i> {{ $menus->count() }} menu</span>
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-user-check text-emerald-400"></i> {{ $stall->owner->name ?? '-' }}</span>
            </div>
        </div>
    </div>

    {{-- Menu list --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold text-gray-800"><i class="fa-solid fa-utensils text-emerald-500 mr-2"></i>Daftar Menu</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        @forelse ($menus as $menu)
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between hover:shadow-md transition">
                <div>
                    <div class="relative w-full h-32 rounded-xl overflow-hidden mb-3 bg-gray-100">
                        <img src="{{ $menu->image }}" alt="{{ $menu->name }}" class="w-full h-full object-cover">
                        @if ($menu->stock > 0 && $menu->stock <= 10)
                            <span class="absolute top-2 left-2 bg-amber-400 text-gray-900 text-[9px] font-bold px-2 py-1 rounded-full">Sisa {{ $menu->stock }}</span>
                        @endif
                    </div>
                    <span class="inline-block bg-gray-100 text-gray-500 text-[9px] font-bold px-2 py-0.5 rounded-full mb-2">{{ $menu->category->name ?? 'Menu' }}</span>
                    <h4 class="font-bold text-sm text-gray-900 mb-1 line-clamp-1">{{ $menu->name }}</h4>
                    <p class="text-[11px] text-gray-400 leading-snug line-clamp-2 mb-3">{{ $menu->description }}</p>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-gray-50">
                    <span class="font-bold text-emerald-600 text-sm">Rp{{ number_format($menu->price, 0, ',', '.') }}</span>
                    @if ($menu->stock > 0)
                        @auth
                            @if (! auth()->user()->isVendor())
                                <div class="flex items-center gap-1.5">
                                    <form action="{{ route('cart.add') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" title="Tambah ke Keranjang"
                                                class="w-8 h-8 bg-mint-50 hover:bg-mint-100 text-mint-700 rounded-lg flex items-center justify-center transition border border-mint-200">
                                            <i class="fa-solid fa-cart-plus text-xs"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('orders.create', $menu) }}" class="px-3 py-1.5 bg-mint-400 hover:bg-mint-500 text-white text-[11px] font-semibold rounded-lg flex items-center gap-1 transition">
                                        <i class="fa-solid fa-plus text-[9px]"></i> Pesan
                                    </a>
                                </div>
                            @endif
                        @else
                            <a href="{{ route('auth.login', ['redirect' => route('orders.create', $menu)]) }}" class="px-3 py-1.5 bg-mint-400 hover:bg-mint-500 text-white text-[11px] font-semibold rounded-lg flex items-center gap-1 transition">
                                <i class="fa-solid fa-plus text-[9px]"></i> Pesan
                            </a>
                        @endauth
                    @else
                        <span class="px-3 py-1 bg-gray-100 text-gray-400 text-[11px] font-semibold rounded-lg">Habis</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl p-10 text-center border border-dashed border-gray-200">
                <i class="fa-solid fa-utensils text-3xl text-gray-300 mb-3"></i>
                <p class="text-gray-400 text-sm">Belum ada menu di stand ini.</p>
            </div>
        @endforelse
    </div>

    {{-- Express pickup info --}}
    <div class="mt-8 bg-gradient-to-r from-mint-700 to-mint-500 rounded-3xl p-6 text-white shadow-lg shadow-emerald-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center text-xl">
                <i class="fa-solid fa-bolt"></i>
            </div>
            <div>
                <h4 class="font-bold text-sm">Express Pickup</h4>
                <p class="text-xs text-emerald-100 mt-0.5">Pesanan siap diambil tanpa antre. Tunjukkan kode unik 4 digit ke konter.</p>
            </div>
        </div>
        <a href="{{ route('canteen.menu', ['category' => $menus->first()?->category->slug ?? 'makanan-utama']) }}"
           class="inline-flex items-center gap-2 bg-white text-mint-700 font-bold text-xs uppercase tracking-wider px-5 py-2.5 rounded-full shadow-md hover:bg-emerald-50 transition shrink-0">
            Lihat Menu Lain <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
@endsection
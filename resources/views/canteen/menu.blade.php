@extends('layouts.app')

@section('title', 'Menu')

@section('content')
    {{-- Search Bar --}}
    <div class="mb-6">
        <form method="GET" action="{{ route('canteen.menu') }}" class="relative w-full sm:w-80">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari makanan atau minuman..."
                   class="w-full pl-10 pr-10 py-2 bg-white rounded-lg text-xs border border-gray-200 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            @if (request('q'))
                <a href="{{ route('canteen.menu') }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            @endif
        </form>
    </div>

    {{-- Page Title --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Menu</h2>
        <p class="text-xs text-gray-500 mt-0.5">Pilih Kategori</p>
    </div>

    {{-- Category Tabs --}}
    <div class="border-b border-gray-200 mb-8 overflow-x-auto">
        <div class="flex gap-8 text-xs font-semibold whitespace-nowrap">
            @foreach ($categories as $category)
                <a href="{{ route('canteen.menu', ['category' => $category->slug]) }}"
                   class="pb-3 border-b-2 transition {{ $activeCategory === $category->slug ? 'border-mint-400 text-mint-400' : 'border-transparent text-gray-400 hover:text-gray-600' }}">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Menu Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
        @forelse ($menus as $menu)
            <div class="bg-white rounded-2xl p-3.5 shadow-sm border border-gray-100 flex flex-col justify-between hover:shadow-md transition">
                <div>
                    <div class="relative w-full h-32 rounded-xl overflow-hidden mb-3 bg-gray-100">
                        <img src="{{ $menu->image }}" alt="{{ $menu->name }}" class="w-full h-full object-cover">
                        @if ($menu->stock > 0 && $menu->stock <= 10)
                            <span class="absolute top-2 left-2 bg-amber-400 text-gray-900 text-[9px] font-bold px-2 py-1 rounded-full">Sisa {{ $menu->stock }}</span>
                        @elseif ($menu->stock <= 0)
                            <span class="absolute top-2 left-2 bg-gray-900/80 text-white text-[9px] font-bold px-2 py-1 rounded-full">Habis</span>
                        @endif
                    </div>
                    <h4 class="font-bold text-xs text-gray-900 mb-1 line-clamp-1">{{ $menu->name }}</h4>
                    <p class="text-[10px] text-gray-400 leading-snug line-clamp-2 mb-4">{{ $menu->description }}</p>
                    <p class="text-[10px] text-emerald-600 font-semibold flex items-center gap-1 mb-3">
                        <i class="fa-solid fa-store"></i> {{ $menu->stall->name ?? '-' }}
                    </p>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-gray-50">
                    <span class="font-bold text-gray-900 text-xs">Rp {{ number_format($menu->price, 0, ',', '.') }}</span>
                    @if ($menu->stock > 0)
                        @auth
                            @if (! auth()->user()->isVendor())
                                <div class="flex items-center gap-1.5">
                                    <form action="{{ route('cart.add') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" title="Tambah ke Keranjang"
                                                class="w-7 h-7 bg-mint-50 hover:bg-mint-100 text-mint-700 rounded-lg flex items-center justify-center transition border border-mint-200">
                                            <i class="fa-solid fa-cart-plus text-[10px]"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('orders.create', $menu) }}"
                                       class="px-2.5 py-1 bg-mint-400 hover:bg-mint-500 text-white text-[11px] font-semibold rounded-lg flex items-center gap-1 transition">
                                        Pesan
                                    </a>
                                </div>
                            @endif
                        @else
                            <a href="{{ route('auth.login', ['redirect' => route('orders.create', $menu)]) }}"
                               class="px-3 py-1 bg-mint-400 hover:bg-mint-500 text-white text-[11px] font-semibold rounded-lg flex items-center gap-1 transition">
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
                <p class="text-gray-400 text-sm">Tidak ada menu yang ditemukan untuk pencarian ini.</p>
            </div>
        @endforelse
    </div>
@endsection
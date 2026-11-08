@extends('layouts.app')

@section('title', 'Keranjang Belanja · Langkah 1')

@section('content')
    {{-- Breadcrumb --}}
    <nav class="text-xs text-gray-400 mb-5 flex items-center gap-2">
        <a href="{{ route('canteen.index') }}" class="hover:text-emerald-600 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-[8px]"></i>
        <a href="{{ route('canteen.menu') }}" class="hover:text-emerald-600 transition">Menu</a>
        <i class="fa-solid fa-chevron-right text-[8px]"></i>
        <span class="text-gray-600 font-semibold">Keranjang Belanja</span>
    </nav>

    {{-- Three-Step Checkout Stepper --}}
    <div class="flex items-center justify-center gap-2 md:gap-4 mb-8">
        {{-- Step 1: Active --}}
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-mint-400 text-white flex items-center justify-center text-xs font-bold shadow-md shadow-emerald-500/20">
                1
            </div>
            <span class="text-xs font-bold text-mint-700">Keranjang</span>
        </div>
        <div class="w-8 md:w-14 h-0.5 bg-gray-200"></div>

        {{-- Step 2: Upcoming --}}
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center text-xs font-bold">
                2
            </div>
            <span class="text-xs font-bold text-gray-400">Jadwal & Catatan</span>
        </div>
        <div class="w-8 md:w-14 h-0.5 bg-gray-200"></div>

        {{-- Step 3: Upcoming --}}
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center text-xs font-bold">
                3
            </div>
            <span class="text-xs font-bold text-gray-400">Konfirmasi</span>
        </div>
    </div>

    {{-- Conflict Alert Modal / Banner --}}
    @if (! empty($conflict))
        <div class="max-w-4xl mx-auto mb-8 bg-amber-50 border-2 border-amber-300 rounded-3xl p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-400 text-gray-900 flex items-center justify-center shrink-0 text-xl shadow-sm">
                    <i class="fa-solid fa-store-slash"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-bold text-gray-900">Konflik Stand Kantin</h3>
                    <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                        Keranjang Anda saat ini berisi menu dari <span class="font-bold text-gray-800">{{ $conflict['current_stall'] }}</span>.
                        Anda baru saja memilih <span class="font-bold text-gray-800">{{ $conflict['menu_name'] }}</span> dari <span class="font-bold text-gray-800">{{ $conflict['new_stall'] }}</span>.
                        Dalam satu pesanan, semua item harus berasal dari stand yang sama.
                    </p>
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        <form action="{{ route('cart.replace') }}" method="POST">
                            @csrf
                            <input type="hidden" name="menu_id" value="{{ $conflict['menu_id'] }}">
                            <input type="hidden" name="quantity" value="{{ $conflict['quantity'] ?? 1 }}">
                            <button type="submit" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition">
                                <i class="fa-solid fa-arrows-rotate"></i> Kosongkan & Ganti ke {{ $conflict['new_stall'] }}
                            </button>
                        </form>
                        <form action="{{ route('cart.clear') }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-gray-500 hover:text-gray-700 px-3 py-2.5 transition">
                                Batalkan Pilihan Baru
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="max-w-4xl mx-auto">
        @if ($cart['items']->isEmpty())
            {{-- Empty State --}}
            <div class="bg-white rounded-3xl p-14 text-center border border-dashed border-gray-200 shadow-sm">
                <div class="w-20 h-20 bg-mint-50 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 text-mint-400">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-1">Keranjang Belanja Masih Kosong</h3>
                <p class="text-xs text-gray-400 mb-6 max-w-sm mx-auto">
                    Yuk pilih menu lezat dari stand kantin favoritmu dan lakukan pre-order sebelum jam istirahat!
                </p>
                <a href="{{ route('canteen.menu') }}"
                   class="inline-flex items-center gap-2 bg-mint-400 hover:bg-mint-500 text-white text-xs font-bold px-6 py-3 rounded-full shadow-md shadow-emerald-500/20 transition">
                    <i class="fa-solid fa-utensils"></i> Jelajahi Menu Kantin
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                {{-- Items List (2 cols) --}}
                <div class="lg:col-span-2 space-y-4">
                    {{-- Stall Header Card --}}
                    @if ($cart['stall'])
                        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl overflow-hidden shrink-0 bg-gray-100">
                                    <img src="{{ $cart['stall']->image }}" alt="{{ $cart['stall']->name }}" class="w-full h-full object-cover">
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-xs font-bold text-gray-900 truncate">{{ $cart['stall']->name }}</p>
                                        <span class="text-[9px] bg-mint-100 text-mint-700 font-bold px-2 py-0.5 rounded-full">Stand Aktif</span>
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-0.5 flex items-center gap-1">
                                        <i class="fa-solid fa-location-dot text-emerald-400"></i> {{ $cart['stall']->location }}
                                    </p>
                                </div>
                            </div>
                            <form action="{{ route('cart.clear') }}" method="POST" onsubmit="return confirm('Kosongkan semua item di keranjang?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[11px] font-semibold text-rose-500 hover:text-rose-600 px-3 py-1.5 rounded-lg border border-rose-200 hover:bg-rose-50 transition">
                                    <i class="fa-solid fa-trash-can text-[10px] mr-1"></i> Kosongkan
                                </button>
                            </form>
                        </div>
                    @endif

                    {{-- Cart Items Cards --}}
                    @foreach ($cart['items'] as $item)
                        @php
                            $menu = $item['menu'];
                        @endphp
                        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 {{ ! $item['is_available'] || ! $item['is_stock_sufficient'] ? 'ring-2 ring-rose-300' : '' }}">
                            {{-- Info --}}
                            <div class="flex items-center gap-3.5 flex-1 min-w-0">
                                <div class="w-16 h-16 rounded-xl overflow-hidden shrink-0 bg-gray-100">
                                    <img src="{{ $menu->image }}" alt="{{ $menu->name }}" class="w-full h-full object-cover">
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-sm font-bold text-gray-900 truncate">{{ $menu->name }}</h4>
                                    <p class="text-xs text-emerald-600 font-bold mt-0.5">Rp{{ number_format($item['unit_price'], 0, ',', '.') }}</p>

                                    @if (! $item['is_available'])
                                        <span class="inline-block text-[10px] text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full font-semibold mt-1">
                                            Menu sudah tidak tersedia
                                        </span>
                                    @elseif (! $item['is_stock_sufficient'])
                                        <span class="inline-block text-[10px] text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full font-semibold mt-1">
                                            Stok tidak cukup (sisa: {{ $menu->stock }})
                                        </span>
                                    @else
                                        <span class="text-[10px] text-gray-400 block mt-0.5">Sisa stok: {{ $menu->stock }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Quantity Stepper & Subtotal --}}
                            <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0 pt-3 sm:pt-0 border-t sm:border-t-0 border-gray-100">
                                {{-- Stepper --}}
                                <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                                    {{-- Minus Button --}}
                                    <form action="{{ route('cart.update', $menu) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="quantity" value="{{ $item['quantity'] - 1 }}">
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition">
                                            <i class="fa-solid fa-minus text-[10px]"></i>
                                        </button>
                                    </form>

                                    {{-- Current Qty --}}
                                    <span class="w-10 text-center text-xs font-bold text-gray-800">
                                        {{ $item['quantity'] }}
                                    </span>

                                    {{-- Plus Button --}}
                                    <form action="{{ route('cart.update', $menu) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="quantity" value="{{ min(10, $item['quantity'] + 1) }}">
                                        <button type="submit"
                                                class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition {{ $item['quantity'] >= min(10, $menu->stock) ? 'opacity-40 cursor-not-allowed' : '' }}"
                                                {{ $item['quantity'] >= min(10, $menu->stock) ? 'disabled' : '' }}>
                                            <i class="fa-solid fa-plus text-[10px]"></i>
                                        </button>
                                    </form>
                                </div>

                                {{-- Line Subtotal --}}
                                <div class="text-right min-w-[5rem]">
                                    <p class="text-xs font-extrabold text-mint-600">Rp{{ number_format($item['subtotal'], 0, ',', '.') }}</p>
                                </div>

                                {{-- Delete Button --}}
                                <form action="{{ route('cart.remove', $menu) }}" method="POST" onsubmit="return confirm('Hapus {{ $menu->name }} dari keranjang?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-rose-500 p-1.5 transition">
                                        <i class="fa-solid fa-xmark text-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach

                    <div class="pt-2">
                        <a href="{{ route('canteen.menu') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-600 hover:underline">
                            <i class="fa-solid fa-plus text-[10px]"></i> Tambah Menu Lain
                        </a>
                    </div>
                </div>

                {{-- Order Summary Sidebar (1 col) --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 sticky top-8">
                        <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-receipt text-emerald-500"></i> Ringkasan Belanja
                        </h3>

                        <div class="space-y-2.5 text-xs pb-4 border-b border-gray-100">
                            <div class="flex justify-between text-gray-500">
                                <span>Total Porsi</span>
                                <span class="font-bold text-gray-800">{{ $cart['count'] }} porsi</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>Stand Kantin</span>
                                <span class="font-bold text-gray-800 truncate max-w-[120px]">{{ $cart['stall']->name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>Subtotal</span>
                                <span class="font-bold text-gray-800">Rp{{ number_format($cart['subtotal'], 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center py-4 text-sm font-extrabold text-gray-900">
                            <span>Total Tagihan</span>
                            <span class="text-mint-600 text-lg">Rp{{ number_format($cart['total'], 0, ',', '.') }}</span>
                        </div>

                        @if ($cart['has_errors'])
                            <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-xs text-rose-600 mb-4 leading-relaxed">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>
                                Harap periksa item Anda. Beberapa menu telah habis atau jumlah melebihi stok yang tersedia.
                            </div>
                            <button disabled class="w-full bg-gray-200 text-gray-400 font-bold text-xs py-3.5 rounded-xl cursor-not-allowed">
                                Lanjut ke Pengambilan
                            </button>
                        @else
                            <a href="{{ route('checkout.details') }}"
                               class="w-full flex items-center justify-center gap-2 bg-mint-400 hover:bg-mint-500 text-white font-bold text-xs py-3.5 rounded-xl shadow-md shadow-emerald-500/20 transition">
                                Lanjut ke Pengambilan <i class="fa-solid fa-arrow-right text-[11px]"></i>
                            </a>
                        @endif

                        <p class="text-[10px] text-gray-400 text-center mt-3 flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                            Stok dan harga diverifikasi langsung dari server.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

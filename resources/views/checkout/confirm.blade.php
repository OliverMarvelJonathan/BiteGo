@extends('layouts.app')

@section('title', 'Konfirmasi Pesanan · Langkah 3')

@section('content')
    {{-- Breadcrumb --}}
    <nav class="text-xs text-gray-400 mb-5 flex items-center gap-2">
        <a href="{{ route('canteen.index') }}" class="hover:text-emerald-600 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-[8px]"></i>
        <a href="{{ route('cart.index') }}" class="hover:text-emerald-600 transition">Keranjang</a>
        <i class="fa-solid fa-chevron-right text-[8px]"></i>
        <a href="{{ route('checkout.details') }}" class="hover:text-emerald-600 transition">Jadwal & Catatan</a>
        <i class="fa-solid fa-chevron-right text-[8px]"></i>
        <span class="text-gray-600 font-semibold">Konfirmasi</span>
    </nav>

    {{-- Three-Step Checkout Stepper --}}
    <div class="flex items-center justify-center gap-2 md:gap-4 mb-8">
        {{-- Step 1: Completed --}}
        <a href="{{ route('cart.index') }}" class="flex items-center gap-2 group">
            <div class="w-8 h-8 rounded-full bg-mint-400 text-white flex items-center justify-center text-xs font-bold shadow-md shadow-emerald-500/20">
                <i class="fa-solid fa-check"></i>
            </div>
            <span class="text-xs font-bold text-mint-700 group-hover:underline">Keranjang</span>
        </a>
        <div class="w-8 md:w-14 h-0.5 bg-mint-400"></div>

        {{-- Step 2: Completed --}}
        <a href="{{ route('checkout.details') }}" class="flex items-center gap-2 group">
            <div class="w-8 h-8 rounded-full bg-mint-400 text-white flex items-center justify-center text-xs font-bold shadow-md shadow-emerald-500/20">
                <i class="fa-solid fa-check"></i>
            </div>
            <span class="text-xs font-bold text-mint-700 group-hover:underline">Jadwal & Catatan</span>
        </a>
        <div class="w-8 md:w-14 h-0.5 bg-sunshine-400"></div>

        {{-- Step 3: Active --}}
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-sunshine-400 text-gray-900 flex items-center justify-center text-xs font-bold shadow-md">
                3
            </div>
            <span class="text-xs font-bold text-gray-800">Konfirmasi</span>
        </div>
    </div>

    <div class="max-w-3xl mx-auto">
        <form method="POST" action="{{ route('checkout.placeOrder') }}">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

            {{-- Main Confirmation Card --}}
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-8 mb-6 space-y-6">
                <div>
                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-mint-700 bg-mint-50 px-3 py-1 rounded-full mb-2">
                        <i class="fa-solid fa-shield-check"></i> Tinjauan Akhir
                    </span>
                    <h2 class="text-xl font-bold text-gray-900">Periksa & Konfirmasi Pesanan</h2>
                    <p class="text-xs text-gray-400 mt-1">Pastikan rincian pesanan dan jadwal pengambilan sudah sesuai sebelum konfirmasi.</p>
                </div>

                {{-- Stall & Schedule Meta --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Stall --}}
                    <div class="bg-mint-50/60 rounded-2xl p-4 border border-mint-100 flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0 bg-white shadow-sm">
                            <img src="{{ $cart['stall']->image }}" alt="{{ $cart['stall']->name }}" class="w-full h-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] uppercase font-bold text-mint-700">Stand Kantin</p>
                            <h4 class="text-sm font-bold text-gray-900 truncate">{{ $cart['stall']->name }}</h4>
                            <p class="text-[11px] text-gray-500 mt-0.5 flex items-center gap-1">
                                <i class="fa-solid fa-location-dot text-emerald-400"></i> {{ $cart['stall']->location }}
                            </p>
                        </div>
                    </div>

                    {{-- Schedule --}}
                    <div class="bg-sunshine-100/50 rounded-2xl p-4 border border-sunshine-200 flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-sunshine-400 text-gray-900 flex items-center justify-center text-lg font-bold shrink-0 shadow-sm">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] uppercase font-bold text-yellow-800">Jadwal Pengambilan</p>
                            <h4 class="text-sm font-bold text-gray-900">{{ $pickupSlotLabel }}</h4>
                            <p class="text-[11px] text-gray-500 mt-0.5">Siap di Express Pickup</p>
                        </div>
                    </div>
                </div>

                {{-- Notes (if any) --}}
                @if (! empty($details['notes']))
                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 text-xs">
                        <span class="font-bold text-gray-700 flex items-center gap-1.5 mb-1">
                            <i class="fa-solid fa-note-sticky text-sunshine-600"></i> Catatan untuk Penjual:
                        </span>
                        <p class="text-gray-600 leading-relaxed pl-5">{{ $details['notes'] }}</p>
                    </div>
                @endif

                {{-- Items Breakdown --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wide mb-3 flex items-center gap-1.5">
                        <i class="fa-solid fa-basket-shopping text-emerald-500"></i> Menu yang Dipesan
                    </h3>
                    <div class="divide-y divide-gray-100 border border-gray-100 rounded-2xl overflow-hidden">
                        @foreach ($cart['items'] as $item)
                            <div class="p-4 flex items-center justify-between gap-4 bg-white hover:bg-gray-50/50 transition">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0 bg-gray-100">
                                        <img src="{{ $item['menu']->image }}" alt="{{ $item['menu']->name }}" class="w-full h-full object-cover">
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-gray-900 truncate">{{ $item['menu']->name }}</p>
                                        <p class="text-[11px] text-gray-400">Rp{{ number_format($item['unit_price'], 0, ',', '.') }} &times; {{ $item['quantity'] }} porsi</p>
                                    </div>
                                </div>
                                <span class="text-sm font-extrabold text-gray-900 shrink-0">
                                    Rp{{ number_format($item['subtotal'], 0, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Total Calculation --}}
                <div class="bg-mint-50 rounded-2xl p-5 border border-mint-100 space-y-2 text-xs">
                    <div class="flex justify-between text-gray-600">
                        <span>Total Porsi</span>
                        <span class="font-bold text-gray-800">{{ $cart['count'] }} porsi</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal Makanan</span>
                        <span class="font-bold text-gray-800">Rp{{ number_format($cart['subtotal'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm font-extrabold text-gray-900 pt-3 border-t border-mint-200">
                        <span>Total Bayar</span>
                        <span class="text-mint-600 text-xl font-mono">Rp{{ number_format($cart['total'], 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3">
                    <a href="{{ route('checkout.details') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl border border-gray-200 text-gray-700 text-xs font-bold hover:border-emerald-300 transition">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i> Ubah Jadwal / Catatan
                    </a>
                    <button type="submit"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-mint-400 hover:bg-mint-500 text-white font-bold text-sm px-8 py-3.5 rounded-xl shadow-md shadow-emerald-500/20 transition">
                        <i class="fa-solid fa-circle-check"></i> Konfirmasi & Buat Pesanan
                    </button>
                </div>
            </div>
        </form>

        <p class="text-center text-[11px] text-gray-400 flex items-center justify-center gap-2">
            <i class="fa-solid fa-lock text-emerald-400"></i>
            Transaksi aman dan stok dijamin terkunci secara atomik dengan Express Pickup.
        </p>
    </div>
@endsection

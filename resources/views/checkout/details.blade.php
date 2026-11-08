@extends('layouts.app')

@section('title', 'Detail Pengambilan · Langkah 2')

@section('content')
    {{-- Breadcrumb --}}
    <nav class="text-xs text-gray-400 mb-5 flex items-center gap-2">
        <a href="{{ route('canteen.index') }}" class="hover:text-emerald-600 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-[8px]"></i>
        <a href="{{ route('cart.index') }}" class="hover:text-emerald-600 transition">Keranjang</a>
        <i class="fa-solid fa-chevron-right text-[8px]"></i>
        <span class="text-gray-600 font-semibold">Jadwal & Catatan</span>
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

        {{-- Step 2: Active --}}
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-sunshine-400 text-gray-900 flex items-center justify-center text-xs font-bold shadow-md">
                2
            </div>
            <span class="text-xs font-bold text-gray-800">Jadwal & Catatan</span>
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

    <div class="max-w-4xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            {{-- Form (2 cols) --}}
            <div class="lg:col-span-2">
                <form method="POST" action="{{ route('checkout.processDetails') }}" class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-8 space-y-6">
                    @csrf

                    {{-- Stall Card --}}
                    @if ($cart['stall'])
                        <div class="bg-mint-50 rounded-2xl p-4 flex items-center gap-4 border border-mint-100">
                            <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0 bg-white shadow-sm">
                                <img src="{{ $cart['stall']->image }}" alt="{{ $cart['stall']->name }}" class="w-full h-full object-cover">
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] text-mint-700 uppercase tracking-wider font-bold">Stand Tujuan</span>
                                <h4 class="text-sm font-bold text-gray-900 truncate">{{ $cart['stall']->name }}</h4>
                                <p class="text-[11px] text-gray-500 mt-0.5 flex items-center gap-1.5">
                                    <i class="fa-solid fa-location-dot text-emerald-500"></i> {{ $cart['stall']->location }}
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- Jadwal Pengambilan --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-800 mb-2">
                            Pilih Jadwal Pengambilan <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-[11px] text-gray-400 mb-3">Pesanan akan disiapkan oleh penjual sebelum jam istirahat dimulai.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($pickupSlots as $key => $slot)
                                <label class="cursor-pointer">
                                    <input type="radio" name="pickup_slot" value="{{ $key }}"
                                           @checked(old('pickup_slot', $details['pickup_slot'] ?? 'istirahat_1') === $key)
                                           class="peer sr-only">
                                    <div class="border-2 border-gray-200 rounded-2xl p-4 peer-checked:border-emerald-400 peer-checked:bg-emerald-50/50 hover:border-emerald-300 transition">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="w-8 h-8 rounded-full {{ $key === 'istirahat_1' ? 'bg-sunshine-400 text-gray-900' : 'bg-amber-400 text-white' }} flex items-center justify-center text-xs font-bold shadow-sm">
                                                <i class="fa-solid {{ $slot['icon'] }}"></i>
                                            </span>
                                            <span class="peer-checked:inline-block hidden text-emerald-600 text-xs font-bold">
                                                <i class="fa-solid fa-circle-check"></i>
                                            </span>
                                        </div>
                                        <p class="font-bold text-sm text-gray-900">{{ $slot['label'] }}</p>
                                        <p class="text-[11px] text-gray-500 mt-0.5">{{ $slot['time'] }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('pickup_slot')
                            <p class="text-xs text-rose-500 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Catatan Pesanan --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-800 mb-1.5">
                            Catatan untuk Penjual <span class="text-[10px] text-gray-400 font-normal">(opsional)</span>
                        </label>
                        <textarea name="notes" rows="3" maxlength="255"
                                  placeholder="Contoh: sambalnya dipisah, kuah banyak, tidak pedas..."
                                  class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition resize-none">{{ old('notes', $details['notes'] ?? '') }}</textarea>
                        <p class="text-[10px] text-gray-400 mt-1">Maksimal 255 karakter.</p>
                        @error('notes')
                            <p class="text-xs text-rose-500 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Form Actions --}}
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('cart.index') }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-200 text-gray-700 text-xs font-bold hover:border-emerald-300 transition">
                            <i class="fa-solid fa-arrow-left text-[10px]"></i> Kembali ke Keranjang
                        </a>
                        <button type="submit"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-mint-400 hover:bg-mint-500 text-white font-bold text-xs px-6 py-3 rounded-xl shadow-md shadow-emerald-500/20 transition">
                            Lanjut ke Konfirmasi <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Summary Sidebar (1 col) --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 sticky top-8">
                    <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-basket-shopping text-emerald-500"></i> Rincian Pesanan
                    </h3>

                    <div class="space-y-3 pb-4 border-b border-gray-100 max-h-72 overflow-y-auto">
                        @foreach ($cart['items'] as $item)
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <div class="min-w-0">
                                    <p class="font-bold text-gray-800 truncate">{{ $item['menu']->name }}</p>
                                    <p class="text-[10px] text-gray-400">Rp{{ number_format($item['unit_price'], 0, ',', '.') }} &times; {{ $item['quantity'] }}</p>
                                </div>
                                <span class="font-extrabold text-gray-800 shrink-0">Rp{{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-4 space-y-2 text-xs">
                        <div class="flex justify-between text-gray-500">
                            <span>Total Porsi</span>
                            <span class="font-bold text-gray-800">{{ $cart['count'] }} porsi</span>
                        </div>
                        <div class="flex justify-between items-center text-sm font-extrabold text-gray-900 pt-2 border-t border-gray-100">
                            <span>Total Tagihan</span>
                            <span class="text-mint-600 text-lg">Rp{{ number_format($cart['total'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="mt-5 bg-mint-50 rounded-2xl p-3.5 border border-mint-100 text-[11px] text-gray-600 flex items-start gap-2.5">
                        <i class="fa-solid fa-lightbulb text-sunshine-500 mt-0.5"></i>
                        <span>Kode unik 4 digit akan diberikan setelah konfirmasi final pada langkah berikutnya.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

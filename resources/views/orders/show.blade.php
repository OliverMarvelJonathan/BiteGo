@extends('layouts.app')

@section('title', 'Lacak Pesanan')

@section('content')
    <div class="max-w-3xl mx-auto">
        {{-- Header --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <p class="text-[11px] text-gray-400 font-semibold uppercase tracking-wide">No. Pesanan</p>
                <h2 class="text-lg font-extrabold text-gray-900 font-mono">{{ $order->order_number }}</h2>
                <p class="text-xs text-gray-400 mt-1">
                    <i class="fa-solid fa-store text-emerald-400 mr-1"></i>{{ $order->stall->name }}
                </p>
            </div>
            <div class="flex items-center gap-6">
                <div class="text-center">
                    <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">Kode Ambi</p>
                    <p class="font-mono text-2xl font-extrabold text-mint-600 tracking-[0.2em]">{{ $order->pickup_code }}</p>
                </div>
                <div class="text-center">
                    <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">Jadwal</p>
                    <p class="text-sm font-bold text-gray-800">{{ $order->pickup_slot_label }}</p>
                </div>
            </div>
        </div>

        {{-- Status tracker --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-route text-emerald-500"></i> Lacak Status Pesanan
                </h3>
                @if ($order->statusLogs->isNotEmpty())
                    <span class="text-[10px] text-gray-400 bg-gray-50 border border-gray-100 px-2.5 py-1 rounded-full font-semibold">
                        <i class="fa-solid fa-clock-rotate-left text-emerald-500 mr-1"></i> {{ $order->statusLogs->count() }} pembaruan tercatat
                    </span>
                @else
                    <span class="text-[10px] text-gray-400 bg-gray-50 border border-gray-100 px-2.5 py-1 rounded-full font-semibold">
                        Tercatat dari sistem
                    </span>
                @endif
            </div>

            @php
                $logsByStatus = $order->statusLogs->keyBy('status');
                $hasHistoricalLogs = $order->statusLogs->isNotEmpty();

                $steps = [
                    'menunggu' => ['Menunggu', 'Pesanan masuk dan antre untuk disiapkan.', 'fa-regular fa-clock'],
                    'dimasak' => ['Dimasak', 'Penjual sedang memasak dan mengemas hidangan.', 'fa-solid fa-fire'],
                    'siap_ambil' => ['Siap Ambil', 'Pesanan siap diambil di konter Express Pickup.', 'fa-solid fa-bolt'],
                    'selesai' => ['Selesai', 'Pesanan telah diambil oleh siswa.', 'fa-solid fa-circle-check'],
                ];

                $stepKeys = array_keys($steps);
                $isCancelled = $order->status === 'dibatalkan';
                $currentIndex = array_search($order->status, $stepKeys);
            @endphp

            @if ($isCancelled)
                {{-- Cancelled State Timeline --}}
                <div class="space-y-4 mb-4">
                    <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                            <i class="fa-solid fa-circle-xmark text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="text-sm font-bold text-rose-800">Pesanan Dibatalkan</h4>
                                <span class="text-[10px] font-bold bg-rose-200 text-rose-800 px-2 py-0.5 rounded-full">Final</span>
                            </div>
                            <p class="text-xs text-rose-600 mt-0.5">
                                Pesanan ini telah dibatalkan dan stok menu telah dipulihkan.
                            </p>
                            <p class="text-[11px] text-rose-500 mt-2 font-mono flex items-center gap-1.5">
                                <i class="fa-solid fa-clock"></i>
                                Waktu Pembatalan:
                                @if ($logsByStatus->has('dibatalkan'))
                                    {{ $logsByStatus->get('dibatalkan')->created_at->format('d M Y, H:i') }} WIB
                                @else
                                    {{ $order->updated_at->format('d M Y, H:i') }} WIB
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @else
                {{-- Normal Lifecycle Timeline --}}
                <div class="space-y-0 relative">
                    @foreach ($steps as $key => [$label, $desc, $icon])
                        @php
                            $stepIndex = array_search($key, $stepKeys);
                            $active = $key === $order->status;
                            $completed = $stepIndex < $currentIndex || $order->status === 'selesai';

                            // Timestamp calculation: from log or fallback
                            $stepLog = $logsByStatus->get($key);
                            $timestamp = null;

                            if ($stepLog) {
                                $timestamp = $stepLog->created_at->format('d M Y, H:i') . ' WIB';
                            } elseif (! $hasHistoricalLogs) {
                                if ($key === 'menunggu') {
                                    $timestamp = $order->created_at->format('d M Y, H:i') . ' WIB';
                                } elseif ($active) {
                                    $timestamp = $order->updated_at->format('d M Y, H:i') . ' WIB';
                                }
                            }
                        @endphp
                        <div class="flex items-start gap-4 relative pb-8 last:pb-0">
                            @if (! $loop->last)
                                <div class="absolute left-[19px] top-10 bottom-0 w-0.5 {{ $completed ? 'bg-emerald-400' : 'bg-gray-200' }}"></div>
                            @endif
                            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 relative z-10 text-sm transition-all
                                {{ $active ? 'bg-mint-400 text-white shadow-lg shadow-emerald-500/30 ring-4 ring-mint-100' : ($completed ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-400') }}">
                                @if ($completed && ! $active)
                                    <i class="fa-solid fa-check"></i>
                                @else
                                    <i class="{{ $icon }}"></i>
                                @endif
                            </div>
                            <div class="pt-1.5 flex-1 min-w-0">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                    <p class="font-bold text-sm {{ $active ? 'text-mint-700' : ($completed ? 'text-gray-700' : 'text-gray-400') }}">
                                        {{ $label }}
                                        @if ($active)
                                            <span class="ml-2 inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-sunshine-400 text-gray-900">
                                                <i class="fa-solid fa-circle text-[5px] animate-pulse"></i> Terkini
                                            </span>
                                        @elseif ($completed)
                                            <span class="ml-2 text-[10px] font-semibold text-emerald-600">
                                                <i class="fa-solid fa-circle-check text-[9px]"></i> Selesai
                                            </span>
                                        @endif
                                    </p>
                                    @if ($timestamp)
                                        <span class="text-[10px] font-mono font-medium {{ $active ? 'text-mint-600 font-bold' : 'text-gray-400' }}">
                                            <i class="fa-regular fa-clock text-[9px] mr-1"></i>{{ $timestamp }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-gray-300 italic">Menunggu giliran</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $desc }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (! $hasHistoricalLogs && ! $isCancelled)
                <p class="text-[10px] text-gray-400 mt-4 italic text-right">
                    * Catatan: Pesanan ini dibuat sebelum sistem log riwayat aktif; waktu disesuaikan dari catatan pesanan.
                </p>
            @endif

            @if ($order->status !== 'dibatalkan')
                <div class="mt-4 bg-mint-50 border border-emerald-100 rounded-2xl p-4 flex items-start gap-3">
                    <i class="fa-solid fa-lightbulb text-sunshine-500 mt-0.5"></i>
                    <div>
                        <p class="text-xs font-bold text-gray-700">Tips Express Pickup</p>
                        <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">
                            Tunjukkan kode <span class="font-mono font-bold text-mint-600">{{ $order->pickup_code }}</span> kepada penjual di konter
                            <span class="font-semibold">{{ $order->stall->location }}</span>.
                            Biasanya siap dalam 5–10 menit sebelum istirahat.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Items --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-6">
            <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-basket-shopping text-emerald-500"></i> Rincian Pesanan
            </h3>
            <div class="space-y-3 mb-4">
                @foreach ($order->items as $item)
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0 bg-gray-100">
                            <img src="{{ $item->menu_image }}" alt="{{ $item->menu_name }}" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800">{{ $item->menu_name }}</p>
                            <p class="text-[11px] text-gray-400">Rp{{ number_format($item->price, 0, ',', '.') }} × {{ $item->quantity }}</p>
                        </div>
                        <span class="text-sm font-bold text-gray-800">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
            @if ($order->notes)
                <div class="bg-gray-50 rounded-xl p-3.5 text-xs text-gray-500 mb-4">
                    <span class="font-bold text-gray-600">Catatan:</span> {{ $order->notes }}
                </div>
            @endif
            <div class="flex justify-between items-center border-t border-gray-100 pt-4 font-extrabold text-gray-900">
                <span class="text-sm">Total</span>
                <span class="text-mint-600">{{ $order->total_amount_formatted }}</span>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:border-emerald-300 text-gray-700 font-bold text-sm px-5 py-3 rounded-xl transition">
                <i class="fa-solid fa-arrow-left text-emerald-500"></i> Kembali
            </a>
            @if ($order->status === 'menunggu')
                <form action="{{ route('orders.cancel', $order) }}" method="POST" class="inline-flex" onsubmit="return confirm('Yakin batalkan pesanan ini?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 bg-rose-50 border border-rose-200 text-rose-600 hover:bg-rose-100 font-bold text-sm px-5 py-3 rounded-xl transition">
                        <i class="fa-solid fa-xmark"></i> Batalkan Pesanan
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection
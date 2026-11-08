@extends('layouts.app')

@section('title', 'Dashboard Penjual')

@section('content')
    @if (! $stall)
        <div class="max-w-xl mx-auto py-16 text-center">
            <div class="w-20 h-20 bg-mint-50 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 text-mint-400">
                <i class="fa-solid fa-store"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Belum Ada Stand Kantin</h2>
            <p class="text-sm text-gray-400">Akun penjualmu belum terhubung ke stand kantin.</p>
            <p class="text-[11px] text-gray-400 mt-2">Hubungi admin untuk menghubungkan standmu, atau gunakan akun demo <code class="bg-gray-100 px-1.5 py-0.5 rounded">daniel@bitego.test</code>.</p>
        </div>
    @else
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl overflow-hidden shrink-0 bg-gray-100 shadow-sm">
                    <img src="{{ $stall->image }}" alt="{{ $stall->name }}" class="w-full h-full object-cover">
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $stall->name }}</h2>
                    <p class="text-xs text-gray-400 flex items-center gap-1.5 mt-0.5">
                        <i class="fa-solid fa-location-dot text-emerald-400"></i> {{ $stall->location }}
                        <span class="mx-1">·</span>
                        <i class="fa-solid fa-receipt text-emerald-400"></i> {{ $orders->count() }} pesanan total
                    </p>
                </div>
            </div>
            <a href="{{ route('canteen.stall', $stall) }}" class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:border-emerald-300 text-gray-700 text-xs font-semibold px-4 py-2.5 rounded-xl transition">
                <i class="fa-solid fa-eye text-emerald-500"></i> Lihat Stand
            </a>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 flex items-center gap-1.5">
                    <i class="fa-regular fa-clock text-sunshine-500"></i> Menunggu
                </p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $counts['menunggu'] }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-fire text-amber-400"></i> Dimasak
                </p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $counts['dimasak'] }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-bolt text-emerald-500"></i> Siap Ambil
                </p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $counts['siap_ambil'] }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-check text-mint-500"></i> Selesai
                </p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $counts['selesai'] }}</p>
            </div>
            <div class="bg-gradient-to-br from-mint-700 to-mint-500 rounded-2xl p-4 shadow-md shadow-emerald-500/20 text-white col-span-2 md:col-span-1">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-100 flex items-center gap-1.5">
                    <i class="fa-solid fa-coins"></i> Pendapatan
                </p>
                <p class="text-xl font-extrabold mt-1">Rp{{ number_format($counts['revenue'], 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Filter tabs --}}
        @php
            $tab = request('status', 'aktif');
            $filters = [
                'aktif' => ['label' => 'Aktif', 'icon' => 'fa-bolt'],
                'menunggu' => ['label' => 'Menunggu', 'icon' => 'fa-clock'],
                'dimasak' => ['label' => 'Dimasak', 'icon' => 'fa-fire'],
                'siap_ambil' => ['label' => 'Siap Ambil', 'icon' => 'fa-bell'],
                'selesai' => ['label' => 'Selesai', 'icon' => 'fa-check'],
                'dibatalkan' => ['label' => 'Dibatalkan', 'icon' => 'fa-xmark'],
            ];
            $filtered = $tab === 'aktif'
                ? $orders->whereIn('status', ['menunggu', 'dimasak', 'siap_ambil'])
                : $orders->where('status', $tab);
        @endphp

        <div class="flex flex-wrap gap-2 mb-5">
            @foreach ($filters as $slug => $f)
                <a href="{{ request()->fullUrlWithQuery(['status' => $slug]) }}"
                   class="inline-flex items-center gap-2 text-xs font-bold px-4 py-2 rounded-xl transition {{ $tab === $slug ? 'bg-mint-400 text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-500 hover:border-emerald-300' }}">
                    <i class="fa-solid {{ $f['icon'] }} text-[10px]"></i> {{ $f['label'] }}
                </a>
            @endforeach
        </div>

        {{-- Orders list --}}
        <div class="space-y-3">
            @forelse ($filtered as $order)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 {{ $order->status === 'menunggu' ? 'ring-2 ring-sunshine-400/30' : '' }}">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-4">

                        {{-- Order info --}}
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                            <div class="text-center shrink-0">
                                <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide">Kode</p>
                                <p class="font-mono text-xl font-extrabold text-mint-600 tracking-widest">{{ $order->pickup_code }}</p>
                            </div>
                            <div class="min-w-0 border-l border-gray-100 pl-4">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-bold text-gray-900">{{ $order->user->name }}</p>
                                    <span class="text-[10px] text-gray-400 bg-gray-50 px-2 py-0.5 rounded-full">{{ $order->user->kelas ?? '-' }}</span>
                                </div>
                                <p class="text-[10px] font-mono text-gray-400 mt-0.5">{{ $order->order_number }}</p>
                                <p class="text-[11px] text-gray-500 mt-1">
                                    @foreach ($order->items as $item)
                                        <span class="inline-block">{{ $item->menu_name }} <span class="font-semibold text-gray-700">&times;{{ $item->quantity }}</span>{{ $loop->last ? '' : ',' }}</span>
                                    @endforeach
                                </p>
                                <p class="text-[11px] text-gray-400 mt-0.5 flex items-center gap-1.5">
                                    <i class="fa-solid fa-clock text-emerald-400"></i> {{ $order->pickup_slot_label }}
                                </p>
                                @if ($order->notes)
                                    <div class="mt-2 flex items-start gap-1.5 bg-sunshine-100 border border-sunshine-300 rounded-lg px-2.5 py-1.5">
                                        <i class="fa-solid fa-note-sticky text-sunshine-600 text-[10px] mt-0.5"></i>
                                        <p class="text-[11px] text-gray-700 leading-snug"><span class="font-bold">Catatan:</span> {{ $order->notes }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Summary --}}
                        <div class="flex items-center gap-4 shrink-0 text-sm">
                            <div class="text-right">
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">Total</p>
                                <p class="font-bold text-gray-900">{{ $order->total_amount_formatted }}</p>
                            </div>
                            <span class="px-3 py-1.5 rounded-full text-[10px] font-bold inline-flex items-center gap-1.5
                                {{ match($order->status) {
                                    'menunggu' => 'bg-gray-100 text-gray-600',
                                    'dimasak' => 'bg-amber-100 text-amber-700',
                                    'siap_ambil' => 'bg-emerald-500/15 text-emerald-700',
                                    'selesai' => 'bg-mint-100 text-mint-700',
                                    'dibatalkan' => 'bg-rose-100 text-rose-600',
                                } }}">
                                {{ $order->status_label }}
                            </span>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 lg:flex-col lg:items-end shrink-0">
                            @if (array_key_exists($order->status, \App\Models\Order::STATUS_NEXT))
                                <form action="{{ route('vendor.orders.status', $order) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ \App\Models\Order::STATUS_NEXT[$order->status] }}">
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 bg-mint-400 hover:bg-mint-500 text-white text-[11px] font-bold px-4 py-2 rounded-lg shadow-sm transition whitespace-nowrap">
                                        <i class="fa-solid {{ $order->status === 'menunggu' ? 'fa-fire' : ($order->status === 'dimasak' ? 'fa-bell' : 'fa-check') }} text-[10px]"></i>
                                        → {{ \App\Models\Order::STATUS_LABELS[\App\Models\Order::STATUS_NEXT[$order->status]] }}
                                    </button>
                                </form>
                            @endif
                            @if ($order->canBeCancelled())
                                <form action="{{ route('vendor.orders.status', $order) }}" method="POST" onsubmit="return confirm('Batalkan pesanan ini?')">
                                    @csrf
                                    <input type="hidden" name="status" value="dibatalkan">
                                    <button type="submit" class="text-[11px] text-rose-500 hover:text-rose-600 font-semibold border border-rose-200 hover:border-rose-300 px-3 py-1.5 rounded-lg transition">
                                        Batalkan
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl p-12 text-center border border-dashed border-gray-200">
                    <div class="w-16 h-16 bg-mint-50 rounded-full flex items-center justify-center text-2xl mx-auto mb-3 text-mint-300">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <p class="text-sm text-gray-400">Tidak ada pesanan pada tab ini.</p>
                </div>
            @endforelse
        </div>
    @endif
@endsection
@extends('layouts.app')

@section('title', 'Pesan ' . $menu->name)

@section('content')
    {{-- Breadcrumb --}}
    <nav class="text-xs text-gray-400 mb-5 flex items-center gap-2">
        <a href="{{ route('canteen.index') }}" class="hover:text-emerald-600 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-[8px]"></i>
        <a href="{{ route('canteen.stall', $menu->stall) }}" class="hover:text-emerald-600 transition">{{ $menu->stall->name }}</a>
        <i class="fa-solid fa-chevron-right text-[8px]"></i>
        <span class="text-gray-600 font-semibold">Pesan {{ $menu->name }}</span>
    </nav>

    {{-- Step indicator --}}
    <div class="flex items-center justify-center gap-2 md:gap-4 mb-8">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-mint-400 text-white flex items-center justify-center text-xs font-bold shadow-md">
                <i class="fa-solid fa-check"></i>
            </div>
            <span class="text-xs font-bold text-mint-700">Pilih Stand</span>
        </div>
        <div class="w-8 md:w-14 h-0.5 bg-mint-400"></div>
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-mint-400 text-white flex items-center justify-center text-xs font-bold shadow-md">
                <i class="fa-solid fa-check"></i>
            </div>
            <span class="text-xs font-bold text-mint-700">Pilih Menu</span>
        </div>
        <div class="w-8 md:w-14 h-0.5 bg-sunshine-400"></div>
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-sunshine-400 text-gray-900 flex items-center justify-center text-xs font-bold shadow-md">3</div>
            <span class="text-xs font-bold text-gray-700">Konfirmasi & Kode</span>
        </div>
    </div>

    <div class="max-w-5xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            {{-- Menu summary --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden sticky top-8">
                    <div class="relative h-52">
                        <img src="{{ $menu->image }}" alt="{{ $menu->name }}" class="w-full h-full object-cover">
                        <span class="absolute top-3 left-3 bg-white/90 text-mint-700 text-[10px] font-bold px-3 py-1.5 rounded-full shadow-sm">
                            <i class="fa-solid fa-store mr-1"></i> {{ $menu->stall->name }}
                        </span>
                    </div>
                    <div class="p-6">
                        <span class="inline-block bg-gray-100 text-gray-500 text-[10px] font-bold px-2.5 py-1 rounded-full mb-2">{{ $menu->category->name ?? 'Menu' }}</span>
                        <h2 class="text-xl font-bold text-gray-900">{{ $menu->name }}</h2>
                        <p class="text-sm text-gray-400 leading-relaxed mt-2">{{ $menu->description }}</p>
                    </div>
                </div>
            </div>

            {{-- Order form --}}
            <div class="lg:col-span-3">
                <form method="POST" action="{{ route('orders.store', $menu) }}" class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-8">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

                    <h3 class="text-sm font-bold text-gray-900 mb-5 flex items-center gap-2">
                        <i class="fa-solid fa-clipboard-list text-emerald-500"></i> Detail Pesanan
                    </h3>

                    {{-- Quantity stepper --}}
                    <div class="mb-6">
                        <label class="block text-xs font-semibold text-gray-600 mb-2">Jumlah Porsi</label>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                                <button type="button" onclick="step(-1)" class="w-11 h-11 flex items-center justify-center text-gray-500 hover:bg-gray-50 transition">
                                    <i class="fa-solid fa-minus text-xs"></i>
                                </button>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" max="10"
                                       class="w-16 h-11 text-center text-sm font-bold focus:outline-none border-x border-gray-200" oninput="updateSummary()">
                                <button type="button" onclick="step(1)" class="w-11 h-11 flex items-center justify-center text-gray-500 hover:bg-gray-50 transition">
                                    <i class="fa-solid fa-plus text-xs"></i>
                                </button>
                            </div>
                            <span class="text-xs text-gray-400" id="stock-hint">Stok tersedia: {{ $menu->stock }}</span>
                        </div>
                    </div>

                    {{-- Pickup slot --}}
                    <div class="mb-6">
                        <label class="block text-xs font-semibold text-gray-600 mb-2">Jadwal Pengambilan</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="cursor-pointer">
                                <input type="radio" name="pickup_slot" value="istirahat_1" checked class="peer sr-only">
                                <div class="border-2 border-gray-200 rounded-2xl p-4 peer-checked:border-emerald-400 peer-checked:bg-emerald-50/50 hover:border-emerald-300 transition">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="w-8 h-8 rounded-full bg-sunshine-400 text-gray-900 flex items-center justify-center text-xs font-bold">
                                            <i class="fa-solid fa-sun"></i>
                                        </span>
                                    </div>
                                    <p class="font-bold text-sm text-gray-900">Istirahat I</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Pagi · 09.30 - 10.00</p>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="pickup_slot" value="istirahat_2" class="peer sr-only">
                                <div class="border-2 border-gray-200 rounded-2xl p-4 peer-checked:border-emerald-400 peer-checked:bg-emerald-50/50 hover:border-emerald-300 transition">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="w-8 h-8 rounded-full bg-amber-400 text-white flex items-center justify-center text-xs font-bold">
                                            <i class="fa-solid fa-cloud-sun"></i>
                                        </span>
                                    </div>
                                    <p class="font-bold text-sm text-gray-900">Istirahat II</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Siang · 12.00 - 12.30</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="mb-6">
                        <label class="block text-xs font-semibold text-gray-600 mb-2">Catatan (opsional)</label>
                        <textarea name="notes" rows="3" placeholder="Contoh: sambalnya pisah, tidak pedas, ekstra sambal..."
                                  class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition resize-none"></textarea>
                    </div>

                    {{-- Summary --}}
                    <div class="bg-mint-50 rounded-2xl p-5 mb-6">
                        <div class="flex justify-between items-center text-sm mb-2">
                            <span class="text-gray-500">{{ $menu->name }}</span>
                            <span class="font-semibold text-gray-700">Rp{{ number_format($menu->price, 0, ',', '.') }} × <span id="qty-label">1</span></span>
                        </div>
                        <div class="flex justify-between items-center border-t border-gray-200 pt-3 mt-3">
                            <span class="font-bold text-gray-900 text-sm">Total Bayar</span>
                            <span class="font-extrabold text-mint-600 text-lg" id="total-label">Rp{{ number_format($menu->price, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full bg-mint-400 hover:bg-mint-500 text-white font-bold text-sm py-3.5 rounded-xl shadow-md shadow-emerald-500/20 transition">
                        Konfirmasi Pesanan <i class="fa-solid fa-arrow-right ml-1"></i>
                    </button>

                    <p class="text-center text-[11px] text-gray-400 mt-4 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                        Kode unik 4 digit diberikan setelah konfirmasi.
                    </p>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const price = {{ (int) $menu->price }};
    const maxStock = {{ (int) $menu->stock }};

    function step(dir) {
        const input = document.getElementById('quantity');
        let val = parseInt(input.value || '1') + dir;
        if (val < 1) val = 1;
        if (val > Math.min(10, maxStock)) val = Math.min(10, maxStock);
        input.value = val;
        updateSummary();
    }

    function updateSummary() {
        const qty = Math.max(1, parseInt(document.getElementById('quantity').value || '1'));
        const total = qty * price;
        document.getElementById('qty-label').textContent = qty;
        document.getElementById('total-label').textContent = 'Rp' + total.toLocaleString('id-ID');
    }
</script>
@endpush
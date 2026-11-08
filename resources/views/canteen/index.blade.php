@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Halo, @auth {{ explode(' ', auth()->user()->name)[0] }} @else BiteGo Lovers! @endauth 👋</h2>
            <p class="text-sm text-gray-500 mt-1">Pesan dari kelas, ambil saat istirahat. Tanpa antre, tanpa nunggu lama.</p>
        </div>
        @auth
            @if (! auth()->user()->isVendor())
                <a href="{{ route('orders.index') }}"
                   class="inline-flex items-center justify-center gap-2 bg-white border border-gray-100 shadow-sm text-gray-700 text-xs font-semibold px-4 py-2.5 rounded-xl hover:border-emerald-300 transition">
                    <i class="fa-solid fa-receipt text-emerald-500"></i> Lacak Pesanan
                </a>
            @else
                <a href="{{ route('vendor.dashboard') }}"
                   class="inline-flex items-center justify-center gap-2 bg-white border border-gray-100 shadow-sm text-gray-700 text-xs font-semibold px-4 py-2.5 rounded-xl hover:border-emerald-300 transition">
                    <i class="fa-solid fa-chart-simple text-emerald-500"></i> Buka Dashboard
                </a>
            @endif
        @endauth
    </div>

    {{-- Hero --}}
    <div class="relative overflow-hidden bg-mint-50 rounded-3xl px-7 py-8 lg:px-10 lg:py-10 mb-10">
        <div class="relative z-10 max-w-2xl">
            <span class="inline-flex items-center gap-2 bg-white/80 text-[#064E3B] text-xs font-semibold px-3 py-1.5 rounded-full mb-5">
                <i class="fa-solid fa-bolt text-[#FBBF24]"></i> Smart Canteen
            </span>
            <h2 class="text-3xl lg:text-4xl font-bold text-[#064E3B] leading-tight mb-4">
                Pesan Sesukamu,<br>
                Ambil Sendiri.
            </h2>
            <p class="text-gray-600 max-w-lg leading-relaxed mb-6">
                Pilih makanan, pesan, dan ambil di outlet pilihanmu. Tanpa antre tanpa tunggu lama.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('canteen.menu') }}" class="inline-flex items-center gap-2 bg-[#10B981] hover:bg-emerald-600 text-white font-semibold px-5 py-3 rounded-xl transition shadow-sm">
                    LIHAT MENU <i class="fa-solid fa-arrow-right text-sm"></i>
                </a>
                <a href="#stand-kantin" class="inline-flex items-center gap-2 bg-white text-[#064E3B] font-semibold px-5 py-3 rounded-xl border border-emerald-200 hover:bg-emerald-50 transition">
                    <i class="fa-solid fa-store"></i> Pilih Stand
                </a>
            </div>
        </div>

        {{-- Hero Image --}}
        <div class="hidden md:block absolute right-8 lg:right-14 top-1/2 -translate-y-1/2">
            <div class="w-48 h-48 lg:w-56 lg:h-56 rounded-full overflow-hidden border-8 border-white/70 shadow-lg">
                <img src="https://images.unsplash.com/photo-1547592180-85f173990554?w=700&auto=format&fit=crop" alt="Makanan" class="w-full h-full object-cover">
            </div>
        </div>

        {{-- Decorative circles --}}
        <div class="absolute -right-10 -bottom-16 w-44 h-44 rounded-full bg-[#10B981]/10"></div>
        <div class="absolute right-32 -top-20 w-36 h-36 rounded-full bg-[#FBBF24]/10"></div>
    </div>

    {{-- Three-step ordering --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-mint-400 text-white flex items-center justify-center font-bold text-sm shadow-md">1</div>
            <div>
                <h4 class="text-sm font-bold text-gray-900">Pilih Stand & Menu</h4>
                <p class="text-xs text-gray-400 leading-relaxed mt-1">Telusuri katalog stand kantin dan pilih menu favoritmu.</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-sunshine-400 text-gray-900 flex items-center justify-center font-bold text-sm shadow-md">2</div>
            <div>
                <h4 class="text-sm font-bold text-gray-900">Tentukan Jam Ambil</h4>
                <p class="text-xs text-gray-400 leading-relaxed mt-1">Pilih Istirahat I atau Istirahat II sesuai jadwalmu.</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-rose-400 text-white flex items-center justify-center font-bold text-sm shadow-md">3</div>
            <div>
                <h4 class="text-sm font-bold text-gray-900">Ambil dengan Kode</h4>
                <p class="text-xs text-gray-400 leading-relaxed mt-1">Tunjukkan kode 4 digit di Express Pickup. Selesai!</p>
            </div>
        </div>
    </div>

    {{-- Kategori --}}
    <div class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-bold text-gray-800"><i class="fa-solid fa-tags text-emerald-500 mr-2"></i>Kategori</h3>
            <a href="{{ route('canteen.menu') }}" class="text-xs font-semibold text-emerald-600 hover:underline flex items-center gap-1">
                Lihat Semua <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach ($categories as $category)
                <a href="{{ route('canteen.menu', ['category' => $category->slug]) }}"
                   class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between hover:border-emerald-300 hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl text-white flex items-center justify-center text-sm
                            {{ $loop->index % 4 === 0 ? 'bg-gray-900' : ($loop->index % 4 === 1 ? 'bg-mint-400' : ($loop->index % 4 === 2 ? 'bg-sunshine-400 text-gray-900' : 'bg-rose-400')) }}">
                            <i class="fa-solid {{ $category->icon }}"></i>
                        </div>
                        <span class="text-xs font-bold text-gray-800">{{ $category->name }}</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Stand Kantin --}}
    <div id="stand-kantin" class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-bold text-gray-800"><i class="fa-solid fa-store text-emerald-500 mr-2"></i>Stand Kantin</h3>
            <a href="{{ route('canteen.menu') }}" class="text-xs font-semibold text-emerald-600 hover:underline flex items-center gap-1">
                Lihat Menu <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($stalls as $stall)
                <a href="{{ route('canteen.stall', $stall) }}" class="group bg-white rounded-2xl p-4 shadow-sm border border-gray-100 hover:border-emerald-300 hover:shadow-md transition">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl overflow-hidden shrink-0 bg-gray-100">
                            <img src="{{ $stall->image }}" alt="{{ $stall->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-sm text-gray-900 truncate">{{ $stall->name }}</h4>
                            <p class="text-[11px] text-gray-400 flex items-center gap-1 mt-0.5">
                                <i class="fa-solid fa-location-dot text-emerald-400"></i> {{ $stall->location }}
                            </p>
                            <span class="inline-flex items-center gap-1.5 text-[10px] mt-1.5 {{ $stall->menus_count > 0 ? 'text-emerald-600' : 'text-gray-400' }}">
                                <i class="fa-solid fa-utensils"></i> {{ $stall->menus_count }} menu tersedia
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-50">
                        <span class="text-[11px] text-gray-400 font-medium">Buka</span>
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-mint-500 group-hover:gap-2 transition-all">
                            Kunjungi <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Menu Terlaris --}}
    <div id="menu-terlaris">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-bold text-gray-800"><i class="fa-solid fa-fire-flame-curved text-rose-400 mr-2"></i>Menu Terlaris</h3>
            <a href="{{ route('canteen.menu') }}" class="text-xs font-semibold text-emerald-600 hover:underline flex items-center gap-1">
                Lihat Semua <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
            @forelse ($popularMenus as $menu)
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between hover:shadow-md transition">
                    <div>
                        <div class="relative w-full h-36 rounded-2xl overflow-hidden mb-3 bg-gray-100">
                            <img src="{{ $menu->image }}" alt="{{ $menu->name }}" class="w-full h-full object-cover">
                            @if ($menu->stock > 0)
                                <span class="absolute top-2 left-2 bg-white/90 text-emerald-700 text-[9px] font-bold px-2 py-1 rounded-full">Stok {{ $menu->stock }}</span>
                            @endif
                        </div>
                        <h4 class="font-bold text-sm text-gray-900 mb-1">{{ $menu->name }}</h4>
                        <p class="text-[11px] text-gray-400 leading-relaxed line-clamp-2 mb-1">{{ $menu->description }}</p>
                        <p class="text-[10px] text-emerald-600 font-semibold flex items-center gap-1 mb-3">
                            <i class="fa-solid fa-store"></i> {{ $menu->stall->name ?? '-' }}
                        </p>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-gray-50">
                        <span class="font-bold text-emerald-600 text-sm">Rp{{ number_format($menu->price, 0, ',', '.') }}</span>
                        @auth
                            @if (! auth()->user()->isVendor() && $menu->stock > 0)
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
                                       class="px-3 py-1 bg-mint-400 hover:bg-mint-500 text-white text-xs font-semibold rounded-full shadow-sm transition">
                                        Pesan
                                    </a>
                                </div>
                            @elseif ($menu->stock > 0)
                                <a href="{{ route('canteen.menu') }}" class="px-4 py-1.5 border border-emerald-400 text-emerald-600 text-xs font-semibold rounded-full hover:bg-emerald-600 hover:text-white transition">
                                    Lihat
                                </a>
                            @else
                                <span class="px-4 py-1.5 bg-gray-100 text-gray-400 text-xs font-semibold rounded-full">Habis</span>
                            @endif
                        @else
                            <a href="{{ route('auth.login', ['redirect' => route('orders.create', $menu)]) }}"
                               class="px-4 py-1.5 bg-mint-400 hover:bg-mint-500 text-white text-xs font-semibold rounded-full shadow-sm transition">
                                Pesan
                            </a>
                        @endauth
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl p-8 text-center text-gray-400 text-sm border border-dashed border-gray-200">
                    Belum ada menu terlaris.
                </div>
            @endforelse
        </div>
    </div>

    {{-- CTA --}}
    <div class="mt-10 bg-gradient-to-r from-mint-700 to-mint-500 rounded-3xl p-8 text-center text-white shadow-lg shadow-emerald-500/20">
        <h3 class="text-xl font-extrabold mb-2">Siap Bebas Antre? 🍽️</h3>
        <p class="text-sm text-emerald-100 mb-5 max-w-md mx-auto">Pesan sekarang langsung dari kelasmu, ambil saat istirahat melalui konter Express Pickup.</p>
        <a href="{{ route('canteen.menu') }}" class="inline-flex items-center gap-2 bg-white text-mint-700 font-bold text-xs uppercase tracking-wider px-6 py-3 rounded-full shadow-md hover:bg-emerald-50 transition">
            Mulai Pesan <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
@endsection
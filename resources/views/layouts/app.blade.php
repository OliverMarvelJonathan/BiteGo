<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BiteGo - Smart Canteen') · BiteGo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        document.addEventListener('error', function (e) {
            var img = e.target;
            if (img && img.tagName === 'IMG') {
                img.addEventListener('error', function () { this.style.display = 'none'; });
                img.src = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='800' height='600'><rect width='100%25' height='100%25' fill='#D1EDE0'/><text x='50%25' y='54%25' font-family='sans-serif' font-size='130' text-anchor='middle' dominant-baseline='middle'>🍱</text></svg>";
            }
        }, true);
    </script>
    @stack('styles')
</head>
<body class="font-sans antialiased text-gray-800">

    <div class="flex min-h-screen">

        {{-- Sidebar --}}
        <aside id="sidebar" class="fixed lg:static z-40 w-64 lg:w-60 bg-mint-900 text-white flex flex-col justify-between p-6 shrink-0 transition-transform -translate-x-full lg:translate-x-0">
            <div>
                {{-- Brand --}}
                <div class="flex items-center gap-3 mb-10">
                    <div class="w-10 h-10 bg-mint-400 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-md">
                        <i class="fa-solid fa-utensils"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight">BiteGo</h1>
                        <p class="text-[10px] text-emerald-200 uppercase tracking-widest font-medium">Smart Canteen</p>
                    </div>
                </div>

                {{-- Navigation --}}
                <nav class="space-y-3">
                    <a href="{{ route('canteen.index') }}"
                       class="flex items-center gap-4 px-4 py-3 rounded-xl transition {{ request()->routeIs('canteen.index', 'canteen.index.alt') ? 'bg-mint-700 text-white font-medium shadow-sm' : 'text-emerald-100 hover:bg-mint-900' }}">
                        <i class="fa-solid fa-house text-lg"></i>
                        <span>Home</span>
                    </a>
                    <a href="{{ route('canteen.menu') }}"
                       class="flex items-center gap-4 px-4 py-3 rounded-xl transition {{ request()->routeIs('canteen.menu') ? 'bg-mint-700 text-white font-medium shadow-sm' : 'text-emerald-100 hover:bg-mint-900' }}">
                        <i class="fa-solid fa-utensils text-lg"></i>
                        <span>Menu</span>
                    </a>
                    <a href="{{ route('canteen.index') }}#stand-kantin"
                       class="flex items-center gap-4 px-4 py-3 rounded-xl transition {{ request()->routeIs('canteen.stall') ? 'bg-mint-700 text-white font-medium shadow-sm' : 'text-emerald-100 hover:bg-mint-900' }}">
                        <i class="fa-solid fa-store text-lg"></i>
                        <span>Stand Kantin</span>
                    </a>

                    @auth
                        @if (auth()->user()->isVendor())
                            <a href="{{ route('vendor.dashboard') }}"
                               class="flex items-center gap-4 px-4 py-3 rounded-xl transition {{ request()->routeIs('vendor.dashboard') ? 'bg-mint-700 text-white font-medium shadow-sm' : 'text-emerald-100 hover:bg-mint-900' }}">
                                <i class="fa-solid fa-chart-simple text-lg"></i>
                                <span>Dashboard Penjual</span>
                            </a>
                            <a href="{{ route('vendor.menu.index') }}"
                               class="flex items-center gap-4 px-4 py-3 rounded-xl transition {{ request()->routeIs('vendor.menu.*') ? 'bg-mint-700 text-white font-medium shadow-sm' : 'text-emerald-100 hover:bg-mint-900' }}">
                                <i class="fa-solid fa-bowl-food text-lg"></i>
                                <span>Menu & Stok</span>
                            </a>
                        @else
                            @php
                                $cartRaw = session('bitego_cart.items', []);
                                $cartCount = is_array($cartRaw) ? array_sum($cartRaw) : 0;
                            @endphp
                            <a href="{{ route('cart.index') }}"
                               class="flex items-center gap-4 px-4 py-3 rounded-xl transition {{ request()->routeIs('cart.*', 'checkout.*') ? 'bg-mint-700 text-white font-medium shadow-sm' : 'text-emerald-100 hover:bg-mint-900' }}">
                                <i class="fa-solid fa-cart-shopping text-lg"></i>
                                <span>Keranjang</span>
                                @if ($cartCount > 0)
                                    <span class="ml-auto bg-sunshine-400 text-gray-900 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                        {{ $cartCount }}
                                    </span>
                                @endif
                            </a>
                            <a href="{{ route('orders.index') }}"
                               class="flex items-center gap-4 px-4 py-3 rounded-xl transition {{ request()->routeIs('orders.*') ? 'bg-mint-700 text-white font-medium shadow-sm' : 'text-emerald-100 hover:bg-mint-900' }}">
                                <i class="fa-solid fa-receipt text-lg"></i>
                                <span>Pesanan Saya</span>
                                @if (auth()->user()->orders()->whereIn('status', ['menunggu', 'dimasak', 'siap_ambil'])->count() > 0)
                                    <span class="ml-auto bg-sunshine-400 text-gray-900 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                        {{ auth()->user()->orders()->whereIn('status', ['menunggu', 'dimasak', 'siap_ambil'])->count() }}
                                    </span>
                                @endif
                            </a>
                        @endif
                    @endauth
                </nav>
            </div>

            {{-- Auth footer / user card --}}
            <div class="mt-8">
                @auth
                    <div class="bg-mint-800 rounded-2xl p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-sunshine-400 text-gray-900 flex items-center justify-center font-bold text-sm">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[10px] text-emerald-200">
                                    {{ auth()->user()->isVendor() ? 'Penjual & Pengelola' : 'Siswa · ' . (auth()->user()->kelas ?? '-') }}
                                </p>
                            </div>
                        </div>
                        <form action="{{ route('auth.logout') }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="w-full flex items-center justify-center gap-2 text-[11px] font-semibold text-emerald-100 hover:bg-mint-900 rounded-lg py-2 transition">
                                <i class="fa-solid fa-right-from-bracket"></i> Keluar
                            </button>
                        </form>
                    </div>
                @else
                    <div class="space-y-2">
                        <a href="{{ route('auth.login') }}" class="block text-center bg-mint-400 hover:bg-mint-500 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-md">
                            Masuk untuk Memesan
                        </a>
                        <a href="{{ route('auth.register') }}" class="block text-center text-emerald-100 hover:bg-mint-900 text-xs font-semibold py-2 rounded-xl transition">
                            Buat Akun Baru
                        </a>
                    </div>
                @endauth
            </div>
        </aside>

        {{-- Mobile overlay --}}
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-30 hidden lg:hidden" onclick="toggleSidebar(false)"></div>

        {{-- Main Content --}}
        <main class="flex-1 p-4 lg:p-8 overflow-x-hidden min-w-0">

            {{-- Mobile top bar --}}
            <div class="lg:hidden flex items-center justify-between mb-4">
                <button onclick="toggleSidebar(true)" class="p-2.5 bg-white rounded-xl shadow-sm border border-gray-100">
                    <i class="fa-solid fa-bars text-gray-700"></i>
                </button>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-mint-400 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                        <i class="fa-solid fa-utensils text-xs"></i>
                    </div>
                    <span class="font-bold text-gray-900">BiteGo</span>
                </div>
                @auth
                    @if (! auth()->user()->isVendor())
                        @php
                            $cartRaw = session('bitego_cart.items', []);
                            $mobileCartCount = is_array($cartRaw) ? array_sum($cartRaw) : 0;
                        @endphp
                        <a href="{{ route('cart.index') }}" class="relative p-2.5 bg-white rounded-xl shadow-sm border border-gray-100 text-gray-700">
                            <i class="fa-solid fa-cart-shopping"></i>
                            @if ($mobileCartCount > 0)
                                <span class="absolute -top-1 -right-1 bg-sunshine-400 text-gray-900 text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                                    {{ $mobileCartCount }}
                                </span>
                            @endif
                        </a>
                    @else
                        <div class="w-9"></div>
                    @endif
                @else
                    <div class="w-9"></div>
                @endauth
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-5 flex items-center justify-between bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 rounded-2xl px-5 py-4 text-sm">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-500"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button onclick="this.closest('div').remove()" class="text-emerald-700/60 hover:text-emerald-700">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-5 flex items-center justify-between bg-rose-500/10 border border-rose-500/30 text-rose-700 rounded-2xl px-5 py-4 text-sm">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button onclick="this.closest('div').remove()" class="text-rose-700/60 hover:text-rose-700">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-5 bg-rose-500/10 border border-rose-500/30 text-rose-700 rounded-2xl px-5 py-4 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                            <span>{{ $error }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        function toggleSidebar(show) {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (show) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
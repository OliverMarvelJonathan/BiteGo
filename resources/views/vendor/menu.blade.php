@extends('layouts.app')

@section('title', 'Kelola Menu & Stok')

@section('content')
    @if (! $stall)
        <div class="max-w-xl mx-auto py-16 text-center">
            <div class="w-20 h-20 bg-mint-50 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 text-mint-400">
                <i class="fa-solid fa-bowl-food"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Belum Ada Stand Kantin</h2>
            <p class="text-sm text-gray-400">Akun penjualmu belum terhubung ke stand kantin sehingga belum bisa mengelola menu.</p>
            <p class="text-[11px] text-gray-400 mt-2">Gunakan akun demo <code class="bg-gray-100 px-1.5 py-0.5 rounded">daniel@bitego.test</code> untuk mencoba.</p>
        </div>
    @else
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div>
                <a href="{{ route('vendor.dashboard') }}" class="text-xs text-gray-400 hover:text-mint-600 font-semibold mb-1 inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Dashboard
                </a>
                <div class="flex items-center gap-4 mt-1">
                    <div class="w-12 h-12 rounded-2xl overflow-hidden shrink-0 bg-gray-100 shadow-sm">
                        <img src="{{ $stall->image }}" alt="{{ $stall->name }}" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Kelola Menu & Stok</h2>
                        <p class="text-xs text-gray-400 mt-0.5 flex items-center gap-1.5">
                            <i class="fa-solid fa-store text-emerald-400"></i> {{ $stall->name }}
                        </p>
                    </div>
                </div>
            </div>
            <button onclick="document.getElementById('new-menu-form').classList.toggle('hidden')"
                    class="inline-flex items-center gap-2 bg-mint-400 hover:bg-mint-500 text-white text-xs font-bold px-5 py-3 rounded-xl shadow-md shadow-emerald-500/20 transition">
                <i class="fa-solid fa-plus"></i> Tambah Menu
            </button>
        </div>

        {{-- Stat mini cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-bowl-food text-mint-400"></i> Total Menu
                </p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-boxes-stacked text-mint-400"></i> Total Stok
                </p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ number_format($stats['stock']) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-check text-emerald-500"></i> Tersedia
                </p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $stats['available'] }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-star text-sunshine-500"></i> Terlaris
                </p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $stats['popular'] }}</p>
            </div>
        </div>

        {{-- Tambah menu form --}}
        <div id="new-menu-form" class="hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 bg-mint-50 rounded-lg flex items-center justify-center text-mint-500">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Tambah Menu Baru</h3>
                    <p class="text-[11px] text-gray-400">Menu akan langsung tampil di halaman kantin stand Anda.</p>
                </div>
            </div>
            <form action="{{ route('vendor.menu.store') }}" method="POST" class="grid md:grid-cols-2 gap-4">
                @csrf
                <div class="md:col-span-2 grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1.5">Nama Menu <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-mint-400 focus:ring-2 focus:ring-mint-400/20 outline-none" placeholder="Nasi Goreng Spesial">
                        @error('name')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1.5">Kategori</label>
                        <select name="category_id" required
                                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-mint-400 focus:ring-2 focus:ring-mint-400/20 outline-none bg-white">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1.5">Harga (Rp) <span class="text-rose-500">*</span></label>
                        <input type="number" name="price" value="{{ old('price') }}" min="100" step="100" required
                               class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-mint-400 focus:ring-2 focus:ring-mint-400/20 outline-none" placeholder="15000">
                        @error('price')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1.5">Stok Awal <span class="text-rose-500">*</span></label>
                        <input type="number" name="stock" value="{{ old('stock') }}" min="0" required
                               class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-mint-400 focus:ring-2 focus:ring-mint-400/20 outline-none" placeholder="50">
                        @error('stock')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1.5">Deskripsi Singkat</label>
                        <input type="text" name="description" value="{{ old('description') }}" maxlength="255"
                               class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-mint-400 focus:ring-2 focus:ring-mint-400/20 outline-none" placeholder="Nasi goreng dengan telur mata sapi dan ayam suwir">
                        @error('description')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1.5">URL Gambar (opsional)</label>
                        <input type="url" name="image" value="{{ old('image') }}"
                               class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-mint-400 focus:ring-2 focus:ring-mint-400/20 outline-none" placeholder="https://...foto-menu.jpg">
                        <p class="text-[10px] text-gray-400 mt-1">Kosongkan jika belum punya gambar — otomatis memakai placeholder.</p>
                        @error('image')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="md:col-span-2 flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 bg-mint-400 hover:bg-mint-500 text-white text-xs font-bold px-6 py-3 rounded-xl shadow-sm transition">
                        <i class="fa-solid fa-check"></i> Simpan Menu
                    </button>
                    <button type="button" onclick="document.getElementById('new-menu-form').classList.add('hidden')"
                            class="text-xs font-semibold text-gray-400 hover:text-gray-600 px-4 py-3 transition">
                        Batal
                    </button>
                </div>
            </form>
        </div>

        {{-- Daftar menu per kategori --}}
        @php
            $grouped = $menus->groupBy('category.name');
        @endphp

        @forelse ($grouped as $categoryName => $categoryMenus)
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-3">
                    <h3 class="text-sm font-bold text-gray-900">{{ $categoryName }}</h3>
                    <span class="text-[10px] font-bold bg-mint-100 text-mint-700 px-2 py-0.5 rounded-full">{{ $categoryMenus->count() }} menu</span>
                </div>

                <div class="space-y-3">
                    @foreach ($categoryMenus as $menu)
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                            <form action="{{ route('vendor.menu.update', $menu) }}" method="POST" class="flex flex-col md:flex-row md:items-center gap-4">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="stock_baseline" value="{{ $menu->stock }}">

                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0 bg-mint-50 flex items-center justify-center">
                                        @if ($menu->image)
                                            <img src="{{ $menu->image }}" alt="{{ $menu->name }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-lg">🍱</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-gray-900 truncate flex items-center gap-2">
                                            {{ $menu->name }}
                                            @if ($menu->is_popular)
                                                <span class="text-[9px] font-bold bg-sunshine-300/60 text-yellow-700 px-1.5 py-0.5 rounded-full inline-flex items-center gap-1"><i class="fa-solid fa-star text-[8px]"></i> Terlaris</span>
                                            @endif
                                        </p>
                                        <p class="text-[11px] text-gray-400 truncate">{{ $menu->description ?? 'Tanpa deskripsi' }}</p>
                                        <p class="text-[11px] text-emerald-600 font-bold mt-0.5">Rp{{ number_format($menu->price, 0, ',', '.') }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 md:gap-3 shrink-0 flex-wrap">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Harga (Rp)</label>
                                        <input type="number" name="price" value="{{ $menu->price }}" min="100" step="100" required
                                               class="w-28 rounded-lg border border-gray-200 px-2.5 py-2 text-sm text-right font-semibold focus:border-mint-400 focus:ring-2 focus:ring-mint-400/20 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Stok</label>
                                        <input type="number" name="stock" value="{{ $menu->stock }}" min="0" required
                                               class="w-20 rounded-lg border border-gray-200 px-2.5 py-2 text-sm text-right font-semibold focus:border-mint-400 focus:ring-2 focus:ring-mint-400/20 outline-none {{ $menu->stock <= 5 ? 'text-rose-600' : '' }}">
                                    </div>
                                </div>

                                <div class="flex gap-4 shrink-0">
                                    <label class="flex items-center gap-1.5 cursor-pointer text-[11px] font-semibold text-gray-500">
                                        <input type="checkbox" name="is_available" value="1" {{ $menu->is_available ? 'checked' : '' }} class="accent-mint-500 w-4 h-4 rounded">
                                        Tersedia
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer text-[11px] font-semibold text-gray-500">
                                        <input type="checkbox" name="is_popular" value="1" {{ $menu->is_popular ? 'checked' : '' }} class="accent-sunshine-500 w-4 h-4 rounded">
                                        Terlaris
                                    </label>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="submit" class="inline-flex items-center gap-1.5 bg-mint-400 hover:bg-mint-500 text-white text-[11px] font-bold px-4 py-2.5 rounded-lg shadow-sm transition">
                                        <i class="fa-solid fa-floppy-disk text-[10px]"></i> Simpan
                                    </button>
                                    <button type="button"
                                            data-delete-form="delete-menu-{{ $menu->id }}"
                                            data-menu-name="{{ $menu->name }}"
                                            onclick="if(confirm('Hapus menu ' + this.dataset.menuName + '?')){document.getElementById(this.dataset.deleteForm).submit()}"
                                            class="inline-flex items-center gap-1.5 text-rose-500 hover:bg-rose-50 text-[11px] font-semibold px-3 py-2.5 rounded-lg border border-rose-200 hover:border-rose-300 transition">
                                        <i class="fa-solid fa-trash text-[10px]"></i>
                                    </button>
                                </div>
                            </form>
                            <form id="delete-menu-{{ $menu->id }}" action="{{ route('vendor.menu.destroy', $menu) }}" method="POST" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-12 text-center border border-dashed border-gray-200">
                <div class="w-16 h-16 bg-mint-50 rounded-full flex items-center justify-center text-2xl mx-auto mb-3 text-mint-300">
                    <i class="fa-solid fa-bowl-food"></i>
                </div>
                <p class="text-sm text-gray-400">Belum ada menu di stand Anda.</p>
                <button onclick="document.getElementById('new-menu-form').classList.remove('hidden')"
                        class="mt-4 inline-flex items-center gap-2 bg-mint-400 hover:bg-mint-500 text-white text-xs font-bold px-5 py-3 rounded-xl shadow-sm transition">
                    <i class="fa-solid fa-plus"></i> Tambah Menu Pertama
                </button>
            </div>
        @endforelse
    @endif
@endsection
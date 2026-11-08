<?php

use App\Models\Category;
use App\Models\Menu;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->catMakanan = Category::where('slug', 'makanan-utama')->first() ?? Category::create([
        'name' => 'Makanan Utama',
        'slug' => 'makanan-utama',
        'icon' => 'fa-bowl-food',
    ]);

    $this->vendor = User::where('role', 'vendor')->first();
    $this->stall = $this->vendor->stall;

    $this->menu = $this->stall->menus()->create([
        'name' => 'Ayam Bakar Test',
        'slug' => 'ayam-bakar-test-'.Str::lower(Str::random(4)),
        'description' => 'Ayam bakar bumbu kecap',
        'category_id' => $this->catMakanan->id,
        'price' => 18000,
        'stock' => 30,
        'is_available' => true,
        'is_popular' => false,
    ]);

    $otherVendor = User::where('role', 'vendor')->orderBy('id')->skip(1)->first();
    $this->otherMenu = $otherVendor->stall->menus()->create([
        'name' => 'Menu Vendor Lain',
        'slug' => 'menu-vendor-lain-'.Str::lower(Str::random(4)),
        'category_id' => $this->catMakanan->id,
        'price' => 5000,
        'stock' => 5,
    ]);
});

it('menampilkan halaman kelola menu beserta statistik stok', function () {
    $this->actingAs($this->vendor)
        ->get(route('vendor.menu.index'))
        ->assertOk()
        ->assertSee('Kelola Menu & Stok')
        ->assertSee($this->menu->name);
});

it('hanya menampilkan menu milik stand penjual itu sendiri', function () {
    $response = $this->actingAs($this->vendor)
        ->get(route('vendor.menu.index'));

    expect($response->getContent())
        ->toContain($this->menu->name)
        ->not->toContain($this->otherMenu->name);
});

it('penjual dapat menambah menu baru ke standnya', function () {
    $response = $this->actingAs($this->vendor)
        ->post(route('vendor.menu.store'), [
            'name' => 'Nasi Uduk Komplit',
            'description' => 'Nasi uduk gurih dengan telur balado dan ayam goreng',
            'category_id' => $this->catMakanan->id,
            'price' => 16000,
            'stock' => 30,
            'image' => 'https://example.com/nasi-uduk.jpg',
        ]);

    $response->assertRedirect(route('vendor.menu.index'));

    $created = Menu::where('name', 'Nasi Uduk Komplit')->where('stall_id', $this->stall->id)->first();

    expect($created)->not->toBeNull()
        ->and($created->price)->toBe(16000)
        ->and($created->stock)->toBe(30)
        ->and($created->is_available)->toBeTrue()
        ->and($created->is_popular)->toBeFalse()
        ->and($created->stall_id)->toBe($this->stall->id);
});

it('menolak nama menu yang sudah dipakai penjual yang sama', function () {
    $this->actingAs($this->vendor)
        ->post(route('vendor.menu.store'), [
            'name' => $this->menu->name,
            'category_id' => $this->catMakanan->id,
            'price' => 10000,
            'stock' => 10,
        ])
        ->assertSessionHasErrors('name');
});

it('penjual dapat memperbarui harga, stok, dan status menu', function () {
    $this->actingAs($this->vendor)
        ->patch(route('vendor.menu.update', $this->menu), [
            'price' => 20500,
            'stock' => 12,
            'is_available' => '0',
            'is_popular' => '1',
        ])
        ->assertRedirect();

    expect($this->menu->fresh())
        ->price->toBe(20500)
        ->stock->toBe(12)
        ->is_available->toBeFalse()
        ->is_popular->toBeTrue();
});

it('menjaga pengurangan stok pesanan saat penjual menyimpan stok usang', function () {
    $this->menu->update(['stock' => 10]);

    $siswa = User::where('role', 'siswa')->first();

    app(OrderService::class)->place(
        $siswa,
        [['menu_id' => $this->menu->id, 'quantity' => 4]],
        'istirahat_1',
    );

    expect($this->menu->fresh()->stock)->toBe(6);

    $this->actingAs($this->vendor)
        ->patch(route('vendor.menu.update', $this->menu), [
            'price' => $this->menu->price,
            'stock' => 10,
            'stock_baseline' => 10,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($this->menu->fresh()->stock)->toBe(6);
});

it('menerapkan selisih penambahan stok terhadap stok terbaru', function () {
    $this->menu->update(['stock' => 10]);

    $siswa = User::where('role', 'siswa')->first();

    app(OrderService::class)->place(
        $siswa,
        [['menu_id' => $this->menu->id, 'quantity' => 4]],
        'istirahat_1',
    );

    $this->actingAs($this->vendor)
        ->patch(route('vendor.menu.update', $this->menu), [
            'price' => $this->menu->price,
            'stock' => 20,
            'stock_baseline' => 10,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($this->menu->fresh()->stock)->toBe(16);
});

it('menolak penyesuaian stok yang membuat stok negatif', function () {
    $this->menu->update(['stock' => 2]);

    $this->actingAs($this->vendor)
        ->patch(route('vendor.menu.update', $this->menu), [
            'price' => $this->menu->price,
            'stock' => 0,
            'stock_baseline' => 10,
        ])
        ->assertSessionHasErrors('stock');

    expect($this->menu->fresh()->stock)->toBe(2);
});

it('penjual dapat menghapus menu miliknya', function () {
    $id = $this->menu->id;

    $this->actingAs($this->vendor)
        ->delete(route('vendor.menu.destroy', $this->menu))
        ->assertRedirect();

    expect(Menu::find($id))->toBeNull();
});

it('penjual lain tidak bisa mengubah atau menghapus menu orang lain', function () {
    $otherVendor = User::where('role', 'vendor')->orderBy('id')->skip(1)->first();

    $this->actingAs($otherVendor)
        ->patch(route('vendor.menu.update', $this->menu), [
            'price' => 100,
            'stock' => 1,
        ])
        ->assertForbidden();

    $this->actingAs($otherVendor)
        ->delete(route('vendor.menu.destroy', $this->menu))
        ->assertForbidden();
});

it('siswa tidak bisa mengakses halaman kelola menu', function () {
    $siswa = User::where('role', 'siswa')->first();

    $this->actingAs($siswa)
        ->get(route('vendor.menu.index'))
        ->assertForbidden();
});

it('mengembalikan pesan kesalahan untuk data yang tidak valid', function () {
    $this->actingAs($this->vendor)
        ->post(route('vendor.menu.store'), [
            'name' => '',
            'category_id' => $this->catMakanan->id,
            'price' => 50,
            'stock' => -5,
        ])
        ->assertSessionHasErrors(['name', 'price', 'stock']);
});

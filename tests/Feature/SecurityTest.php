<?php

use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->siswa = User::where('role', 'siswa')->first();
    $this->vendor = User::where('role', 'vendor')->first();
});

// ─── Login rate limiting ──────────────────────────────────────────

it('membatasi percobaan masuk yang gagal berulang', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => $this->siswa->email,
            'password' => 'password-salah',
        ]);
    }

    $response = $this->post('/login', [
        'email' => $this->siswa->email,
        'password' => 'password-salah',
    ]);

    $response->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('Terlalu banyak percobaan masuk');
});

it('tetap memblokir masuk walau password benar setelah batas terlampaui', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => $this->siswa->email,
            'password' => 'password-salah',
        ]);
    }

    $this->post('/login', [
        'email' => $this->siswa->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

it('mengizinkan masuk dengan kredensial yang benar sebelum batas', function () {
    $this->post('/login', [
        'email' => $this->siswa->email,
        'password' => 'password',
    ])->assertRedirect(route('canteen.index'));

    $this->assertAuthenticatedAs($this->siswa);
});

it('mengarahkan penjual ke dashboard setelah masuk', function () {
    $this->post('/login', [
        'email' => $this->vendor->email,
        'password' => 'password',
    ])->assertRedirect(route('vendor.dashboard'));
});

// ─── Malformed login input ───────────────────────────────────────

it('menolak email berbentuk array tanpa menghasilkan error server', function () {
    $this->post('/login', [
        'email' => ['siswa@bitego.test'],
        'password' => 'password',
    ])->assertRedirect()
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

// ─── Open redirect ────────────────────────────────────────────────

it('menolak redirect protocol-relative ke situs luar', function () {
    $this->post('/login', [
        'email' => $this->siswa->email,
        'password' => 'password',
        'redirect' => '//evil.example',
    ])->assertRedirect(route('canteen.index'));
});

it('menolak redirect dengan backslash yang dinormalisasi browser', function () {
    $this->post('/login', [
        'email' => $this->siswa->email,
        'password' => 'password',
        'redirect' => '/\\evil.example',
    ])->assertRedirect(route('canteen.index'));
});

it('menolak redirect dengan karakter kontrol', function () {
    $this->post('/login', [
        'email' => $this->siswa->email,
        'password' => 'password',
        'redirect' => "/menu\r\nLocation: https://evil.example",
    ])->assertRedirect(route('canteen.index'));
});

it('mengizinkan redirect lokal yang aman', function () {
    $this->post('/login', [
        'email' => $this->siswa->email,
        'password' => 'password',
        'redirect' => '/menu',
    ])->assertRedirect('/menu');
});

// ─── Role separation ──────────────────────────────────────────────

it('tamu diarahkan ke login dari halaman penjual', function () {
    $this->get('/vendor')->assertRedirect(route('auth.login'));
    $this->get('/vendor/menu')->assertRedirect(route('auth.login'));
});

it('siswa ditolak dari halaman penjual', function () {
    $this->actingAs($this->siswa)->get('/vendor')->assertForbidden();
    $this->actingAs($this->siswa)->get('/vendor/menu')->assertForbidden();
});

it('penjual ditolak dari halaman pesanan siswa', function () {
    $this->actingAs($this->vendor)->get('/orders')->assertForbidden();
});

// ─── Safe output rendering ────────────────────────────────────────

it('merender nama menu berbahaya tanpa mengeksekusi script', function () {
    $stall = $this->vendor->stall;
    $category = $stall->menus()->first()->category;
    $payload = '<script>alert(1)</script>';
    $name = "Ayam O'Brien \" $payload";

    $stall->menus()->create([
        'name' => $name,
        'slug' => 'xss-probe-'.Str::lower(Str::random(4)),
        'category_id' => $category->id,
        'price' => 15000,
        'stock' => 10,
        'is_available' => true,
    ]);

    $response = $this->actingAs($this->vendor)->get(route('vendor.menu.index'));

    $response->assertOk();

    $html = $response->getContent();

    expect($html)->not->toContain($payload)
        ->and($html)->toContain('data-menu-name="'.e($name).'"')
        ->and($html)->toContain(e($name));
});

it('tidak membocorkan menu stand lain ke halaman kelola menu', function () {
    $otherMenu = Menu::where('stall_id', '!=', $this->vendor->stall->id)->first();

    $this->actingAs($this->vendor)
        ->get(route('vendor.menu.index'))
        ->assertOk()
        ->assertDontSee($otherMenu->name);
});

# BiteGo Phase 2 — Fix Report

> Lingkup: perbaikan temuan Medium **F1, F2, F3** dan Low **F4, F7, F10** dari `PHASE2-FINAL-REVIEW.md`.
> Tidak ada commit, tidak ada Phase 3, tidak ada perubahan pada migration yang sudah berjalan (`000001`, `000002`), dan tidak ada operasi database destruktif.

## Ringkasan

| Temuan | Tingkat | Status |
|---|---|---|
| F1 — `email[]` membuat login 500 | Medium | Diperbaiki |
| F2 — Lost update stok vendor | Medium | Diperbaiki |
| F3 — Asumsi backfill `stock_deducted` | Medium | Diaudit + didokumentasikan |
| F4 — Validasi input `OrderService` | Low | Diperbaiki |
| F7 — Stall nonaktif masih bisa diakses | Low | Diperbaiki |
| F10 — Double-submit order | Low | Diperbaiki |
| F5, F8, F9 | Low | Tidak termasuk lingkup; tetap backlog |
| F6 — Order demo tidak mengurangi stok | Low | Didokumentasikan (seeder eksplisit `stock_deducted = false`) |

## Perubahan per Temuan

### F1 — Guard input array pada rate limiter login
- `app/Providers/AppServiceProvider.php:29-30`
- Email dibaca tanpa cast paksa: `is_string($email) ? Str::lower(trim($email)) : ''`.
- Request `email[]=...` sekarang melewati rate limiter dan berhenti di validasi controller sebagai error validasi, bukan HTTP 500.
- Test baru: `tests/Feature/SecurityTest.php` — `menolak email berbentuk array tanpa menghasilkan error server`.

### F2 — Update stok vendor berbasis delta + row lock
- `app/Http/Controllers/VendorController.php:123-170` (`menuUpdate`)
- Form vendor mengirim `stock_baseline` tersembunyi (`resources/views/vendor/menu.blade.php:154`).
- Controller menghitung `delta = stock_dikirim - stock_baseline`, lalu di dalam `DB::transaction` membaca ulang menu dengan `lockForUpdate()` dan menerapkan delta ke stok terbaru.
- Jika hasilnya negatif, dikembalikan sebagai `ValidationException` pada field `stock`.
- Fallback kompatibilitas: bila `stock_baseline` tidak dikirim (klien lama/test lama), baseline memakai stok route-bound sehingga perilaku absolut lama tetap sama.
- Test baru di `tests/Feature/VendorMenuManagementTest.php`:
  - stok usang tidak menimpa pengurangan order,
  - penambahan stok dihitung sebagai delta,
  - penyesuaian yang membuat stok negatif ditolak.

### F3 — Audit asumsi backfill `stock_deducted`
- Migration `2026_11_08_000002_add_stock_deducted_to_orders_table.php` **tidak diubah** karena sudah berjalan.
- Audit data aktual (read-only):
  - total order: **9**
  - `stock_deducted = true`: **9**
  - order `menunggu`: **1** (order nyata id 9, sesuai catatan review)
- Tidak dilakukan reklasifikasi data karena order lama demo dan order nyata pasca-Phase 2 tidak dapat dibedakan secara aman dari data saja.
- `database/seeders/DatabaseSeeder.php` sekarang menulis eksplisit `'stock_deducted' => false` untuk order demo, sehingga seed baru tidak lagi bergantung pada default kolom.
- Rekomendasi operasional: pada environment lain yang memiliki order demo `menunggu` lama, jalankan audit manual dan set `stock_deducted = false` hanya untuk order yang terbukti tidak pernah mengurangi stok sebelum deployment.

### F4 — Validasi defensif `OrderService::place()`
- `app/Services/OrderService.php:17,29,41-45,153-190`
- Ditambahkan `MAX_QUANTITY_PER_MENU = 10` dan `validateInput()`.
- Ditolak dengan `ValidationException` sebelum transaksi:
  - daftar baris kosong,
  - `menu_id`/`quantity` hilang atau bukan bilangan bulat positif,
  - total quantity per menu melebihi 10,
  - `pickup_slot` di luar `istirahat_1`/`istirahat_2`,
  - `notes` lebih dari 255 karakter.
- Test baru: `tests/Feature/OrderIntegrityTest.php` — input tidak valid tidak membuat order dan tidak mengubah stok.

### F7 — Stall nonaktif diblokir di semua jalur
- `app/Http/Controllers/CanteenController.php:19-23,37-39,58-64`
  - Menu populer dan daftar menu difilter dengan `whereHas('stall', is_active = true)`.
  - Halaman detail stall `abort(404)` bila tidak aktif.
- `app/Http/Controllers/OrderController.php:16-24,38-45`
  - `create()` dan `store()` memuat stall dan `abort(404)` bila stall tidak aktif.
- `app/Services/OrderService.php:79`
  - Service juga menolak menu dari stall nonaktif (`$menu->stall?->is_active`).
- Test baru: `tests/Feature/OrderIntegrityTest.php` — menu tidak tampil di `/` dan `/menu`, halaman stall/create 404, POST order 404, jumlah order tidak berubah.

### F10 — Idempotency key untuk pembuatan order
- Migration baru: `database/migrations/2026_11_08_000003_add_idempotency_key_to_orders_table.php`
  - kolom `idempotency_key` nullable (64) setelah `order_number`,
  - unique index `(user_id, idempotency_key)`; NULL tetap diizinkan berulang.
- `app/Models/Order.php:28-31` — `idempotency_key` masuk `$fillable`.
- `app/Http/Controllers/OrderController.php`
  - `create()` mengirim UUID baru ke view,
  - `store()` memvalidasi `idempotency_key` (nullable, maks 64) dan meneruskannya ke service,
  - `resources/views/orders/create.blade.php:60` menyimpan key sebagai hidden input.
- `app/Services/OrderService.php:20-150`
  - `place()` menerima `?string $idempotencyKey` sebagai parameter terakhir,
  - jika key sudah ada untuk user tersebut, order lama dikembalikan tanpa transaksi baru,
  - bila terjadi `UniqueConstraintViolationException` karena balapan, order pemenang dicari dan dikembalikan,
  - retry pickup code tetap berjalan untuk konflik non-idempotency.
- Test baru: `tests/Feature/OrderIntegrityTest.php` — dua POST berurutan dengan key sama hanya menghasilkan satu order dan satu kali pengurangan stok.

## Verifikasi

- `php artisan migrate --force` → `2026_11_08_000003...` berhasil (batch 4).
- `php artisan test --colors=never` → **65 passed / 199 assertions / 0 failed / 0 skipped** (baseline 58/165; +7 test, +34 assertion).
- `php vendor/bin/pint --test` → **passed**.
- `git diff --check` → **bersih** (tidak ada whitespace error).
- Skema terverifikasi: kolom `idempotency_key` ada; **9** order lama tetap `NULL`; **9** order tetap `stock_deducted = true`; tidak ada data yang dihapus atau diubah selain penambahan kolom/index.

## Catatan dan Risiko Sisa

- **F5** (preflight duplikat sebelum unique index) belum ditambahkan karena migration terkait sudah berjalan. Query audit tetap perlu dijalankan manual sebelum deploy ke environment lain.
- **F6** tetap seperti desain: order demo tidak mengurangi stok, tetapi sekarang ditandai eksplisit `stock_deducted = false`.
- **F8** (test paralel dua koneksi) belum dibuat; verifikasi concurrency masih tidak langsung.
- **F9** (race validasi unique pada register/nama menu) belum diperbaiki; tetap backlog.
- Rollback migration baru (`000003`) hanya menghapus kolom/index idempotency dan tidak menyentuh data order lain. Karena aplikasi sudah memakai kolom tersebut, rollback harus disertai penyesuaian kode.

## Status Git

- Semua perubahan masih berada di working tree branch `feature/ordering-system`.
- Tidak ada commit yang dibuat.

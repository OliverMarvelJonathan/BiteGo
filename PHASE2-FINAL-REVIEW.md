# BiteGo Phase 2 — Final Code Review

> Review dilakukan secara read-only. Tidak ada kode, database, migration, atau commit yang diubah.
> Test suite tidak dijalankan ulang pada review ini untuk menghormati larangan mengubah database.
> Hasil test terakhir yang tercatat: **58 passed / 165 assertions / 0 failed / 0 skipped**.

## Ringkasan Eksekutif

- **Critical:** 0
- **High:** 0
- **Medium:** 3
- **Low:** 7
- Tidak ditemukan celah XSS, SQL injection, open redirect, atau otorisasi utama yang masih terbuka pada perubahan Phase 2.
- Ada beberapa bug robustness dan concurrency yang belum dicakup test.

**Keputusan akhir: READY AFTER MINOR FIXES.**

---

## 1. Status Git dan Cakupan Perubahan

Branch: `feature/ordering-system`, up to date dengan `origin/feature/ordering-system`.

Perubahan belum di-commit:

- **10 file tracked yang dimodifikasi**
- **5 path untracked / 6 file baru**:
  - `app/Services/OrderService.php`
  - `database/migrations/2026_11_08_000001_add_pickup_date_and_unique_code_to_orders_table.php`
  - `database/migrations/2026_11_08_000002_add_stock_deducted_to_orders_table.php`
  - `tests/Feature/OrderIntegrityTest.php`
  - `tests/Feature/SecurityTest.php`

Tidak ada commit baru. Semua pekerjaan Phase 2 masih berada di working tree.

---

## 2. Review `OrderService.php`

### Yang sudah benar

- `place()` memakai `DB::transaction`.
- Menu dikunci dengan `lockForUpdate()` dan diurutkan berdasarkan `id` sebelum dibaca.
- Quantity per menu diagregasi dan diurutkan dengan `ksort`.
- Validasi `is_available`, kesamaan `stall_id`, dan kecukupan stok dilakukan setelah row lock.
- `deductStock()` memakai guard `WHERE stock >= quantity`.
- Item dan order dibuat di dalam transaksi yang sama; kegagalan item akan me-rollback order dan stok.
- `transition()` membaca ulang order dengan `lockForUpdate()`, sehingga cancel/advance bersamaan tidak dapat memproses state yang sama dua kali.
- Restorasi stok hanya dilakukan jika `stock_deducted = true`, lalu flag diubah menjadi `false`.
- Pickup code memakai `random_int`, cek duplikat, unique index, dan retry pada `UniqueConstraintViolationException`.

### Catatan

- Locking menu pada `place()` dan increment menu pada `transition()` menggunakan urutan `id`, sehingga risiko deadlock dua transaksi yang menyentuh menu yang sama relatif kecil.
- `transition()` tidak melakukan otorisasi sendiri; otorisasi dilakukan di controller. Ini dapat diterima untuk kondisi sekarang, tetapi service tidak boleh dipanggil dari jalur baru tanpa pengecekan otorisasi.

---

## 3. Review Status Transition dan Authorization

### Yang sudah benar

- Lifecycle `menunggu → dimasak → siap_ambil → selesai` ditegakkan melalui `Order::STATUS_NEXT` dan `canTransitionTo()`.
- `dibatalkan` hanya diizinkan dari `menunggu` melalui `CANCELLABLE_STATUSES`.
- Lompatan status, status mundur, status tidak dikenal, dan pembukaan kembali order selesai ditolak.
- Vendor dicek kepemilikan stall-nya sebelum mengubah status.
- Route vendor memakai middleware `role:vendor`.
- Order siswa memakai middleware `auth` + `role:siswa`.
- `show`, `success`, dan `cancel` memeriksa kepemilikan `user_id`.

### Catatan

- Otorisasi pada `cancel` memakai model hasil route binding sebelum masuk service. Karena `user_id` order tidak berubah, ini aman pada kondisi sekarang.
- `VendorController::updateStatus` mengakses `$order->stall->user_id`. Jika relasi stall hilang (tidak mungkin karena foreign key cascade), akan terjadi error. Bukan risiko aktif.

---

## 4. Review Keamanan

### Yang sudah diperbaiki dan terverifikasi dari kode

- **XSS di `vendor/menu.blade.php`**: nama menu tidak lagi dimasukkan mentah ke string JavaScript. Sekarang memakai `data-menu-name` yang di-escape Blade dan dibaca lewat `dataset`.
- **Open redirect**: `AuthController::isSafeLocalPath()` hanya mengizinkan path same-origin, menolak `//host`, backslash, whitespace, dan karakter kontrol.
- **Session fixation**: session diregenerasi setelah login dan register.
- **Rate limiting**: login 5/menit per email+IP dan 30/menit per IP; register 30/jam per IP.
- **Otorisasi**: kepemilikan order, kepemilikan stall, dan pemisahan role siswa/vendor.
- **Validasi dan mass assignment**: input divalidasi eksplisit; tidak ada pemakaian request-wide mass assignment.
- **Output**: tidak ditemukan `{!! !!}`, `@json`, atau interpolasi data user mentah ke inline script.
- **SQL injection**: pencarian menu memakai query parameter binding; tidak ditemukan raw SQL dengan input user.
- **CSRF**: form-form utama memiliki `@csrf`.

### Catatan

- Isu array pada input email rate limiter (temuan F1) adalah masalah robustness/availability, bukan XSS atau SQL injection.
- Tidak ada klaim bahwa aplikasi “100% aman”; penilaian di atas hanya berdasarkan kode dan test yang diperiksa.

---

## 5. Review Migration dan Kompatibilitas Data Lama

### Migration 1 — pickup date + unique code

- Menambahkan `pickup_date` nullable, backfill dari `DATE(created_at)`, lalu mengubahnya menjadi NOT NULL.
- Menambahkan unique index `(stall_id, pickup_date, pickup_code)`.
- Data aktual `bitego` yang diperiksa sebelumnya: **0 duplicate group**, **0 null pickup_date**.
- Migration ini sudah berjalan (batch 2).
- Risiko: tidak ada pengecekan duplikat di dalam migration. Jika environment lain memiliki duplikat lama, pembuatan unique index akan gagal dan deployment terhenti. Tidak ada data yang dihapus.

### Migration 2 — stock_deducted

- Menambahkan boolean `stock_deducted` default `false`.
- Backfill semua order lama menjadi `true`.
- Migration ini sudah berjalan (batch 3).
- Risiko: order demo/seeder lama yang tidak pernah mengurangi stok juga ikut ditandai `true`. Pada database `bitego` saat ini, satu-satunya order `menunggu` adalah order nyata (id 9), sehingga aman untuk data sekarang. Di environment lain dengan order demo `menunggu`, pembatalan bisa menambah stok yang tidak pernah dikurangi.

### Down migration

- Kedua migration memiliki `down()`, tetapi rollback akan menghapus kolom yang sudah dipakai model. Rollback hanya boleh dilakukan dengan sadar dan setelah aplikasi disesuaikan.

---

## 6. Kualitas Test

### Yang sudah diuji dengan baik

- Pembuatan order sukses dan pengurangan stok.
- Stok tidak cukup, stok habis, dan pencegahan stok negatif.
- Rollback transaksi saat penulisan item gagal.
- Valid transition dan invalid transition.
- Pembatalan siswa dan vendor, termasuk pencegahan restorasi ganda.
- Pembatalan setelah diproses ditolak.
- Otorisasi lintas siswa dan lintas vendor.
- Pickup code 4 digit dan unique index.
- XSS pada nama menu di halaman vendor.
- Login rate limiting dan open redirect.
- Pemisahan akses siswa/vendor.

### Keterbatasan test

- Test concurrency bersifat tidak langsung: `memakai stok terbaru meski model menu sudah usang` memanggil service secara berurutan, bukan dua proses/dua koneksi paralel.
- Tidak ada test untuk input `email[]` pada login (temuan F1).
- Tidak ada test untuk race antara update stok vendor dan pembuatan order (temuan F2).
- Tidak ada test untuk backfill migration (temuan F3).
- Tidak ada test untuk input service yang kosong/negatif (temuan F4).
- Retry pickup code saat unique violation tidak diuji langsung; yang diuji adalah constraint unique-nya.
- Flag `stock_deducted` setelah pembatalan normal tidak selalu di-assert; yang di-assert adalah stok kembali dan pembatalan kedua gagal.
- XSS pada `notes` dan URL gambar hanya diverifikasi dari kode Blade, bukan test eksplisit.

---

## 7. Temuan

### F1 — Medium — Input `email` berupa array membuat login 500

- **File/lokasi:** `app/Providers/AppServiceProvider.php:29`
- **Penjelasan:** Rate limiter login melakukan `(string) $request->input('email')`. Jika request mengirim `email[]=x`, PHP mengeluarkan warning “Array to string conversion”. Laravel `HandleExceptions` mengubah warning tersebut menjadi `ErrorException`, sehingga request gagal sebelum validasi controller berjalan.
- **Dampak:** Request tidak terautentikasi ke `POST /login` dapat menghasilkan HTTP 500 dan log spam. Validasi seharusnya mengembalikan error validasi, bukan 500.
- **Rekomendasi:** Jangan cast langsung. Gunakan guard tipe, misalnya `$email = $request->input('email'); $email = is_string($email) ? Str::lower(trim($email)) : '';`, atau validasi bentuk input sebelum rate limiter membaca email. Tambahkan test `email[]=x`.

### F2 — Medium — Update stok vendor dapat menimpa pengurangan stok order (lost update)

- **File/lokasi:** `app/Http/Controllers/VendorController.php:123-139` (`menuUpdate`, khususnya `$menu->update(['stock' => ...])` di baris 132-138)
- **Penjelasan:** Vendor menyimpan stok sebagai nilai absolut dari model yang dibaca saat halaman dimuat. Tidak ada transaksi atau `lockForUpdate()` saat menyimpan. Jika siswa memesan di antara waktu baca dan waktu simpan, nilai stok lama dapat menimpa stok terbaru.
- **Dampak:** Stok bisa kembali lebih tinggi dari seharusnya, sehingga penjualan berikutnya dapat menyebabkan overselling atau stok tidak akurat.
- **Rekomendasi:** Bungkus update dalam `DB::transaction`, baca ulang menu dengan `lockForUpdate()`, lalu update. Alternatif yang lebih aman: pisahkan endpoint penyesuaian stok berbasis delta. Tambahkan test konkurensi untuk skenario ini.

### F3 — Medium — Backfill `stock_deducted = true` mengasumsikan semua order lama sudah mengurangi stok

- **File/lokasi:** `database/migrations/2026_11_08_000002_add_stock_deducted_to_orders_table.php:16`
- **Penjelasan:** Semua order lama ditandai `true`, termasuk order demo/seeder yang tidak pernah mengurangi stok. Pada database saat ini, satu-satunya order `menunggu` adalah order nyata (id 9), sehingga aman untuk data sekarang. Namun asumsi ini tidak selalu benar di environment lain.
- **Dampak:** Jika ada order demo `menunggu` lama yang tidak pernah mengurangi stok, pembatalannya akan menambah stok yang tidak semestinya.
- **Rekomendasi:** Audit data lama sebelum deploy, tandai order demo sebagai `false`, atau sediakan command/migration rekonsiliasi. Dokumentasikan asumsi ini.

### F4 — Low — `OrderService::place()` tidak memvalidasi bentuk input service

- **File/lokasi:** `app/Services/OrderService.php:20-30`, `44-68`, `147-153`
- **Penjelasan:** Service adalah API publik, tetapi tidak memvalidasi `$lines` kosong, key `quantity` yang hilang, atau quantity `<= 0`. Input negatif dapat menghasilkan total/quantity negatif yang ditolak kolom unsigned (500), sedangkan input kosong dapat menghasilkan `stall_id` null dan error database. Jalur HTTP saat ini selalu memvalidasi quantity 1–10, sehingga bug ini belum reachable dari UI.
- **Dampak:** Pemanggil service baru atau perubahan controller di masa depan dapat menghasilkan 500, bukan ValidationException. Jika constraint database berubah, quantity negatif berpotensi mengubah stok ke arah yang salah.
- **Rekomendasi:** Validasi `$lines` di awal service: tidak kosong, setiap `menu_id` integer, setiap `quantity` integer minimal 1 dan dalam batas wajar. Validasi juga `pickupSlot` dan panjang `notes`.

### F5 — Low — Migration pickup code tidak memeriksa duplikat sebelum membuat unique index

- **File/lokasi:** `database/migrations/2026_11_08_000001_add_pickup_date_and_unique_code_to_orders_table.php:16-20`
- **Penjelasan:** Migration langsung membuat unique index. Jika data lama memiliki duplikat `(stall_id, pickup_date, pickup_code)`, migration akan gagal. Data `bitego` saat ini terbukti 0 duplikat, tetapi migration tidak memiliki preflight check.
- **Dampak:** Deployment dapat gagal di environment lain tanpa pesan yang jelas. Tidak ada data yang dihapus, tetapi proses migrasi terhenti.
- **Rekomendasi:** Tambahkan query audit/preflight sebelum index, atau dokumentasikan query pemeriksaan duplikat untuk dijalankan sebelum deploy.

### F6 — Low — Seeder membuat order demo tanpa mengurangi stok

- **File/lokasi:** `database/seeders/DatabaseSeeder.php:115-173`
- **Penjelasan:** Sample order dibuat langsung melalui `Order::create()` tanpa pengurangan stok dan tanpa `stock_deducted = true`.
- **Dampak:** Data demo tidak konsisten dengan stok menu. Tidak berdampak ke produksi karena ini data seed.
- **Rekomendasi:** Kurangi stok untuk order demo non-cancelled, atau dokumentasikan bahwa data demo memang tidak mengurangi stok.

### F7 — Low — Stall nonaktif masih dapat diakses/dipesan lewat URL langsung

- **File/lokasi:** `app/Http/Controllers/CanteenController.php:31-53,58-64`; `app/Http/Controllers/OrderController.php:15-27`
- **Penjelasan:** Filter `is_active` hanya dipakai di halaman index. Halaman menu, halaman stall, dan halaman create order hanya memeriksa `is_available` dan stok menu.
- **Dampak:** Siswa yang mengetahui slug menu/stall dapat memesan dari stall yang sudah dinonaktifkan.
- **Rekomendasi:** Tambahkan filter `whereHas('stall', fn ($q) => $q->where('is_active', true))` pada query menu dan guard di `create`/`store`.

### F8 — Low — Test concurrency belum benar-benar paralel

- **File/lokasi:** `tests/Feature/OrderIntegrityTest.php:46-63`
- **Penjelasan:** Test `memakai stok terbaru meski model menu sudah usang` memanggil service dua kali secara berurutan. Ini menguji re-read di bawah lock, tetapi bukan dua transaksi paralel.
- **Dampak:** Tidak ada bukti otomatis bahwa dua request paralel benar-benar tidak oversell, meskipun implementasi `lockForUpdate` sudah tepat.
- **Rekomendasi:** Tambahkan test dengan dua koneksi database/dua proses, atau dokumentasikan sebagai verifikasi manual. Assert juga bahwa `stock_deducted` kembali `false` setelah pembatalan normal.

### F9 — Low — Race pada validasi unique dapat menghasilkan 500

- **File/lokasi:** `app/Http/Controllers/AuthController.php:18-43` (email register); `app/Http/Controllers/VendorController.php:87-118` (nama menu/slug)
- **Penjelasan:** Validasi `unique` berjalan sebelum insert. Dua request bersamaan dengan nilai yang sama dapat sama-sama lolos validasi, lalu salah satunya gagal di unique constraint database dan menghasilkan 500.
- **Dampak:** Error 500 pada kondisi balapan, bukan pesan validasi yang ramah.
- **Rekomendasi:** Tangkap `UniqueConstraintViolationException` dan ubah menjadi `ValidationException`, atau gunakan alur insert yang menangani konflik.

### F10 — Low — Tidak ada proteksi double-submit pada pembuatan order

- **File/lokasi:** `app/Http/Controllers/OrderController.php:32-49`
- **Penjelasan:** Tidak ada idempotency key atau penguncian submit. Double click atau retry jaringan dapat membuat dua order dan mengurangi stok dua kali.
- **Dampak:** Order ganda dan pengurangan stok ganda.
- **Rekomendasi:** Nonaktifkan tombol setelah submit, tambahkan idempotency token per form, atau batasi pembuatan order per user dalam interval singkat.

---

## 8. Keputusan

**READY AFTER MINOR FIXES**

Alasan:

- Tidak ada temuan Critical atau High.
- Implementasi inti Phase 2 (transaksi, row locking, status transition, cancellation, pickup uniqueness, security) sudah benar dan didukung test.
- Namun ada 3 temuan Medium yang sebaiknya diperbaiki/didokumentasikan sebelum Phase 3:
  1. **F1** — guard input array pada rate limiter login.
  2. **F2** — locking/transaksi pada update stok vendor.
  3. **F3** — audit asumsi backfill `stock_deducted`.

Minimal sebelum Phase 3:

- Perbaiki F1 dan F2.
- Dokumentasikan atau rekonsiliasi F3.
- Pertimbangkan F5 sebagai preflight deployment.

Temuan Low dapat dikerjakan setelah Phase 3 dimulai atau dimasukkan ke backlog, kecuali F4 dan F9 yang murah untuk diperbaiki sekarang.

# 🍽️ BiteGo

> **Segar. Cepat. Lezat.**  
> Platform **Pre-Order & Pemesanan Makanan Kantin Sekolah Modern — Bebas Antre.**

![BiteGo Logo](Logo%20A.jpg)

## 📌 Tentang BiteGo

**BiteGo** adalah platform pemesanan makanan berbasis web yang dirancang khusus untuk lingkungan kantin sekolah. BiteGo memungkinkan siswa melakukan **pre-order makanan dari dalam kelas sebelum jam istirahat**, memilih jadwal pengambilan, lalu mengambil pesanan melalui **Express Pickup** tanpa perlu mengantre panjang.

BiteGo hadir sebagai solusi untuk mengatasi antrean kantin, keterbatasan waktu istirahat, penjual yang kewalahan, serta ketidakpastian ketersediaan menu.

## 🎯 Tujuan

- ⏱️ **Efisiensi waktu** — memangkas waktu tunggu siswa dari sekitar 15 menit menjadi kurang dari 2 menit melalui sistem pre-order.
- 🍳 **Penyiapan makanan lebih teratur** — membantu pedagang memperkirakan jumlah porsi berdasarkan pesanan yang masuk.
- 🏫 **Digitalisasi sekolah** — mendukung terciptanya ekosistem **Smart Canteen** yang modern, tertib, higienis, dan ramah pengguna.

## 👥 Target Pengguna

### 👨‍🎓 Siswa & Guru
Dapat memesan makanan lebih awal dari kelas dan mengambilnya saat jam istirahat tanpa harus mengantre.

### 🧑‍🍳 Pengelola Stand Kantin
Mendapatkan rekap pesanan lebih awal sehingga persiapan makanan dan jumlah porsi dapat dilakukan dengan lebih teratur.

### 🏫 Pihak Sekolah
Mendukung lingkungan kantin yang lebih tertib, bersih, tidak berdesakan, dan memanfaatkan teknologi modern.

## ✨ Fitur Utama

### 🏪 Katalog Stand Kantin Digital
Menampilkan daftar penjual, menu harian, harga, serta ketersediaan stok makanan secara transparan.

### 🕐 Pre-Order Jam Istirahat
Siswa dapat memilih jadwal pengambilan pesanan:
- **Istirahat I**
- **Istirahat II**

### 🔢 PIN / Kode Unik Pengambilan
Setiap pesanan mendapatkan **kode unik 4 digit**. Siswa cukup menunjukkan atau menyebutkan kode tersebut kepada penjual untuk mengambil pesanan.

### 📊 Dashboard Penjual Kantin
Penjual dapat memantau pesanan secara real-time dengan status:

`Menunggu → Dimasak → Siap Ambil`

### ⚡ Express Pickup
Pesanan yang sudah siap dapat langsung diambil melalui konter **Express Pickup**, sehingga siswa tidak perlu mengantre seperti pada proses pembelian biasa.

## 🔄 Alur Pemesanan

```text
Pilih Stand Kantin
       ↓
Pilih Menu & Jam Ambil
       ↓
Konfirmasi Pesanan
       ↓
Dapatkan Kode Unik 4 Digit
       ↓
Pesanan Diproses Penjual
       ↓
Status: Siap Ambil
       ↓
Express Pickup
```

## 🎨 Konsep UI/UX

BiteGo menggunakan konsep antarmuka yang modern, sederhana, dan cocok untuk lingkungan sekolah.

- 🌱 **Fresh Mint** — memberikan kesan segar dan ramah.
- ☀️ **Sunlight Yellow** — memberikan kesan cerah dan energik.
- 📱 **Responsive Design** — dirancang agar nyaman digunakan melalui perangkat siswa.
- 3️⃣ **Three-Step Ordering** — proses pemesanan dibuat sederhana:
  **Pilih Stand → Pilih Menu & Jam Ambil → Dapatkan Kode Pengambilan**
- 🔄 **Real-Time Order Status** — siswa dapat melihat perkembangan pesanan dari **Menunggu → Dimasak → Siap Diambil**.

## 🛠️ Teknologi yang Digunakan

| Teknologi | Fungsi |
|---|---|
| **Laravel 13** | Backend, logika bisnis, transaksi pre-order, dan RESTful API |
| **Tailwind CSS** | Styling dan pembuatan UI yang responsif |
| **MySQL** | Penyimpanan data relasional |
| **GitHub** | Version control dan kolaborasi pengembangan |

## 🗄️ Data yang Dikelola

Sistem menggunakan database relasional untuk menyimpan data seperti:

- Pengguna
- Stand kantin
- Menu makanan
- Ketersediaan stok
- Pesanan
- Transaksi
- Status pesanan
- Kode unik pengambilan

## 👨‍💻 Tim Pengembang — TechBite

| Nama | Peran |
|---|---|
| **Bryan Geraldo** | Project Leader & Backend |
| **Darren Bagus** | Frontend Developer |
| **Luis Fabian** | UI/UX Designer |
| **Oliver Marvel** | UI/UX Designer |

## 🗓️ Timeline Pengembangan

| Minggu | Tahap |
|---|---|
| **1–2** | Desain UI/UX Web menggunakan Figma |
| **3–5** | Backend & Database menggunakan Laravel dan MySQL, termasuk generator kode unik 4 digit |
| **6–7** | Frontend & Integrasi menggunakan Tailwind CSS |
| **8** | Testing, finalisasi, dan persiapan demo/presentasi |

## 🚀 Konsep Pengembangan

BiteGo dikembangkan dengan fokus pada tiga hal utama:

**Cepat** — mengurangi waktu antre dan mempercepat proses pengambilan makanan.

**Teratur** — membantu penjual mengetahui jumlah pesanan sebelum jam istirahat.

**Modern** — menghadirkan pengalaman kantin berbasis teknologi melalui sistem pre-order dan status pesanan real-time.

## 📖 Status Proyek

> <img src="https://img.shields.io/badge/STATUS-COMPLETED-2ea44f?style=for-the-badge" alt="Completed">

BiteGo telah menyelesaikan tahap pengembangan sebagai platform **Smart Canteen** berbasis web untuk lingkungan kantin sekolah.

Proyek ini telah melalui tahapan perancangan UI/UX, pengembangan frontend dan backend, integrasi database, serta finalisasi aplikasi.

| Informasi | Detail |
|---|---|
| **Project Status** | Completed |
| **Development Phase** | Finalized |
| **Project Type** | School Final Project |

---

## ⚙️ Instalasi dan Konfigurasi

Ikuti langkah berikut untuk menjalankan BiteGo di lingkungan lokal.

### Persyaratan Sistem

Pastikan perangkat sudah memiliki:

- PHP 8.3 atau lebih baru
- Composer
- Node.js dan npm
- MySQL
- Git

### 1. Clone Repository

Buka terminal dan jalankan:

```bash
git clone https://github.com/OliverMarvelJonathan/BiteGo.git
```

Masuk ke direktori proyek:

```bash
cd BiteGo
```

### 2. Install Dependencies

Install seluruh dependencies Laravel:

```bash
composer install
```

Install dependencies frontend:

```bash
npm install
```

### 3. Konfigurasi Environment

Salin file `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

Untuk pengguna Windows, file tersebut juga dapat disalin secara manual melalui File Explorer.

Generate application key:

```bash
php artisan key:generate
```

### 4. Konfigurasi Database

Buat database baru melalui MySQL dengan nama:

```sql
CREATE DATABASE bitego;
```

Kemudian sesuaikan konfigurasi database pada file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bitego
DB_USERNAME=root
DB_PASSWORD=
```

Sesuaikan username dan password dengan konfigurasi MySQL pada perangkat masing-masing.

### 5. Migrasi dan Seed Database

Jalankan migrasi untuk membuat tabel database:

```bash
php artisan migrate
```

Untuk mengisi database dengan data contoh (demo), jalankan:

```bash
php artisan db:seed
```

### 6. Menjalankan Aplikasi

Jalankan server Laravel:

```bash
php artisan serve
```

Kemudian buka terminal baru dan jalankan Vite:

```bash
npm run dev
```

Buka aplikasi melalui browser:

```text
http://127.0.0.1:8000
```

---

## 📖 Panduan Penggunaan Aplikasi

BiteGo memiliki dua jenis pengguna utama, yaitu siswa dan penjual kantin.

### 👨‍🎓 Siswa

1. Buka halaman BiteGo melalui browser.
2. Buat akun melalui halaman Register.
3. Login menggunakan akun yang telah dibuat.
4. Pilih stand kantin yang tersedia.
5. Pilih makanan atau minuman dari katalog menu.
6. Tambahkan produk ke keranjang.
7. Lanjutkan ke halaman Checkout.
8. Lengkapi informasi pemesanan dan pilih jadwal pengambilan.
9. Konfirmasi pesanan.
10. Simpan kode unik 4 digit yang diberikan sistem.
11. Pantau status pesanan melalui halaman pesanan.
12. Ambil pesanan ketika status sudah Siap Ambil.

### 🧑‍🍳 Penjual Kantin

1. Login menggunakan akun penjual.
2. Masuk ke Dashboard Penjual.
3. Lihat daftar pesanan yang masuk.
4. Perbarui status pesanan sesuai proses persiapan makanan.
5. Kelola menu makanan dan minuman.
6. Perbarui informasi menu sesuai ketersediaan produk.

---

## 🏗️ Arsitektur Sistem

BiteGo menggunakan pendekatan Model-View-Controller (MVC) dari framework Laravel untuk memisahkan pengelolaan data, logika aplikasi, dan tampilan pengguna.

### Komponen Arsitektur

| Komponen | Teknologi | Tanggung Jawab |
|---|---|---|
| Frontend | Blade & Tailwind CSS | Menampilkan antarmuka pengguna |
| Backend | Laravel 13 | Mengelola logika aplikasi dan pemrosesan pesanan |
| Database | MySQL | Menyimpan data pengguna, menu, stand, dan pesanan |
| Build Tool | Vite | Mengelola dan membangun aset frontend |
| Version Control | Git & GitHub | Mengelola versi kode dan kolaborasi |

### Alur Kerja Sistem

1. Pengguna mengakses aplikasi melalui browser.
2. Request diterima oleh sistem routing Laravel.
3. Middleware memeriksa autentikasi dan hak akses pengguna.
4. Controller memproses permintaan dan menjalankan logika aplikasi.
5. Model berinteraksi dengan database melalui Eloquent ORM.
6. Data dikirim kembali untuk ditampilkan melalui halaman aplikasi.

### Struktur Direktori Utama

```text
BiteGo/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   └── Models/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
├── routes/
│   └── web.php
├── storage/
├── tests/
├── .env.example
├── artisan
├── composer.json
├── package.json
└── vite.config.js
```

---

## 🤝 Kontribusi dan Kolaborasi

Pengembangan BiteGo dilakukan secara kolaboratif oleh tim TechBite menggunakan Git dan GitHub.

Untuk menjaga keteraturan kode selama pengembangan, berikut alur kontribusi yang digunakan sebagai panduan:

1. Pastikan repository sudah tersedia secara lokal.
2. Buat branch baru sesuai fitur atau pekerjaan yang dikerjakan.
3. Lakukan perubahan kode sesuai tanggung jawab masing-masing.
4. Gunakan commit message yang jelas untuk setiap perubahan.
5. Push branch ke repository GitHub.
6. Buat Pull Request (PR) untuk proses pemeriksaan dan penggabungan kode.

Contoh pembuatan branch:

```bash
git checkout -b feature/nama-fitur
```

Contoh commit:

```bash
git add .
git commit -m "feat: add ordering feature"
git push origin feature/nama-fitur
```

Setiap anggota tim bertanggung jawab menjaga kualitas kode, berkomunikasi selama proses pengembangan, dan memastikan fitur yang dibuat dapat berjalan dengan baik.

---

## 📄 Lisensi

BiteGo menggunakan lisensi **MIT License**, sesuai deklarasi lisensi pada `composer.json`.

Lisensi ini memberikan izin penggunaan, modifikasi, dan distribusi perangkat lunak sesuai ketentuan MIT License.

Untuk informasi lebih lanjut mengenai lisensi, silakan lihat dokumentasi [MIT License](https://opensource.org/license/mit).

### 💚 BiteGo
**Segar. Cepat. Lezat.**

*Smart Canteen Experience — Bebas Antre.*

# TokoKasir

Sistem Point of Sale (POS) berbasis web untuk mendukung penjualan dan pengelolaan stok toko retail.
Proyek Capstone — Teknik Informatika, Universitas Nusantara PGRI Kediri, 2026.

**Teknologi:** Laravel 12 (PHP 8.2+, pola MVC) · Blade + Alpine.js · Tailwind CSS 4 · MySQL/MariaDB · Chart.js · DomPDF

---

## Cara menjalankan

### 1. Prasyarat

- PHP 8.2+ dengan ekstensi `pdo_mysql`, `mbstring`, `xml`, `gd`, `zip`
- Composer 2, Node.js 20+, MySQL 8 / MariaDB 10.6+

### 2. Buat database

```bash
sudo mysql -e "CREATE DATABASE tokokasir CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tokokasir'@'localhost' IDENTIFIED BY 'password_anda';
GRANT ALL ON tokokasir.* TO 'tokokasir'@'localhost';
CREATE DATABASE tokokasir_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL ON tokokasir_test.* TO 'tokokasir'@'localhost';"
```

Isi `DB_PASSWORD` pada file `.env` dengan password yang sama.

### 3. Instal & isi data

```bash
composer install
npm install && npm run build
cp .env.example .env        # lewati jika .env sudah ada
php artisan key:generate    # lewati jika APP_KEY sudah terisi
php artisan migrate --seed  # tabel + data demo 30 hari
php artisan serve
```

Buka <http://127.0.0.1:8000>. Agar bisa diakses dari tablet/komputer lain di jaringan yang sama:
`php artisan serve --host=0.0.0.0` lalu buka `http://<ip-komputer>:8000`.

### 4. Akun demo (password: `password`)

| Peran | Email |
|---|---|
| Kasir | `kasir@tokokasir.test` (Siti) · `kasir2@tokokasir.test` (Budi) |
| Admin | `admin@tokokasir.test` |
| Pemilik Toko | `pemilik@tokokasir.test` |

Seeder membuat 37 produk, 4 supplier, ±800 transaksi, dan riwayat shift 30 hari terakhir
melalui service aplikasi, sehingga stok, kartu stok, dan laporan saling konsisten.
Jalankan ulang dengan `php artisan migrate:fresh --seed`.

### 5. Pengujian

```bash
php artisan test
```

Pengujian memakai database `tokokasir_test` (lihat `phpunit.xml`), mencakup autentikasi & hak akses,
transaksi (harga dari server, stok kurang, transaksi ganda, uang kurang), shift & selisih kas,
barang masuk, penyesuaian stok, dan perhitungan laba.

---

## Pemetaan kebutuhan fungsional

| Kode | Use case | Aktor | Halaman / Kode |
|---|---|---|---|
| UC-01 | Login / Logout | Semua | `/login` · `Auth/LoginController` · middleware `role` |
| UC-02 | Transaksi penjualan | Kasir | `/kasir/transaksi` · `Kasir/PosController` · `resources/js/pos.js` · `Services/CheckoutService` |
| UC-03 | Pembayaran multi-metode | Kasir | Modal pembayaran (tunai / transfer / QRIS) |
| UC-04 | Cetak / simpan struk | Kasir | `/struk/{id}` (printer thermal 58/80 mm) · `/struk/{id}/pdf` |
| UC-05 | Kas awal & kas akhir (shift) | Kasir | `/kasir/shift/buka` · `/kasir/shift/tutup` · `Kasir/ShiftController` |
| UC-06 | Produk & kategori | Admin | `/admin/produk` · `/admin/kategori` |
| UC-07 | Supplier | Admin | `/admin/supplier` |
| UC-08 | Barang masuk | Admin | `/admin/barang-masuk` · `Services/PurchaseService` |
| UC-09 | Penyesuaian stok | Admin | `/admin/stok` (kartu stok + form penyesuaian) |
| UC-10 | Akun pengguna | Admin | `/admin/pengguna` |
| UC-11 | Laporan penjualan | Pemilik | `/pemilik/laporan/penjualan` (+ ekspor CSV) |
| UC-12 | Stok & produk terlaris | Pemilik | `/pemilik/laporan/stok` · Dashboard |
| UC-13 | Laba & aktivitas kasir | Pemilik | `/pemilik/laporan/laba-kasir` |

## Catatan desain

- **Skema database** mengikuti ERD pada proposal (Gambar 3), termasuk nama kolom dan primary key
  (`user_id`, `product_id`, …). Kolom tambahan diberi komentar `tambahan` di
  `database/migrations/2026_10_23_000001_create_tokokasir_tables.php`, mis. `kode_transaksi`,
  `client_ref`, `stok_sebelum/stok_sesudah`, `kas_seharusnya`, `selisih`.
- **Keandalan transaksi:** penyimpanan transaksi, barang masuk, dan penyesuaian stok berjalan dalam
  `DB::transaction` dengan penguncian baris produk (`lockForUpdate`) agar stok tidak minus saat dua
  kasir menjual barang yang sama. Setiap checkout membawa `client_ref` (UUID) sehingga klik ganda
  atau kirim ulang saat koneksi putus tidak membuat transaksi ganda.
- **Harga** selalu diambil dari database saat checkout; harga beli disimpan per item transaksi
  sehingga laba historis tidak berubah ketika harga beli produk diperbarui.
- **Semua perubahan stok** melewati `Services/StockService` dan tercatat di `stock_movements`.
  Stok produk tidak dapat diedit langsung dari form produk.
- **Selisih kas** = kas akhir − (kas awal + penjualan tunai). Transfer dan QRIS tidak masuk laci.
- **Pembayaran transfer/QRIS** dicatat manual tanpa payment gateway, sesuai ruang lingkup.
  Letakkan gambar QRIS statis toko di `public/images/qris.png` agar tampil di layar pembayaran,
  dan atur nomor rekening pada `TOKO_REKENING` di `.env`.
- **Barcode scanner** bekerja sebagai keyboard: kolom pencarian di halaman kasir selalu fokus,
  kode yang dipindai + Enter langsung masuk keranjang. Pintasan: `F2` cari, `F9` bayar, `Esc` tutup.
- **Keamanan:** password di-hash (bcrypt), CSRF, validasi input di server, pembatasan percobaan login,
  akun nonaktif langsung dikeluarkan dari sesi, dan URL dipaksa HTTPS saat `APP_ENV=production`.

## Identitas toko

Atur pada `.env`:

```dotenv
TOKO_NAMA="TokoKasir Mart"
TOKO_ALAMAT="Jl. Ahmad Dahlan No. 76, Kediri"
TOKO_TELEPON="0812-3456-7890"
TOKO_REKENING="BCA 1234567890 a.n. TokoKasir Mart"
TOKO_FOOTER="Terima kasih atas kunjungan Anda"
```

## Struktur penting

```
app/
├── Http/Controllers/{Auth,Kasir,Admin,Pemilik}/   controller per aktor
├── Http/Middleware/EnsureRole.php, EnsureOpenShift.php
├── Models/                                       model sesuai ERD
├── Services/  CheckoutService, PurchaseService, StockService, ReportService
└── Support/   DateRange (filter periode laporan), Icons
resources/
├── views/{kasir,admin,pemilik,receipts,components,layouts}
└── js/  pos.js (halaman kasir), purchase-form.js, charts.js
database/seeders/   UserSeeder, CatalogSeeder, DemoHistorySeeder
tests/Feature/      AuthTest, CheckoutTest, ShiftTest, InventoryTest, ReportTest
```

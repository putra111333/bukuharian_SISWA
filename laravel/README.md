# SIMANIS

**Sistem Informasi Akademik Dan Informasi Siswa** — portal akademik dengan akses terpisah untuk yayasan/super admin, pengelola sekolah, dan keluarga/siswa.

## Menjalankan secara lokal

Pastikan PHP 8.2+, Composer, Node.js, dan npm tersedia. Dari folder `laravel`:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
```

Di terminal lain, jalankan:

```powershell
php artisan serve
```

Buka `http://127.0.0.1:8000`. Untuk menyiapkan aset produksi, jalankan `npm run build`; tes aplikasi dapat dijalankan dengan `php artisan test`.

Jika file `.env` sudah ada, jangan menimpanya: perbarui `APP_NAME=SIMANIS`, `APP_LOCALE=id`, serta pastikan `DB_CONNECTION` dan `DB_DATABASE` menunjuk ke basis data yang ingin digunakan.

## Persiapan hosting produksi

Panduan ini berlaku umum untuk hosting Linux yang mendukung PHP 8.2+, Composer, Node.js/npm saat proses build, dan MySQL/MariaDB. Ikuti petunjuk penyedia hosting untuk membuat database serta pengguna database; jangan memakai akun database `root`.

1. Unggah kode ke server dan arahkan document root/domain ke folder `laravel/public` saja. Jangan jadikan root proyek sebagai document root karena berisi `.env`, source, dan dependensi.
2. Siapkan database MySQL/MariaDB kosong dan kredensial khusus aplikasi. Di server, buat `.env` dari `.env.example` lalu atur nilai produksi berikut. Jangan menyalin `.env` lokal atau mengunggahnya ke repositori:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domain-anda.example
   APP_TIMEZONE=Asia/Jakarta
   APP_KEY=

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nama_database
   DB_USERNAME=pengguna_aplikasi
   DB_PASSWORD=password_database_yang_kuat

   SESSION_DRIVER=database
   SESSION_SECURE_COOKIE=true
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   ```

   Ganti domain, host, nama database, username, dan password sesuai informasi dari penyedia hosting. Pastikan HTTPS sudah aktif sebelum mengaktifkan cookie secure.
3. Instal dependensi dan siapkan skema serta aset produksi dari folder proyek:

   ```sh
   composer install --no-dev --prefer-dist --optimize-autoloader
   npm ci
   npm run build
   php artisan key:generate
   php artisan migrate --force
   php artisan optimize
   ```

   Jalankan `key:generate` hanya untuk instalasi baru saat `APP_KEY` masih kosong. Simpan kunci tersebut dan pertahankan saat deployment berikutnya karena menggantinya dapat membuat data terenkripsi lama tidak bisa dibaca. Jangan jalankan `db:seed` di hosting produksi; seeder berisi data contoh dan akun demo. Jangan jalankan `composer run setup` untuk deployment.
4. Pastikan proses PHP/web server memiliki izin tulis ke `storage` dan `bootstrap/cache`, tetapi folder lain tidak perlu dibuka untuk publik. Pada server yang mengelola pengguna/grup Linux sendiri, sesuaikan pemilik dan grup folder tersebut dengan akun PHP; contoh izin grup:

   ```sh
   chmod -R ug+rwX storage bootstrap/cache
   ```
5. Buat akun super admin pertama dengan username unik dan password kuat menggunakan prosedur Tinker yang dijelaskan pada bagian akun demo di bawah (jangan gunakan kredensial contoh). Bila hosting tidak menyediakan akses CLI/Tinker, siapkan akun melalui prosedur administrasi server yang aman sebelum membuka aplikasi ke publik.
6. Jalankan pemeriksaan konfigurasi dan deployment:

   ```sh
   php artisan app:deployment-check
   ```

   Perintah ini harus selesai dengan exit code `0`. Jika gagal, perbaiki butir yang dilaporkan. Pemeriksaan ini memvalidasi konfigurasi produksi, HTTPS, database dan tabel utama, akun demo, izin tulis, dan manifest aset; ini bukan pengganti tes alur aplikasi di server.
7. Sebelum go-live, verifikasi halaman login dan semua peran, HTTPS, unggah/unduh berkas, serta log aplikasi. Atur backup terjadwal database **dan** berkas privat `storage/app/private`; uji pemulihannya. Atur pengiriman email SMTP jika diperlukan.

`SESSION_DRIVER=database`, `CACHE_STORE=database`, dan `QUEUE_CONNECTION=database` memerlukan database yang sudah dimigrasikan. Jika aplikasi/hosting memakai antrean database, siapkan proses worker persisten dengan `php artisan queue:work --tries=3` dan pantau prosesnya. Jangan menjalankan perintah worker sebagai bagian dari request web. Setelah mengubah `.env` pada deployment berikutnya, jalankan `php artisan optimize` lagi.

## Memasang SIMANIS sebagai aplikasi desktop

SIMANIS dapat dipasang sebagai aplikasi web di Microsoft Edge atau Google Chrome. Setelah aplikasi tersedia lewat HTTPS (atau berjalan lokal pada `localhost`), buka alamat SIMANIS, masuk ke halaman login, lalu pilih **Pasang SIMANIS di desktop** jika tombol pemasangan tersedia. Konfirmasi pemasangan; browser akan menambahkan aplikasi yang terbuka dalam jendela tersendiri dan opsi pintasan desktop/start menu bergantung pada browser atau sistem operasi. Tombol juga tersedia di dashboard setelah login.

Jika tombol belum muncul, pastikan situs menggunakan HTTPS, muat ulang halaman, dan gunakan Edge/Chrome versi terbaru. Instalasi tidak membuat aplikasi bisa digunakan tanpa server: hosting atau server lokal tetap harus berjalan dan perangkat harus terhubung ke jaringan. SIMANIS sengaja tidak menyimpan halaman akademik atau data login untuk akses offline. Di Windows, pintasan yang sudah dipasang dapat dibuka tanpa tab/address bar browser.

## Akun demo lokal

Seeder menyiapkan akun berikut. Password setiap akun sama dengan username:

| Peran | Username | Password |
| --- | --- | --- |
| Yayasan / Super Admin | `yayasan123` | `yayasan123` |
| Admin sekolah | `admin123` | `admin123` |
| Orang tua siswa demo | `user123` | `user123` |

Akun demo ditujukan untuk pengembangan lokal. Jangan mempublikasikan kredensial demo atau menggunakannya untuk data produksi. Akun pribadi untuk admin sekolah dapat dibuat oleh yayasan; akun siswa dan orang tua dibuat sekolah saat mendaftarkan siswa. Password akun disimpan dalam bentuk hash.

Seeder menolak dijalankan di lingkungan `production`; untuk deployment, jalankan migrasi tanpa `--seed` dan buat akun yayasan pertama secara manual dengan password kuat. Panel demo juga hanya ditampilkan di lingkungan lokal dan pengujian.

Untuk membuat akun yayasan pertama di deployment, jalankan `php artisan tinker`, lalu buat pengguna dengan kredensial rahasia pilihanmu:

```php
\App\Models\User::create([
    'name' => 'Yayasan',
    'username' => 'yayasan-utama',
    'password' => \Illuminate\Support\Facades\Hash::make('GANTI-DENGAN-PASSWORD-KUAT'),
    'role' => 'super_admin',
]);
```

Ganti username dan placeholder password sebelum menjalankannya. Setelah login, yayasan dapat membuat akun admin sekolah dari dashboard.

## Peran dan akses

- **Yayasan / Super Admin:** melihat jumlah sekolah dan siswa, mendaftarkan serta memperbarui sekolah, dan membuat akun kepala sekolah, guru, dan tata usaha.
- **Admin sekolah:** mengelola data siswa dan akun pribadi, membuat kelompok kelas, memasukkan tugas dan jadwal, mencatat nilai serta presensi, menulis catatan siswa, dan mengunduh hasil tugas dari sekolahnya.
- **Siswa / orang tua:** melihat profil akademik siswa yang ditautkan ke akunnya, nilai, tugas, jadwal, presensi, tagihan pribadi, kegiatan, pengumuman, dan catatan yang dibagikan sekolah; akun dapat mengunggah hasil tugas pribadi.

Setiap catatan akademik dan berkas pengumpulan tugas dibatasi berdasarkan sekolah dan/atau siswa tertaut. Unggahan disimpan di disk privat, bukan direktori publik. Akun demo menggunakan kredensial bersama sehingga hanya untuk data contoh lokal; gunakan akun individual dan password kuat pada penggunaan sebenarnya.

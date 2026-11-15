# Afika Residence

Project Laravel 12, PHP 8.2+, MySQL. Halaman publik berada di `/`, dashboard di `/admin`.

## Menjalankan lokal

Database `.env` sudah menggunakan `web_afika`, host `127.0.0.1`, port `3306`, username `root`, password kosong mengikuti konfigurasi lokal Laragon. Sesuaikan jika konfigurasi MySQL berubah.

```sh
php artisan serve
```

Buka http://127.0.0.1:8000. Aset website menggunakan CSS dan SVG langsung sehingga tidak memerlukan npm atau Vite untuk dijalankan.

Login awal: username `admin`, password `Afika123!`. Ganti password melalui dashboard sebelum digunakan secara publik.

## Konten

- Dashboard: tambah/edit/hapus properti, foto, harga, spesifikasi, deskripsi, status tayang/draft.
- Konten website: judul, deskripsi, profil perusahaan, alamat, WhatsApp (628xxx), Instagram, unggah logo.
- Data properti berlabel Contoh dan ilustrasi rumah adalah materi pratinjau, bukan informasi proyek aktual. Ganti dengan data sebenarnya.
- Identitas teks dan warna mengikuti referensi logo. Unggah berkas logo asli melalui dashboard untuk memakai logo persis dari referensi.
- Nomor WhatsApp dan alamat awal dikosongkan agar tidak menampilkan kontak fiktif.

## Instalasi ulang

```sh
composer install
cp .env.example .env
php artisan key:generate
# Atur DB_CONNECTION=mysql dan kredensial DB_DATABASE=web_afika pada .env
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Seeder tidak mereset akun atau menimpa konten yang sudah ada. Semua akun yang dibuat untuk aplikasi ini merupakan administrator; tidak tersedia registrasi publik.

## Pengujian

```sh
php artisan test
```

Tes memakai SQLite in-memory agar database MySQL lokal tidak berubah. Untuk hosting gunakan document root `public`, HTTPS, `APP_DEBUG=false`, dan konfigurasi database server sebenarnya.

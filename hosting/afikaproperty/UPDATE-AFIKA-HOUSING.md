Pembaruan katalog Afika — 15 November 2026

Database lokal sudah dapat diperbarui dengan:

```sh
php artisan migrate --force
php artisan db:seed --class=AfikaHousingSeeder --force
```

Untuk hosting melalui phpMyAdmin, pilih database website/FAQ yang digunakan bot lalu impor `update-afika-housing.sql`. Tabel website harus sudah tersedia. Skrip ini otomatis memeriksa dan menambahkan kolom FAQ `faq_type`, `is_public`, dan `is_ai` yang belum ada, sehingga tidak perlu mengimpor pembaruan audience secara terpisah untuk katalog ini. Skrip katalog dapat diimpor ulang: kolom dan catatan migrasi diperiksa terlebih dahulu, data dicocokkan berdasarkan nama perumahan dan pertanyaan FAQ.

Upload juga kode model Property, AdminController, tampilan home/property/admin property, CSS public, migrasi, dan berkas seeder/data. SQL saja memperbarui data, sedangkan tampilan memerlukan kode tersebut. Setelah upload, jalankan `php artisan optimize:clear` jika menggunakan cache Laravel.

Isi katalog: Afika Park tipe 36/60, Afika Land tipe 36/54, Afika Residence tipe 36; tipe 60 Residence tidak tersedia. Harga cash, DP normal/promo, biaya akad, dan empat tenor angsuran mengikuti pesan klien. Foto lama yang sudah diunggah dipertahankan. Foto pengganti sementara memakai pengaturan foto website.

Luas tanah dan jumlah kamar belum diberikan. Nilai 0 merupakan penanda belum diisi dan tidak ditampilkan sebagai spesifikasi di kartu katalog baru. Harga utama merupakan harga cash terendah, sedangkan rincian ada pada setiap tipe.

FAQ baru aktif untuk website dan AI. FAQ trial yang pertanyaan dan jawabannya masih sama persis dengan berkas trial dinonaktifkan; jawaban yang telah dikoreksi admin dipertahankan. Entri properti contoh disembunyikan. Informasi lain tidak dihapus.

Skrip/seeder menimpa kembali isi katalog dan FAQ katalog jika dijalankan ulang. Perubahan harga berikutnya melalui admin perlu diikuti pembaruan jawaban FAQ terkait agar jawaban bot tetap sesuai. Bot dengan database bersama langsung membaca FAQ aktif tersebut; pemasangan dengan database FAQ terpisah perlu sinkronisasi FAQ.

Jika impor sebelumnya berhenti dengan `#1054 Unknown column is_ai`, impor ulang seluruh versi terbaru `update-afika-housing.sql` dari awal. Skrip memeriksa kolom sebelum menambahkannya dan memperbarui data berdasarkan nama/pertanyaan agar tidak membuat entri katalog ganda.

# Catatan internal: 30 FAQ trial

Data pada `database/seeders/data/afika_faq_trial.json` merupakan simulasi atas permintaan pemilik untuk menguji Fika. Bukan hasil verifikasi informasi proyek sebenarnya. Semua FAQ diaktifkan agar bisa dipakai saat trial setelah disinkronkan.

Contoh angka yang perlu diperiksa dan diganti sebelum dipakai sebagai penawaran kepada pelanggan:

- Jam kantor Senin–Sabtu 08.00–17.00 WIB, Minggu 09.00–15.00 WIB.
- Tipe 36/72: Rp350 juta, tipe 45/90: Rp450 juta, tipe 60/120: Rp650 juta.
- Simulasi DP 20%, booking fee Rp5 juta, tunai bertahap sampai 12 bulan.
- Spesifikasi material, listrik, air, fasilitas dan pengawasan kawasan adalah contoh.
- Target serah terima siap huni 30 hari kerja, inden 6 bulan, masa pemeliharaan 90 hari adalah contoh, bukan komitmen proyek terverifikasi.

Legalitas spesifik, ketersediaan unit, bunga bank, refund booking, dan biaya transaksi tidak dijanjikan tanpa konfirmasi dokumen/penawaran. Jawaban mengarahkan pemeriksaan kepada tim.

Koreksi melalui Admin → FAQ. Sesudah perubahan, klik Sinkronkan FAQ ke WhatsApp. Seeder hanya menambahkan pertanyaan yang belum ada dan tidak menimpa jawaban yang sudah diedit. Seeder ini tidak dimasukkan dalam DatabaseSeeder default.

```sh
php artisan db:seed --class=FaqTrialSeeder
```

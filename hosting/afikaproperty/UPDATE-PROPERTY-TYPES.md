Tombol dan detail tipe rumah

- Beranda: klik tombol tipe untuk membuka /properti/{id}/tipe/{nomor-tipe}.
- Halaman tipe hanya menampilkan harga, DP, akad, dan angsuran tipe tersebut. Tipe tidak tersedia tetap menampilkan statusnya tanpa menawarkan harga.
- Admin > Properti > Edit tipe [nama perumahan], lalu pilih Edit tipe 36/54/60. Kolom detail tipe, harga cash, DP promo/normal, akad, dan empat tenor dapat diperbarui di sana.
- Harga mulai di beranda otomatis mengikuti harga cash terendah dari tipe yang tersedia setelah formulir disimpan.
- FAQ harga tetap dikelola melalui menu FAQ; perbarui jawabannya juga jika harga diubah agar jawaban AI mengikuti.

Penerapan:
1. Upload isi update-property-types.zip sesuai struktur aplikasi Laravel.
2. Untuk domain dengan document root public_html, file public/css/* masuk ke public_html/css, bukan folder public_html/public/css.
3. Jalankan php artisan optimize:clear setelah upload untuk membersihkan cache route/view/config. Buka ulang dengan Ctrl+F5.
4. Tidak ada perubahan skema atau SQL tambahan. Kolom house_types dari pembaruan katalog sebelumnya harus sudah ada. Jangan mengimpor ulang SQL katalog hanya untuk pembaruan tombol ini karena akan mengembalikan harga ke data awal klien.

Paket hanya disiapkan lokal, belum diunggah ke hosting.

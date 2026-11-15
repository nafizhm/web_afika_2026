# Update editor tampilan website tanpa terminal

1. Push/pull seluruh perubahan aplikasi melalui Git, termasuk config/website.php, controller, route, view dan CSS.
2. Jika config Laravel pernah di-cache, hapus hanya file bootstrap/cache/config.php melalui File Manager agar config/website.php baru terbaca. Jika route lama masih di-cache, hapus file bootstrap/cache/routes-v7.php bila ada. Jangan hapus folder bootstrap/cache atau .gitignore-nya.
3. Karena document root hosting terpisah, salin CSS terbaru dari public/css/ ke public_html/css/. Pastikan public_html/storage terhubung ke storage/app/public Laravel yang sama. Upload gambar melalui editor memerlukan folder storage/app/public yang dapat ditulis PHP.
4. Buka Admin > Tampilan Website. Kelompok isian: bagian yang tampil, identitas & navigasi, beranda, tentang & galeri, hunian, lokasi, FAQ, kontak & footer, pesan WhatsApp, foto dan gaya.
5. Profil Perusahaan tetap menjadi sumber nama perusahaan, logo, favicon, alamat, email, telepon, WA CS dan embed Maps. Properti dan pertanyaan/jawaban FAQ tetap di menu masing-masing.
6. Tidak ada migration atau SQL tambahan untuk editor ini; nilai baru disimpan di tabel settings. Tidak perlu update webhook atau worker VPS.

Upload foto mengungguli URL yang diisi pada penyimpanan yang sama. Untuk mengganti foto upload dengan eksternal, isi URL baru. Nilai FAQ 0 menampilkan semua FAQ publik aktif; FAQ internal tetap tersembunyi.

Editor mengatur isi, foto, warna utama/tombol/latar, pilihan font, dan visibilitas bagian. Tata letak responsive dan komponen tetap memakai desain website yang ada; editor ini bukan pembuat layout bebas.

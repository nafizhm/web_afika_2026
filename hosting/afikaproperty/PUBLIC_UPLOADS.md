# Upload langsung ke folder publik — tanpa symbolic link

Semua upload gambar (logo, favicon, foto properti dan foto website) sekarang memakai disk public yang mengarah ke public_path('storage'). Dengan bootstrap/app.php hosting saat ini, hasilnya:

/home/afikaproperty/public_html/storage/branding/
/home/afikaproperty/public_html/storage/properties/
/home/afikaproperty/public_html/storage/website/

Langkah hosting tanpa terminal:
1. Push/pull perubahan config/filesystems.php dan app/Providers/AppServiceProvider.php.
2. Di File Manager, buat folder biasa public_html/storage jika belum ada. Pastikan PHP dapat menulis folder itu (biasanya 755 atau 775 sesuai kepemilikan hosting).
3. Jika public_html/storage sudah berupa symlink, jangan hapus target/data aslinya. Bila link tersebut bekerja, upload tetap berjalan. Untuk memakai folder biasa, ganti nama link menjadi storage-link-lama, buat folder storage, kemudian salin isi sumber lama ke folder baru.
4. Salin isi /home/afikaproperty/repositories/web_afika_2026/storage/app/public/ ke /home/afikaproperty/public_html/storage/. Pertahankan subfolder branding, properties dan website. Jangan menyalin folder app/private, logs, .env atau file aplikasi ke area publik.
5. Jika ada bootstrap/cache/config.php, hapus file cache itu melalui File Manager. Tidak perlu menjalankan storage:link atau SQL.
6. Upload satu logo dari Profil Perusahaan, simpan, lalu periksa public_html/storage/branding/ dan buka website.

Database dan alamat /storage/... tidak berubah. File upload publik tetap di-ignore Git agar pembaruan kode tidak menimpa gambar hosting.

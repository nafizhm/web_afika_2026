Perbaikan tampilan FAQ admin

Pemeriksaan URL publik hosting menemukan:
- /css/admin-controls.css: 404 (file tidak tersedia).
- /css/admin.css: versi lama tanpa aturan dropdown FAQ.
- /css/admin-theme.css: berbeda dari versi lokal.
- CSS publik menggunakan Cache-Control max-age=604800 (7 hari).

Paket update-faq-style.zip berisi tiga CSS, layout admin, serta form dan daftar FAQ. Ini paket tampilan, bukan pembaruan database atau controller.

1. Ekstrak ZIP di komputer. Upload resources/views ke folder resources/views aplikasi Laravel di hosting, sesuai struktur ZIP.
2. Upload TIGA file dari public/css ke folder CSS yang dilayani domain. Jika document root domain adalah public_html, tujuan biasanya public_html/css; jika domain menunjuk public Laravel, tujuannya public/css. Ganti file yang lama.
3. Pastikan https://afikaproperty.com/css/admin-controls.css dapat dibuka dan bukan 404.
4. Jalankan php artisan view:clear dari folder aplikasi jika terminal tersedia.
5. Buka ulang halaman FAQ dengan Ctrl+F5. Jika memakai CDN, bersihkan cache CDN untuk tiga URL CSS tersebut.

Layout terbaru memberi versi URL CSS berdasarkan hash isi file, sehingga perubahan CSS mendapat URL baru walaupun timestamp hasil upload tetap sama.

Tidak perlu impor SQL. Paket ini belum diunggah otomatis ke hosting.

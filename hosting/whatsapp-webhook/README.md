# Webhook WhatsApp Baileys — hosting PHP mandiri

PHP 8.2+, PDO MySQL, mbstring, HTTPS. Tidak membutuhkan Laravel/Composer pada hosting ini.

1. Upload `webhook.php` dan `Receiver.php` ke direktori publik yang sama, contoh `public_html/`.
2. Salin `whatsapp-config.example.php` menjadi `whatsapp-config.php` di direktori **induk** public_html, sehingga tidak dapat diunduh dari web. Isi kredensial MySQL, `account=6282221992911`, dan secret acak minimal 32 karakter. Secret harus sama dengan environment `AFIKA_WEBHOOK_SECRET` di VPS. Buat dengan `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`.
3. Jika lokasi config berbeda, set environment hosting `AFIKA_WEBHOOK_CONFIG` ke path absolut file konfigurasi.
4. Import `schema.sql` ke database tujuan bila tabel `whatsapp_messages` belum ada. Website Laravel sudah membuat tabel melalui migration. Gunakan database yang sama dengan website agar pesan terlihat di halaman admin; bila database berbeda, pesan hanya tersimpan pada database hosting tersebut.
5. Set `AFIKA_WEBHOOK_URL=https://domain-anda/webhook.php` pada proses Baileys VPS. Endpoint hanya menerima POST JSON dengan header `Authorization: Bearer SECRET`.
6. Di VPS gunakan `afika-forwarder.mjs`. Buat satu instance `createAfikaForwarder()` di luar fungsi reconnect, kemudian panggil `afika.attach(sock)` setiap socket Baileys dibuat. Panduan lengkap ada di `integrations/baileys/README.md` pada project Laravel.

Jika hosting membuang Authorization, atur penerusan header di web server. Apache dengan mod_rewrite mungkin membutuhkan `RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]` pada `.htaccess`.

Jangan upload config database, file SQL, atau file environment ke area publik. Pada paket ini hanya `webhook.php` dan `Receiver.php` yang perlu masuk ke area publik.

Pesan teks/caption dan metadata pesan masuk disimpan. Berkas foto/audio/video belum diunduh. Pesan keluar dan status WhatsApp diabaikan. Batas request 2 MiB. Pesan dengan ID yang sama pada account dan chat yang sama tidak diduplikasi saat retry.

Respons: 201 tersimpan, 200 duplikat, 202 diabaikan, 401 secret salah, 422 format salah, 413 payload terlalu besar, 503 konfigurasi belum siap, 500 database bermasalah. Forwarder menyimpan pesan yang belum terkonfirmasi di outbox VPS untuk retry.

# Worker AI VPS untuk Afika

**Pembaruan:** webhook sekarang memakai dua file: `wa-webhook-test.php` dan `wa-webhook-config.php` dalam folder hosting yang sama. Konfigurasi DB/token/AI berada di file config; saat update berikutnya cukup ganti file webhook. Lihat `hosting/WEBHOOK_UPDATE.md`.

Hosting tetap satu file PHP. VPS menjalankan worker Node 22 yang memeriksa antrean setiap 2 detik, memanggil Kenari, lalu mengirim lewat gateway lokal session `cobadong`. Tidak membuka koneksi MySQL dari VPS ke hosting. Ini menghilangkan jeda cron satu menit; waktu model AI dan pengiriman WA tetap berlaku. Worker saat ini satu proses dan memproses pesan berurutan.

## Tahap 1: update hosting

1. Backup file hosting `wa-webhook-test.php`.
2. Upload versi terbaru dari `public/wa-webhook-test.php`. Pertahankan konfigurasi database hosting yang sudah berhasil.
3. Buat token acak di terminal VPS:

```sh
openssl rand -hex 32
```

4. Isi hasilnya pada `$workerToken = 'TOKEN_ACAK';` di file PHP hosting. Jangan kirim token ke chat atau meletakkannya dalam URL.
5. Pastikan `$aiConfig['worker_mode'] = 'remote'`. Mode ini menonaktifkan pemrosesan oleh cron PHP lama, sehingga cron dan VPS tidak membalas pesan yang sama. Boleh membiarkan cron lama terpasang selama pemasangan; lognya hanya menyatakan mode VPS aktif. Job baru tetap ditampung selama VPS belum berjalan.

Key/model Kenari di hosting tidak diperlukan untuk remote worker. Nomor, client ID, konfigurasi DB dan flag enabled pada hosting tetap diperlukan. `$webhookToken` untuk autentikasi pesan masuk berbeda dari `$workerToken` untuk antrean.

Tabel baru `whatsapp_ai_remote` dibuat otomatis. Status `unknown` lama tidak di-reset atau dikirim ulang otomatis.

## Tahap 2: upload file worker VPS

Upload `afika-ai-worker.mjs` ke:

```text
/var/www/gateway-test/afika-ai-worker.mjs
```

Upload `.env.afika-ai.example` menjadi:

```text
/var/www/gateway-test/.env.afika-ai
```

Isi file environment ini di VPS:

```dotenv
AFIKA_WEBHOOK_URL=https://afikaproperty.com/wa-webhook-test.php
AFIKA_WORKER_TOKEN=TOKEN_YANG_SAMA_DENGAN_HOSTING
KENARI_API_KEY=API_KEY_KENARI
KENARI_MODEL=glm-4-6v-flash:free
KENARI_BASE_URL=https://kenari.id/v1
WA_GATEWAY_URL=http://127.0.0.1:3000
WA_GATEWAY_CLIENT_ID=cobadong
WA_GATEWAY_TOKEN=
AFIKA_POLL_MS=2000
AFIKA_ACK_DIR=/var/www/gateway-test/data-test/afika-ai-acks
```

Tidak perlu mengubah source atau session Baileys. Worker adalah proses PM2 tambahan. Jangan menyalin `.env.afika-ai` ke direktori publik hosting atau mengunggahnya ke Git.

## Tahap 3: jalankan

Di terminal VPS:

```sh
cd /var/www/gateway-test
chmod 600 .env.afika-ai
node --env-file=.env.afika-ai afika-ai-worker.mjs
```

Setelah muncul `Afika AI worker running. Gateway session: cobadong`, kirim pesan teks dari nomor lain. Job yang sebelumnya pending juga akan diproses. Hentikan pengujian foreground dengan Ctrl+C setelah memastikan hasil, lalu jalankan satu proses PM2:

```sh
pm2 start /var/www/gateway-test/afika-ai-worker.mjs --name afika-ai-worker --cwd /var/www/gateway-test --node-args="--env-file=/var/www/gateway-test/.env.afika-ai" --kill-timeout 120000
pm2 logs afika-ai-worker --lines 30
```

Jangan menjalankan foreground dan PM2 bersamaan. Jika nama PM2 sudah ada, gunakan `pm2 restart afika-ai-worker` setelah memperbarui environment file. Untuk menjalankan kembali setelah reboot pada setup PM2 startup yang sudah ada, jalankan `pm2 save` setelah memeriksa daftar proses.

## Tahap 4: verifikasi dan hapus cron lama

- Pastikan pesan sungguhan dibalas di WA.
- DB `whatsapp_ai_replies`: status `queued`, `gateway_job_id` terisi. Queued berarti diterima antrean gateway, belum bukti delivery WA.
- Hapus cron hosting `wa-webhook-test.php --worker` setelah uji berhasil.
- Key Kenari hosting boleh dikosongkan karena panggilan AI sekarang dari VPS.
- Gateway lokal dipakai worker, sehingga worker tidak membutuhkan akses port 3000 dari hosting. Perubahan firewall dapat dilakukan terpisah setelah memastikan aplikasi lain tidak bergantung pada akses tersebut.

## Keandalan

Token worker minimal 32 karakter dan selalu lewat HTTPS Authorization Bearer. Setiap job diklaim dengan lease 5 menit; token lease lama tidak dapat mengubah klaim baru. Generasi AI gagal dicoba maksimal 3 kali. Job yang kehilangan worker sebelum fase kirim bisa diklaim lagi setelah lease habis. Job yang putus ketika mengirim ditahan sebagai unknown agar tidak otomatis mengirim dua kali. Jika prepare_send belum terkonfirmasi, worker tidak mengirim pesan.

Hasil gateway yang belum terkonfirmasi tersimpan dalam folder ACK VPS, lalu dicoba disimpan ulang ke hosting tanpa mengirim ulang WA. File `.review` berarti perlu pemeriksaan manual karena lease sudah habis. Jangan hapus folder ACK saat deploy. Crash sebelum ACK tersimpan tetap mungkin menghasilkan unknown; gateway belum mendukung idempotency key pengiriman.

Grup, media, pesan lama dan JID LID tanpa alternatif nomor masih mengikuti filter webhook sebelumnya. Trial ini belum memakai data properti atau riwayat percakapan. Jam VPS/hosting perlu sinkron untuk filter pesan masuk lima menit.

## Tes lokal tanpa pesan WhatsApp nyata

```sh
php artisan test
node --test integrations/baileys/afika-ai-worker.test.mjs
php tests/manual/remote-ai-mysql.php
```

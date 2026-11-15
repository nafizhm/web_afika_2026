# Sakelar AI di admin Afika

Admin → Pengaturan AI: `/admin/pengaturan-ai`. Tombol mengubah status pada database hosting melalui endpoint worker bertoken; bukan hanya mengubah status lokal. Memakai `FAQ_SYNC_URL` dan `FAQ_SYNC_TOKEN` yang sudah digunakan untuk sinkronisasi FAQ.

## Pemasangan update

1. Upload `hosting/wa-webhook-test.php` dari paket ke hosting Afika, menggantikan file webhook lama. File `wa-webhook-config.php` tetap dipakai, tidak perlu diubah.
2. Upload `vps/afika-ai-worker.mjs` ke `/var/www/gateway-test/afika-ai-worker.mjs`, lalu jalankan `pm2 restart afika-ai-worker` di VPS. Tidak perlu restart gateway Baileys.
3. Buka Pengaturan AI. Jika status Belum terkonfirmasi, periksa bahwa PHP terbaru terpasang, worker_mode remote, dan token admin sama dengan workerToken hosting.
4. Klik Nonaktifkan AI saat admin bertugas; Aktifkan AI pada malam hari/hari libur. Tidak perlu restart PM2 setiap mengganti sakelar.

## Perilaku

- Status dibaca langsung dari hosting. Akses gagal ditampilkan sebagai belum terkonfirmasi, tidak dianggap berhasil mematikan AI.
- Status awal mempertahankan AI aktif untuk instalasi yang sebelumnya sudah berjalan. Setelah update, pilih status yang diinginkan melalui admin.
- AI nonaktif: pesan masuk tetap disimpan; pesan baru tidak masuk antrean balasan. Pending dan processing dibatalkan. Job yang dibatalkan tidak dilanjutkan ketika AI kembali aktif.
- Worker memeriksa izin melalui prepare_send setelah menyiapkan jawaban. Jika AI dimatikan selama generasi, worker tidak mengirim jawaban tersebut.
- Pesan yang sudah masuk tahap sending atau antrean gateway sebelum sakelar dimatikan mungkin tetap terkirim; sakelar ini tidak menarik kembali pesan dari WhatsApp/gateway.
- Status disimpan di tabel `whatsapp_ai_control`, dibuat otomatis. Konfigurasi enabled pada file PHP tetap merupakan pengaturan utama instalasi dan harus true agar menu kontrol remote dapat dipakai.
- Jadwal malam/hari libur belum otomatis: sakelar dioperasikan manual sesuai jadwal kerja admin.

Paket update tidak berisi kredensial database atau API key.

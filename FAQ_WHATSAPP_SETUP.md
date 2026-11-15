# Menghubungkan FAQ admin dengan Fika

**Pembaruan:** webhook sekarang memakai dua file: `wa-webhook-test.php` dan `wa-webhook-config.php` dalam folder hosting yang sama. Konfigurasi DB/token/AI berada di file config; saat update berikutnya cukup ganti file webhook. Lihat `hosting/WEBHOOK_UPDATE.md`.

Admin Laravel lokal dan webhook hosting menggunakan database terpisah. Menyimpan FAQ di admin saja tidak mengubah salinan pada hosting. Gunakan tombol **Sinkronkan FAQ ke WhatsApp** setelah perubahan konten.

## Pemasangan

1. Upload ulang `storage/app/afika-ai-vps/hosting/wa-webhook-test.php` ke hosting. Pertahankan kredensial DB dan workerToken hosting yang sudah berhasil. Endpoint worker sekarang mendukung `sync_faqs` dan mengirim FAQ bersama job. Tabel `faqs` dibuat otomatis jika belum ada.
2. Upload ulang `integrations/baileys/afika-ai-worker.mjs` ke `/var/www/gateway-test/afika-ai-worker.mjs` di VPS, kemudian `pm2 restart afika-ai-worker`.
3. Konfigurasi `.env` project admin:

```dotenv
FAQ_SYNC_URL=https://afika.berkahtech.biz.id/wa-webhook-test.php
FAQ_SYNC_TOKEN=TOKEN_YANG_SAMA_DENGAN_workerToken_HOSTING
```

Nilai lokal telah disiapkan menggunakan token pada salinan file deployment jika tersedia. Jangan menampilkan token. Jika token hosting berubah, perbarui konfigurasi sinkronisasi serta worker VPS. Jalankan `php artisan config:clear` setelah mengubah `.env`.

4. Buka `/admin/faq`, lengkapi pertanyaan/jawaban, aktifkan FAQ yang sudah siap, lalu klik **Sinkronkan FAQ ke WhatsApp**. Konfirmasi bahwa salinan FAQ hosting akan diganti dengan data dari admin ini. Maksimal 100 FAQ per sinkronisasi; payload maksimal 2 MiB. Data lokal tetap tersimpan jika koneksi gagal.
5. Pastikan muncul pesan sukses sinkronisasi. Uji pesan WhatsApp BARU, misalnya pertanyaan jam operasional. Job dengan jawaban lama yang sudah tersimpan tidak dibuat ulang secara otomatis.

## Cara kerja

Sinkronisasi mengganti snapshot FAQ di hosting secara transaksional, termasuk status aktif/nonaktif. Worker hanya menerima FAQ aktif yang tidak mengandung placeholder `[seperti ini]`. FAQ nonaktif tidak ikut menjadi konteks. Untuk menghapus FAQ dari jawaban, nonaktifkan/hapus di admin lalu sinkronkan lagi.

Hosting memilih hingga 20 FAQ, mengutamakan kemiripan kata dengan pertanyaan, dengan batas total 40.000 karakter. Pertanyaan sama persis (mengabaikan kapital/tanda baca) memakai jawaban resmi langsung. Untuk parafrasa, Kenari menerima konteks FAQ dengan instruksi mendahulukan fakta di dalamnya. Jika fakta tidak tersedia, Fika mengarahkan ke admin. Penyusunan bahasa oleh AI tetap perlu diuji memakai contoh pertanyaan pelanggan.

Nama Fika tetap menjadi identitas utama pada instruksi worker. Nama juga boleh dicantumkan dalam FAQ. Variasi pertanyaan “nama mu” kini dikenali.

Sinkronisasi perlu diulang setiap kali isi/status FAQ berubah. Tidak ada API key Kenari atau kredensial database dalam data FAQ yang dikirim ke worker.

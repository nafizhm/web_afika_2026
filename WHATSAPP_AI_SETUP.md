# Tahap awal balasan AI WhatsApp

**Pembaruan:** webhook sekarang memakai dua file: `wa-webhook-test.php` dan `wa-webhook-config.php` dalam folder hosting yang sama. Konfigurasi DB/token/AI berada di file config; saat update berikutnya cukup ganti file webhook. Lihat `hosting/WEBHOOK_UPDATE.md`.

Untuk trial yang memakai worker VPS tanpa jeda cron, ikuti [AI_VPS_SETUP.md](integrations/baileys/AI_VPS_SETUP.md). Versi webhook terbaru memakai `worker_mode=remote` secara default; petunjuk cron di bawah hanya berlaku jika mode sengaja diubah menjadi `cron`.

Satu file hosting tetap `public/wa-webhook-test.php`. Memerlukan PHP 8.2+, PDO MySQL, cURL, mbstring, dan cron/CLI PHP. Pesan masuk disimpan segera; worker cron memanggil Kenari dan mengantrekan balasan melalui gateway. Belum memakai data properti atau memori percakapan.

## Konfigurasi

Pada Laravel lokal, `.env` yang sudah Anda isi dipakai untuk Kenari. Tambahan konfigurasi:

```dotenv
WA_AI_ENABLED=true
WA_GATEWAY_URL=http://43.133.133.142:3000
WA_GATEWAY_CLIENT_ID=cobadong
WA_GATEWAY_TOKEN=
```

Worker/webhook ini membaca nilai dari file `.env` langsung jika ada Composer Laravel di direktori induk `public`. Nilai tersebut mengungguli konfigurasi AI di bagian atas file. Konfigurasi database masih memakai variabel koneksi pada bagian atas file PHP.

Untuk hosting PHP mandiri tanpa Laravel, isi bagian `$aiConfig` di file PHP: `api_key`, `model`, `base_url`, `gateway_url`, dan `client_id`. API key asli tidak disalin ke source publik pada project ini. Isi koneksi database hosting seperti file webhook yang sebelumnya sudah berhasil. Jangan menimpa kredensial hosting dengan konfigurasi root lokal.

Gateway session yang terhubung adalah `cobadong`, bukan ID akun aplikasi `15`. URL callback tetap `https://afika.berkahtech.biz.id/wa-webhook-test.php`. Gateway harus dapat dijangkau dari hosting melalui `gateway_url`. Alamat port 3000 sebelumnya timeout dari lingkungan pengujian; akses dari hosting masih harus diverifikasi. Jika Anda punya URL gateway HTTPS atau API aplikasi KirimWatsap yang dapat diakses dari hosting, gunakan endpoint yang kompatibel; jangan menebak format autentikasinya.

## Upload dan jalankan worker

1. Backup file webhook hosting yang sudah berjalan.
2. Upload ulang `wa-webhook-test.php` setelah mengisi konfigurasi hosting.
3. Pada terminal hosting, gunakan path PHP dan lokasi file sebenarnya:

```sh
php /home/AKUN/public_html/wa-webhook-test.php --worker
```

Jika belum ada pesan baru, hasilnya `Tidak ada pesan menunggu balasan.`. Perintah ini membuat tabel `whatsapp_ai_replies` otomatis, tanpa mengubah tabel pesan masuk. User database perlu hak CREATE, SELECT, INSERT, UPDATE.

4. Buat cron job hosting setiap menit:

```cron
* * * * * /usr/bin/php /home/AKUN/public_html/wa-webhook-test.php --worker >> /home/AKUN/afika-ai-worker.log 2>&1
```

Ganti semua path dengan path hosting sebenarnya. Gunakan binary PHP CLI 8.2+ dengan ekstensi yang sama. Log disimpan di luar public_html. Cron pada panel hosting sering mempunyai kolom jadwal terpisah: isi hanya perintahnya pada kolom command.

5. Kirim pesan WhatsApp asli dari nomor lain ke 082221992911. Waktu tunggu hingga sekitar satu menit mengikuti cron, ditambah waktu AI/gateway.

## Memeriksa hasil

```sql
SELECT id, recipient, status, attempts, reply_text, gateway_job_id, last_error
FROM whatsapp_ai_replies
ORDER BY id DESC LIMIT 10;
```

- `pending`: menunggu worker atau percobaan Kenari berikutnya (maksimal 3).
- `processing`: worker sedang memproses.
- `queued`: gateway telah menerima job. Ini belum membuktikan pesan sudah terkirim ke WhatsApp. Cek `GET /messages/ID_JOB` pada gateway.
- `failed`: permintaan ditolak atau percobaan Kenari habis.
- `unknown`: hasil pengiriman gateway belum pasti, misalnya timeout atau worker mati. Tidak diulang otomatis agar tidak menggandakan balasan; periksa job/log gateway sebelum tindakan manual.

Pesan duplikat memakai satu job AI. Worker menggunakan lock database agar dua cron tidak memproses antrean bersamaan. Hanya teks pribadi dari client `cobadong` yang berumur maksimal 5 menit saat webhook diterima yang masuk antrean AI. Grup, status, media, dan pesan lama hanya disimpan seperti sebelumnya. JID `@lid` tanpa alternatif nomor telepon tidak dibalas karena gateway saat ini membutuhkan nomor; jangan mengubah LID menjadi nomor telepon secara paksa.

Autentikasi webhook memakai `$webhookToken` yang sudah tersedia. Jika diisi, set header Authorization yang sama pada callback gateway. Request Kenari gagal tidak menampilkan API key atau detail respons ke pelanggan. Tidak ada pesan WhatsApp nyata yang dikirim selama tes otomatis project.

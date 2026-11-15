# Baileys VPS → webhook hosting → MySQL

Tombol WA membuka `082221992911` dalam format internasional `6282221992911`.
Klik tombol saja belum menghasilkan pesan database. Pesan tersimpan ketika pengguna mengirim pesan dan sesi Baileys menerima event-nya.

## 1. Pilih endpoint hosting

### A. Website Laravel ini di hosting (disarankan)

1. Upload project Laravel termasuk folder `hosting/whatsapp-webhook/Receiver.php`, install dependency Composer, arahkan document root ke `public`.
2. Atur MySQL di `.env` hosting. Jalankan `php artisan migrate --force`.
3. Isi `.env` hosting:

```dotenv
WHATSAPP_NUMBER=6282221992911
WHATSAPP_WEBHOOK_SECRET=isi_secret_acak_minimal_32_karakter
```

4. Buat secret acak dengan `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Simpan nilainya hanya di konfigurasi hosting dan VPS. Project lokal sudah memiliki secret acak di `.env`.
5. Jalankan `php artisan config:clear` (atau buat ulang config cache).
6. URL endpoint: `https://domain-anda/webhooks/whatsapp`, metode POST, JSON, header `Authorization: Bearer SECRET`.
7. Lihat pesan di `/admin/pesan-whatsapp`, menu **Pesan WA**.

### B. Hanya file PHP pada hosting terpisah (tanpa Laravel)

1. Upload **dua file** dari `hosting/whatsapp-webhook/`: `webhook.php` dan `Receiver.php` ke direktori publik yang sama, misalnya `public_html/`.
2. Salin `whatsapp-config.example.php` menjadi `whatsapp-config.php` **satu direktori di atas public_html**, bukan di direktori publik. Isi kredensial MySQL, secret yang sama dengan VPS, dan account. Alternatif: environment `AFIKA_WEBHOOK_CONFIG` berisi path absolut file config.
3. Import `hosting/whatsapp-webhook/schema.sql` melalui phpMyAdmin jika tabel belum dibuat oleh Laravel. Gunakan database/akses database yang sama dengan website agar pesan terlihat di admin. Database berbeda berarti pesan hanya ada di database hosting tersebut.
4. URL endpoint: `https://domain-webhook/webhook.php`. File ini tidak membutuhkan Composer atau Laravel. Kebutuhan PHP 8.2+, PDO MySQL, mbstring, HTTPS.
5. Jika hosting membuang header Authorization, aktifkan penerusannya pada konfigurasi web server. Pada Apache dengan mod_rewrite, hosting dapat membutuhkan `RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]` di `.htaccess`.

Jangan unggah file `.env`, konfigurasi database, atau `schema.sql` ke area publik. Secret kosong/kurang dari 32 karakter menonaktifkan webhook (HTTP 503).

## 2. Pasang forwarder pada project Baileys VPS yang sudah ada

Gunakan Node.js 20+. Salin `afika-forwarder.mjs` ke project Baileys. Isi environment dari `.env.example` melalui PM2/systemd atau loader `.env` project Anda; file contoh tidak dibaca otomatis oleh modul.

```js
import { createAfikaForwarder } from './afika-forwarder.mjs';

// Satu instance untuk satu proses/session account, di luar fungsi reconnect.
const afika = await createAfikaForwarder();

// Setelah sock = makeWASocket(...) pada kode Anda:
afika.attach(sock);

// Setiap reconnect yang menghasilkan sock baru:
// afika.attach(sockBaru);

// Saat graceful shutdown: await afika.stop();
```

Ini memakai sesi Baileys yang sudah berjalan, tidak membuat sesi atau pairing baru. Pastikan sesi itu terhubung ke nomor `082221992911`. Handler bot Anda yang lain tetap bisa berjalan. Satu direktori outbox hanya untuk satu proses forwarder aktif.

Forwarder memproses SEMUA elemen pada `messages.upsert` untuk `notify` dan `append`, menyaring pesan keluar (`fromMe`) dan status broadcast. Pesan grup ikut tersimpan, dengan JID grup dan peserta. Pesan lama yang muncul lagi pada event tersebut tidak diduplikasi; ini tidak mengimpor seluruh riwayat sebelum integrasi. Event edit/hapus dan `messaging-history.set` bukan bagian impor ini.

## 3. Retry dan format pesan

Sebelum request, payload disimpan ke outbox VPS. Pesan dihapus dari outbox hanya setelah hosting mengembalikan JSON `ok: true` dengan HTTP sukses. Request gagal dicoba lagi setiap 15 detik dan setelah restart. Jangan hapus outbox saat deploy. Error permanen (401/422/413) tetap disimpan: periksa secret, payload atau ukuran pesan; pantau log dan kapasitas disk. Gangguan disk sebelum payload tersimpan tidak bisa dipulihkan oleh outbox.

Payload per request:

```json
{
  "account": "6282221992911",
  "event_type": "notify",
  "message": {
    "key": { "id": "UNIQUE_MESSAGE_ID", "remoteJid": "628123456789@s.whatsapp.net", "fromMe": false },
    "pushName": "Nama pengirim",
    "messageTimestamp": 1794614400,
    "message": { "conversation": "Halo, saya ingin informasi rumah." }
  }
}
```

Metadata lengkap pesan masuk tersimpan sebagai `raw_payload`; teks/caption di `body`. Nomor hanya diambil dari JID nomor telepon, bukan menebak nomor dari JID `@lid`. Media (gambar, audio, video, dokumen, stiker) disimpan sebagai metadata Baileys, **bukan file media**; unduh lampiran memerlukan proses tambahan di VPS. Batas payload 2 MiB. Waktu database UTC, inbox menampilkan WIB.

Respons: 201 tersimpan, 200 duplikat, 202 diabaikan (pesan keluar/status), 401 secret salah, 422 payload salah, 413 terlalu besar, 503 konfigurasi secret belum siap, 500 gangguan database. Kunci unik account + chat JID + message ID mencegah duplikat termasuk saat retry.

## 4. Pengujian

```sh
# Di root project Laravel (SQLite in-memory, tidak mengubah data MySQL)
php artisan test
# Tes forwarder tanpa koneksi WhatsApp atau hosting
node --test integrations/baileys/afika-forwarder.test.mjs
```

Kirim pesan nyata ke nomor tersebut setelah VPS dan hosting terhubung, lalu periksa tabel `whatsapp_messages` atau inbox admin. Koneksi VPS/hosting sebenarnya tetap perlu diuji dengan konfigurasi Anda.

Referensi event resmi Baileys: https://github.com/WhiskeySockets/baileys.wiki-site/blob/main/docs/socket/receiving-updates.md

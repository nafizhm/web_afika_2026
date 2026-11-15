# Indikator mengetik Fika

Worker mengirim presence `composing` ketika mulai menangani job, memperbaruinya setiap 5 detik selama AI bekerja, lalu mengirim `paused` ketika proses selesai atau gagal. Tidak menambahkan jeda buatan. Fitur ini tidak memperbaiki kegagalan model Kenari; balasan tetap membutuhkan request Kenari yang berhasil.

## Upload ke VPS Baileys

Backup file gateway yang akan diganti. Dari paket ZIP, upload tiga file berikut ke path yang sama di `/var/www/gateway-test/`:

```text
afika-ai-worker.mjs
src/http/routes.js
src/services/presenceService.js
```

File routes dibuat dari source lokal `D:/1_vibe coding/wa_gateway/src/http/routes.js`. Jika VPS mempunyai perubahan routes lain yang belum ada di lokal, gabungkan hanya tambahan import `sendChatPresence` dan route `POST /presence`, jangan menimpa perubahan lain.

Tidak perlu mengubah PHP webhook hosting atau file environment yang sudah bekerja. Worker mengaktifkan typing secara default; untuk mematikannya tambahkan `WA_TYPING_ENABLED=false` di `.env.afika-ai` lalu restart worker.

Restart di terminal VPS:

```sh
pm2 restart wa-gateway-v7
pm2 restart afika-ai-worker
pm2 logs afika-ai-worker --lines 20 --nostream
```

Gateway perlu session cobadong berstatus open setelah restart. Kirim pesan yang memerlukan jawaban AI dari HP lain dan buka percakapannya untuk melihat indikator. Pesan FAQ langsung mungkin terlalu cepat untuk menampilkan typing secara jelas. Indikator dimulai setelah job diambil worker, bukan sebelum pesan diterima webhook.

Jika endpoint presence belum dipasang atau session sedang terputus, worker tetap mencoba memproses balasan dan mencatat `Typing indicator unavailable...`. Request presence tidak membuat job pesan baru. API gateway tetap menggunakan pengaturan akses jaringan/auth yang sudah ada; worker memanggilnya di localhost VPS.

Untuk menguji endpoint secara manual dari VPS, gunakan nomor HP penguji yang ingin melihat indikator:

```sh
curl -X POST http://127.0.0.1:3000/presence -H 'Content-Type: application/json' -d '{"clientId":"cobadong","to":"628123456789","state":"composing"}'
curl -X POST http://127.0.0.1:3000/presence -H 'Content-Type: application/json' -d '{"clientId":"cobadong","to":"628123456789","state":"paused"}'
```

Dokumentasi resmi Baileys: https://github.com/WhiskeySockets/baileys.wiki-site/blob/main/docs/socket/presence-receipts.md

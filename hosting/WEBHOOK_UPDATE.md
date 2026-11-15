# Webhook Afika: dua file

Mulai versi ini, webhook AI memakai dua file dalam folder hosting yang sama:

- `wa-webhook-test.php`: kode penerima pesan, antrean, sinkronisasi FAQ, dan endpoint worker.
- `wa-webhook-config.php`: koneksi DB, nomor WA, token webhook/worker, dan pengaturan AI/gateway.

Pada pemasangan pertama versi ini, upload keduanya ke `/home/berkaht4/afika.berkahtech.biz.id/`. Pastikan konfigurasi memakai kredensial DB dan token hosting yang sebelumnya berhasil. Token worker harus tetap sama dengan environment VPS dan FAQ_SYNC_TOKEN admin.

Saat memperbarui kode berikutnya, upload hanya `wa-webhook-test.php`. Jangan menimpa atau menghapus `wa-webhook-config.php`. URL callback, konfigurasi worker VPS, dan URL sinkronisasi FAQ tetap sama. Worker VPS tidak perlu di-restart hanya karena file PHP dipisahkan.

Jika file konfigurasi hilang atau PHP-nya tidak valid, webhook mengembalikan HTTP 503 tanpa menampilkan kredensial. Akses langsung ke file konfigurasi melalui browser mengembalikan 404.

ZIP distribusi menggunakan konfigurasi template tanpa kredensial asli. Salinan deployment yang sudah Anda edit tetap tersedia di `storage/app/afika-ai-vps/hosting/`; konfigurasi di sana dipindahkan dari file sebelumnya tanpa mengubah nilainya.

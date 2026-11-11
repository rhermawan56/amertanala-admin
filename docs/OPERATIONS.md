# Panduan operasional dan validasi

Baseline v1.0.0, 9 November 2026. Perintah di bawah adalah panduan operator.
Tidak dijalankan pada sesi pencatatan dokumentasi ini. Jangan menjalankan
perintah terhadap database nyata hanya untuk memeriksa dokumentasi.

## 1. Setup lokal

Prasyarat: PHP yang memenuhi composer.lock beserta extension yang diperlukan,
Composer, Node/npm yang memenuhi engine paket terkunci, MySQL, dan mysqldump
untuk backup. Workflow CI saat ini menggunakan PHP 8.4 dan Node 22.

Pada checkout baru, pasang dependensi dari lockfile dengan `composer install`
dan `npm ci`. Buat `.env` dari `.env.example` jika belum ada; jangan menimpa
environment yang sudah dipakai. Buat APP_KEY hanya untuk environment baru.
Mengganti APP_KEY yang sudah dipakai dapat memutus dekripsi backup/data lama.

Konfigurasi utama yang perlu ditetapkan tanpa memasukkan rahasia ke Git:

| Kelompok | Variabel |
| --- | --- |
| Aplikasi | APP_ENV, APP_DEBUG, APP_URL, APP_KEY, APP_LOCALE |
| MySQL | DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD |
| Session | SESSION_DRIVER, SESSION_LIFETIME, SESSION_ENCRYPT, SESSION_COOKIE, SESSION_SECURE_COOKIE, SESSION_SAME_SITE |
| Chat | CHAT_COOKIE, LANDING_URL, LANDING_FRAME_ORIGINS |
| Queue/cache | QUEUE_CONNECTION, CACHE_STORE |
| Email | MAIL_MAILER serta kredensial transport yang dipilih |
| Reverb | BROADCAST_CONNECTION, REVERB_APP_ID/KEY/SECRET, REVERB_HOST/PORT/SCHEME, REVERB_SERVER_HOST/PORT |
| Browser Reverb | VITE_REVERB_APP_KEY/HOST/PORT/SCHEME |
| Backup | MYSQLDUMP_PATH |

Nilai VITE dikirim ke browser; jangan memasukkan REVERB_APP_SECRET atau rahasia
server ke variabel VITE. Nilai contoh dalam .env.example bukan kredensial produksi.

Setelah memastikan target database benar dan memiliki backup bila berisi data,
jalankan migrasi Laravel. `php artisan storage:link` diperlukan untuk URL media
public. Buat admin melalui `php artisan admin:create alamat@example.test`
dengan password melalui prompt tersembunyi, bukan argumen shell.

Opsi `--bootstrap` hanya untuk admin pertama dalam environment local. Opsi itu
menulis password sementara ke private/initial-admin.txt; jangan tampilkan file
ke chat/log atau commit, dan hapus setelah akses tersimpan aman.

## 2. Proses pendukung

`composer run dev` menjalankan lima proses:

| Proses | Nilai lokal pada composer.json |
| --- | --- |
| HTTP admin | php artisan serve --host=127.0.0.1 --port=8001 |
| Queue | php artisan queue:work --sleep=1 --tries=3 --timeout=30 |
| Reverb | php artisan reverb:start --host=127.0.0.1 --port=8081 |
| Scheduler | php artisan schedule:work |
| Vite | npm run dev |

dev.ps1 menambahkan direktori PHP Laragon ke PATH proses. Sesuaikan parameter
PhpDirectory jika instalasi berbeda. Proses tersebut untuk pengembangan;
deployment membutuhkan pengelolaan worker, scheduler, dan WebSocket tersendiri.

Pada landingpage, atur ADMIN_API_URL ke origin admin dan ADMIN_CHAT_URL ke
halaman /chat admin. Pastikan LANDING_FRAME_ORIGINS pada admin memuat origin
landingpage yang tepat. Baca kontrak landingpage sebelum mengubah repository itu.

## 3. Ketentuan keamanan operasional

- Aktifkan HTTPS/WSS dan secure cookie untuk produksi; APP_DEBUG tetap false.
- Cookie admin dan chat harus memiliki nama berbeda dari aplikasi tetangga.
  Contoh memakai amertanala_admin_session dan amertanala_chat.
- Cookie CSRF bernama AMERTANALA-XSRF. Inertia, fetch client, dan auth WebSocket
  harus mengikuti nama ini. Cookie CSRF dapat dibaca JavaScript; cookie akses
  admin/chat harus tetap HttpOnly sesuai konfigurasinya.
- Header sumber melarang embedding workspace dan membatasi embedding chat;
  nosniff, no-referrer, serta pembatasan camera/microphone/geolocation diterapkan.
  CSP saat ini bukan kebijakan script-src lengkap; jangan mengklaim proteksi XSS menyeluruh.
- Jangan meletakkan backup, APP_KEY, token, database dump, atau kredensial dalam
  public/storage. Gunakan izin filesystem/Windows ACL yang sesuai; chmod pada
  kode bukan bukti ACL Windows sudah benar.
- Email recovery memerlukan transport nyata dan worker queue. Notifikasi unread
  memerlukan scheduler dan queue. WebSocket memerlukan Reverb dan worker broadcast.
- Saat memeriksa log, gunakan data dummy dan jangan menyalin body pesan atau token.

Skill security-audit dipakai dalam mode panduan untuk mendokumentasikan batas
kepercayaan. Tidak ada full audit, pentest, atau klaim kerentanan terkonfirmasi.

## 4. Backup dan restore

`php artisan admin:backup` menggunakan konfigurasi koneksi mysql dan mysqldump.
Hasil SQL dibatasi 16 MiB, dikompresi gzip, lalu dienkripsi melalui APP_KEY.
File disimpan di storage/app/private/backups; retensi 30 hari. Scheduler menjadwalkan
02:00 Asia/Bangkok. Simpan APP_KEY secara terpisah dan aman.

Backup ini hanya database. Cadangkan juga storage/app/public/media dan file
privat chat-deletions.txt melalui mekanisme terpisah. Lindungi salinan daftar
penghapusan terkini agar restore database lama tidak menghidupkan chat terhapus.
Kegagalan backup, kapasitas batas, dan keberhasilan jadwal perlu dipantau operator.

Prosedur restore harus dilakukan operator pada environment terisolasi dahulu:

1. Identifikasi backup, APP_KEY yang sesuai, media, dan daftar penghapusan terbaru.
2. Buat backup kondisi sekarang; hentikan proses penulis selama pemulihan yang disetujui.
3. `php artisan admin:export-backup <nama-file.enc>` hanya mendekripsi menjadi
   storage/app/private/backups/restore.sql; perintah ini tidak mengimpor database.
4. Operator mengimpor SQL ke target yang sudah diverifikasi. Tidak ada perintah
   impor otomatis dalam aplikasi.
5. Pulihkan media dan daftar penghapusan terbaru, lalu jalankan
   `php artisan chat:apply-deletions` sebelum membuka akses pengguna kembali.
6. Periksa konsistensi konten, media, foreign key, dan penghapusan memakai data
   yang boleh diperiksa. Pertimbangkan pencabutan sesi/token yang dipulihkan
   sesuai prosedur insiden; pencabutan massal belum diotomatisasi.
7. Hapus dump plaintext setelah validasi sesuai kebijakan penyimpanan operator.

Pengujian round-trip restore dan pemulihan izin file belum dilakukan pada sesi ini.

## 5. Validasi perubahan

| Dampak perubahan | Pemeriksaan yang relevan |
| --- | --- |
| Dokumentasi saja | Tautan relatif, akurasi terhadap sumber, manifest hash, tidak ada rahasia |
| Vue/TypeScript | npm run typecheck; npm run build |
| Visual | Pemeriksaan halaman dan viewport pada DESIGN.md; simpan bukti untuk perubahan kontrak |
| CMS/auth/media | php artisan test --compact tests/Feature/WorkspaceTest.php |
| Chat/recovery/retensi | php artisan test --compact tests/Feature/ChatTest.php |
| Backend luas | Suite PHPUnit sesuai cakupan dan environment uji terisolasi |
| Integrasi layanan | Mail queue dummy, channel Reverb privat, outage API, cache fallback, media URL |

phpunit.xml mengarahkan test ke SQLite memory, session/cache array, email array,
dan queue sync. Ini berbeda dari MySQL/queue/Reverb produksi. Pastikan environment
uji memang terisolasi; test memakai RefreshDatabase.

`npm run lint` memakai --fix dan `npm run format` memakai --write. Keduanya
mengubah sumber. format:check tersedia untuk pemeriksaan format frontend.
Workflow GitHub tersedia, tetapi belum menjadi gate kontrak desain/proses dan
belum mencakup semua validasi di atas; jangan menganggap dokumentasi sudah ditegakkan CI.

Saat menggunakan security-audit, eksekusi aplikasi/test/build hanya boleh dalam
sandbox OS dengan jaringan eksternal tertutup, environment allowlist, target
read-only, scratch terbatas, dan batas sumber daya. Kontrol tersebut tidak
disiapkan dalam sesi ini; validasi dilakukan statis, tanpa menjalankan aplikasi.

## 6. Cara membaca manifest baseline

docs/baseline/v1.0.0.json memuat path relatif dan SHA-256. Verifikasi memakai
Get-FileHash terhadap file saat ini lalu bandingkan dengan manifest. Hash berbeda
berarti sumber berubah; tidak otomatis berarti regresi atau perubahan disetujui.
File baru di luar daftar perlu diperiksa melalui status/diff repository.

Manifest sengaja tidak memasukkan .env, storage, vendor, node_modules, build,
database aktual, atau file rahasia. Git belum memiliki commit ketika baseline
dicatat. Setelah review, pemilik dapat membuat commit baseline dokumentasi dan
sumber; sesi ini tidak membuat commit.

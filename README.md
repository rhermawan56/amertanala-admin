# Amertanala Studio — Admin

Workspace pengelola konten landing page, pustaka media, dan percakapan pengunjung.
Fondasi: Laravel 13, Inertia 3, Vue 3, TypeScript, Tailwind CSS 3,
Fortify, dan Reverb. Database contoh konfigurasi menggunakan MySQL.
Versi yang benar-benar dikunci tersedia di `composer.lock` dan `package-lock.json`.

## Dokumentasi utama

| Dokumen | Kegunaan |
| --- | --- |
| [AGENTS.md](AGENTS.md) | Aturan wajib sebelum perubahan oleh agen |
| [DESIGN.md](DESIGN.md) | Kontrak visual dan interaksi admin |
| [Proses bisnis](docs/BUSINESS-PROCESS.md) | Alur, aturan data, dan kriteria penerimaan |
| [Arsitektur](docs/ARCHITECTURE.md) | Modul, integrasi landingpage, serta batas kepercayaan |
| [Operasional](docs/OPERATIONS.md) | Setup, proses pendukung, keamanan, backup, dan validasi |
| [Riwayat keputusan](docs/CHANGELOG.md) | Baseline dan prosedur perubahan kontrak |
| [Manifest baseline](docs/baseline/v1.0.0.json) | SHA-256 sumber saat dokumentasi dibuat |

## Status cakupan

Sudah ada dalam sumber: login admin tanpa MFA, penggantian/reset password,
dashboard berbasis data, CMS draf/publikasi/restore, unggah gambar, chat tamu,
inbox admin, pemulihan chat melalui email, maintenance, dan perintah backup.
Status ini berdasarkan pembacaan sumber, bukan pernyataan semua layanan sudah
dikonfigurasi atau semua pemeriksaan sudah lulus.

Editor undangan seperti Canva merupakan rencana. Belum ada mesin editor,
format desain undangan final, pembayaran, atau isolasi tenant pelanggan.

Admin dan landingpage sekarang dua aplikasi Laravel terpisah. Admin lokal
direncanakan pada port 8001; landingpage pada port 8000. Keduanya terhubung
melalui API konten publik dan iframe chat, bukan berbagi model/database.
Ini menggantikan saran awal satu aplikasi dalam diskusi.

## Mulai pengembangan

Baca [panduan operasional](docs/OPERATIONS.md) sebelum menyiapkan environment.
Setelah dependensi, database, dan environment siap, `composer run dev`
menjalankan server, worker queue, Reverb, scheduler, dan Vite.
`dev.ps1` menyediakan pilihan direktori PHP khusus Laragon.

Perubahan desain dan proses bisnis mengikuti [kontrak perubahan](docs/CHANGELOG.md).

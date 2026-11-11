# Aturan kerja Amertanala Admin

## Dokumen wajib

Sebelum mengubah aplikasi, baca `DESIGN.md`, `docs/BUSINESS-PROCESS.md`,
`docs/ARCHITECTURE.md`, dan `docs/CHANGELOG.md`. Untuk autentikasi, chat,
media, konfigurasi, dan deployment, baca juga `docs/OPERATIONS.md`.
Aturan ini berlaku untuk seluruh repository admin, bukan repository landingpage.

## Menjaga baseline

- Baseline v1.0.0 mencatat implementasi yang ada pada 9 November 2026.
  Pencatatan ini tidak membuktikan persetujuan historis setiap detail atau
  menyatakan aplikasi sudah lolos audit keamanan dan pengujian visual.
- Pertahankan desain gelap, token, layout, navigasi, breakpoint, dan perilaku
  komponen dalam `DESIGN.md`. Gunakan komponen dan pola yang sudah tersedia.
- Pertahankan aturan proses BP-01 sampai BP-08. Perbaikan internal harus menjaga
  hasil yang terlihat pengguna dan batas akses yang didokumentasikan.
- Jangan mengganti gaya menggunakan referensi awesome-design-md lain kecuali
  pengguna meminta perubahan gaya tersebut secara eksplisit.
- Jangan menganggap saran lama dalam chat lebih kuat daripada kondisi repository
  dan keputusan terbaru yang sudah dicatat. Admin sekarang aplikasi terpisah.
- Jangan mengaktifkan MFA, passkeys, registrasi publik, editor Canva, pembayaran,
  atau multi-tenant sebagai bagian sampingan pekerjaan lain.
- Jangan menghapus data MFA lama walaupun fitur MFA sedang ditunda.
- Jangan mengubah landingpage dari pekerjaan admin tanpa cakupan eksplisit.

## Perubahan kontrak

Jika permintaan eksplisit mengubah kontrak, kerjakan dalam cakupan permintaan itu;
tidak perlu meminta persetujuan yang sama lagi. Jika perubahan kontrak hanya
merupakan usulan tambahan, jelaskan dampaknya dan tunggu keputusan pemilik.

Untuk perubahan yang disetujui, catat ID perubahan, keputusan pemilik, perilaku
sebelum/sesudah, kontrak terdampak, validasi, dan rollback di `docs/CHANGELOG.md`.
Perbarui dokumentasi terkait dalam perubahan yang sama. Jangan mengubah baseline
atau test hanya untuk menyembunyikan regresi. Jangan mengarang bukti persetujuan.

## Batas implementasi dan validasi

- Gunakan Laravel, Inertia, Vue 3, TypeScript dan dependensi yang sudah ada.
  Perubahan dependensi besar memerlukan cakupan tugas yang mendukungnya.
- Autentikasi dan otorisasi tetap diperiksa server; menyembunyikan tombol bukan kontrol akses.
- Jangan membaca atau menyalin `.env`, kredensial bootstrap, database pengguna,
  log pesan, token pemulihan, atau backup ke dokumentasi dan output publik.
- Jangan menjalankan migrasi destruktif, restore, publikasi konten, atau pembuatan
  akun nyata untuk memverifikasi dokumentasi.
- Validasi sesuai dampak perubahan: typecheck/build untuk frontend, test fitur
  terkait untuk backend, pemeriksaan visual untuk desain. Jangan mengklaim lulus
  jika belum dijalankan. Ikuti batas sandbox dari skill yang sedang digunakan.
- `npm run lint` dan `npm run format` mengubah file; gunakan dengan sengaja.
- Dokumentasi tidak menjamin pencegahan regresi otomatis. Hash baseline hanya
  mendeteksi perbedaan berkas; test perilaku dan review tetap diperlukan.

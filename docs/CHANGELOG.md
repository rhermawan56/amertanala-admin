# Baseline dan perubahan kontrak

## v1.0.0 — 9 November 2026

**Jenis:** pencatatan baseline sumber dan penetapan prosedur perubahan.

**Dasar:** pemilik meminta ketentuan desain dan dokumentasi agar desain serta
proses bisnis yang telah disetujui tidak berubah tanpa kendali.

**Hasil:** DESIGN.md, BUSINESS-PROCESS.md, ARCHITECTURE.md, OPERATIONS.md,
README.md, AGENTS.md, dan manifest SHA-256 sumber.

**Status persetujuan:** permintaan dokumentasi ini eksplisit. Riwayat persetujuan
tiap perubahan sebelumnya tidak tersedia di repository; baseline mencatat keadaan
sekarang, bukan menciptakan bukti persetujuan historis. Jika pemilik menunjukkan
perbedaan dengan keputusan sebelumnya, catat koreksinya sebagai perubahan berikutnya.

**Keputusan yang dicatat:**

- Stack Laravel + Inertia + Vue 3 + TypeScript sesuai keputusan diskusi.
- Admin saat ini aplikasi terpisah dari landingpage; integrasi memakai API dan iframe.
- Visual admin gelap dengan panel kaca dan aksen cyan dipertahankan dari sumber.
- Alur BP-01 sampai BP-08 menjadi acuan review; MFA tetap ditunda dan datanya dipertahankan.
- Editor undangan seperti Canva tetap rencana, belum kontrak fitur aktif.

**Validasi sesi:** pembacaan statis sumber, keterlacakan dokumen, pemeriksaan
tautan lokal, dan hash baseline. Tidak menjalankan test/build, browser, queue,
email, backup, restore, atau publikasi konten. Tidak mengubah runtime aplikasi.

**Batas perlindungan:** dokumen dan AGENTS.md membimbing perubahan berikutnya;
tidak mengunci UI secara teknis atau menegakkan semua aturan melalui CI.
Manifest tidak berisi screenshot dan tidak membuktikan kesamaan visual.

## Prosedur perubahan berikutnya

1. Identifikasi aturan DESIGN atau BP yang terdampak sebelum mengubah sumber.
2. Tentukan apakah perubahan menjaga kontrak atau mengubah kontrak.
3. Jika menjaga kontrak, lakukan perubahan dan validasi sesuai dampak.
4. Jika mengubah kontrak, gunakan keputusan eksplisit pemilik. Permintaan yang
   sudah jelas mengubah aturan merupakan otorisasi dalam cakupannya; jangan
   mengulang pertanyaan persetujuan yang sama. Usulan tambahan perlu keputusan.
5. Catat perubahan melalui format berikut, perbarui dokumen dan test yang relevan,
   serta simpan bukti validasi. Hanya buat manifest versi baru untuk baseline baru
   yang memang disepakati; jangan menimpa manifest v1.0.0.

### Template catatan

```text
ID: CHG-YYYYMMDD-NN
Tanggal dan versi kontrak:
Permintaan/keputusan pemilik: (kutipan ringkas atau referensi yang benar-benar tersedia)
Status: usulan / disetujui / diterapkan / dibatalkan
Aturan terdampak: DESIGN bagian ... / BP-...
Sebelum:
Sesudah:
Alasan dan cakupan:
Dampak pada data, akses, API, dan landingpage:
File terkait:
Validasi yang dijalankan beserta hasil:
Validasi yang belum dijalankan dan batasnya:
Rollback kode/data:
```

### Penomoran kontrak

- Patch: koreksi dokumentasi atau klarifikasi tanpa perubahan hasil pengguna.
- Minor: penambahan yang kompatibel, dengan keputusan pemilik bila mengubah kontrak.
- Major: perubahan visual/proses yang memutus kontrak atau kompatibilitas lama.

Nomor versi bukan bukti persetujuan. Rollback kode tidak otomatis memulihkan
database, isi published, file media, token, atau data chat yang sudah dihapus.

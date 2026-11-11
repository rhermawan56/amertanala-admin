# Kontrak proses bisnis

Baseline v1.0.0, 9 November 2026. Aturan berikut mencatat perilaku sumber admin
saat ini agar perubahan berikutnya dapat dinilai terhadap acuan yang jelas.
Nama test merupakan bukti adanya skenario regresi, bukan bukti test sudah lulus
pada sesi dokumentasi ini.

## BP-01 — Akses administrator

- Akun dibuat lewat `admin:create`; pendaftaran publik tidak aktif.
- Login menggunakan email dan password, hanya akun `is_admin=true` diterima.
  Remember-me dipaksa nonaktif. Halaman workspace memakai middleware auth/admin.
- Password baru minimal 12 karakter, huruf besar/kecil, angka, simbol;
  perubahan password memerlukan password sekarang dan konfirmasi password baru.
- MFA ditunda. Data enrollment lama dipertahankan; route enrollment/challenge
  tidak aktif. Keberadaan file Challenge atau migration passkeys bukan fitur aktif.
- Batas middleware workspace: idle 900 detik dan maksimal 28.800 detik sejak
  pencatatan awal workspace. Polling pesan/read dan reload parsial Inertia tidak
  memperpanjang timer aktivitas tersebut. Pengaturan session database tetap berpengaruh.
- Logout mengakhiri akses; permintaan non-admin ditolak server.

Penerimaan: tamu diarahkan ke login, non-admin ditolak, admin dapat membuka lima
halaman utama tanpa MFA. Test: `WorkspaceTest`, khususnya akses, registrasi,
session limits, data MFA lama, route MFA, private channels, dan change password.
Sumber: FortifyServiceProvider, EnsureAdmin, config/fortify.php, CreateAdmin.

## BP-02 — Draf, publikasi, dan pemulihan konten

1. Admin membuka konten; record `site_contents.key=landing` dibuat dari default
   jika belum ada. Keadaan awal memiliki draft dan belum memiliki published.
2. Simpan draf memvalidasi payload, mencocokkan `version`, mengunci record dalam
   transaksi, memperbarui draft, menaikkan version, dan membuat revisi `draft`.
3. Publikasi memerlukan version terbaru. Server menyalin draft tersimpan ke
   published, mengisi published_at, menaikkan version, dan mencatat `published`.
4. Restore hanya memilih revisi milik record landing, menyalinnya ke draft,
   menaikkan version, dan mencatat `restored`. Published tetap tidak berubah.
5. Konflik version menghasilkan HTTP 409. Jangan otomatis menimpa perubahan
   admin lain. Muat ulang dan rekonsiliasi perubahan sebelum mencoba kembali.

UI meminta simpan sebelum publikasi dan menampilkan konfirmasi. Tidak ada autosave
atau publikasi terjadwal. Riwayat UI mengambil 15 revisi terakhir; ini bukan
kebijakan menghapus revisi lama.

Penerimaan: menyimpan/restore tidak mengubah konten publik; publikasi eksplisit
baru mengubahnya; stale write/publish ditolak. Test: tiga skenario drafts,
invalid content, restoring pada `WorkspaceTest`. Sumber: ContentController,
SaveContentRequest, pages/workspace/Content.vue.

## BP-03 — Struktur konten dan tema

| Data | Ketentuan server saat baseline |
| --- | --- |
| Identitas dan teks halaman | 15 field teks wajib, masing-masing maksimal 1000 karakter |
| Email konsultasi | Opsional, format email, maksimal 255 karakter |
| Chat aktif | Boolean wajib |
| Tema | 1–30 item; urutan array menentukan urutan tampilan |
| ID tema | Unik dalam payload, huruf kecil/angka/tanda minus, maksimal 100 karakter |
| Nama, eyebrow, caption tema | Wajib, maksimal 150 karakter masing-masing |
| Nama pasangan pada tema | 1–3 baris, maksimal 100 karakter setiap baris |
| URL preview | Opsional, HTTP/HTTPS, maksimal 2048 karakter |
| Alt preview | Opsional pada payload tema, maksimal 255 karakter |
| Fitur layanan | 1–12 item; judul maksimal 150, deskripsi maksimal 500 karakter |

Lima tab UI tidak mengubah skema ini. ID tema yang sudah digunakan harus stabil
agar konteks pemilihan tema tetap bermakna. Chat menyimpan nama tema hasil resolusi
server saat pembuatan, bukan relasi katalog hidup yang ikut berubah namanya.
Daftar lengkap field teks ada di SaveContentRequest dan config/workspace.php.

## BP-04 — Konsumsi konten oleh landingpage

- Endpoint `GET /api/public/landing` hanya mengirim published, version, published_at;
  tidak mengirim draft atau revisi. Sebelum publikasi pertama, hasilnya 404.
- Header cache API `public, max-age=30`. Version adalah versi record workspace,
  sehingga simpan draft juga menaikkan version walaupun published tetap sama.
- Aplikasi landingpage membaca API dari server melalui ADMIN_API_URL. Pembaca
  memakai cache 30 detik, connect timeout 1 detik, request timeout 2 detik,
  dan tidak mengikuti redirect.
- Data valid terakhir disimpan 86.400 detik. Jika API gagal atau payload tidak
  valid, landingpage memakai last-good yang masih tersedia atau default lokal.
- Karena fallback, pesan UI “paling lambat 30 detik” bukan jaminan ketika API gagal.
  Halaman yang sudah terbuka juga tidak dijamin memperbarui dirinya otomatis.
- ADMIN_CHAT_URL mengarahkan iframe chat. Origin embedding harus sesuai konfigurasi.

Penerimaan: outage admin tidak mengharuskan halaman publik gagal; draft tidak
bocor melalui API. Sumber lintas repository: landingpage/app/Services/
PublishedLandingContent.php dan landingpage/config/cms.php. Repository itu tidak
diubah dalam pencatatan baseline ini.

## BP-05 — Pustaka media

Admin mengunggah JPG/JPEG, PNG, atau WebP dengan alt text wajib maksimal 255
karakter. Batas file 4096 KB dan dimensi maksimal 6000 × 6000. File masuk disk
public pada direktori media; metadata masuk media_assets. Daftar 24 item per
halaman, terbaru dahulu. URL disalin untuk dipakai pada preview tema.

Media publik dapat dibaca lewat URL; tidak boleh dipakai menyimpan dokumen privat.
Belum ada operasi hapus, edit gambar, atau unggah lampiran chat.
Penerimaan: SVG dan payload non-gambar ditolak, non-admin tidak dapat mengelola
media. Test: media upload pada WorkspaceTest. Sumber: MediaController dan Media.vue.

## BP-06 — Chat pengunjung dan inbox admin

- Mulai chat memakai nama maksimal 100, pesan maksimal 2000, UUID client_id,
  optional theme_id, dan honeypot website kosong. Server menetapkan sender guest.
- Tema diambil dari konten published atau default saat belum ada publikasi.
  theme_id yang tidak ditemukan menghasilkan konteks tema null.
- Cookie chat memetakan akses ke satu percakapan. Parameter conversation_id dari
  tamu tidak dapat memilih percakapan lain. Tanpa sesi valid, akses pesan ditolak.
- Token acak 32 byte disimpan sebagai hash SHA-256; cookie HttpOnly, SameSite Strict,
  dan Secure mengikuti konfigurasi. Kedaluwarsa absolut 7 hari, idle lebih dari 1 hari
  ditolak. Membaca/polling tidak menyentuh last_seen; aksi yang memakai touch dapat
  memperbaruinya. Jangan mengubah polling menjadi perpanjangan sesi tanpa keputusan.
- Idempotensi pesan memakai kombinasi conversation_id, sender, client_id.
  Retry ID yang sama mengembalikan pesan awal, bukan menimpa body atau menggandakan.
- Start pertama juga memiliki start_key dari sesi browser dan client_id agar retry
  tanpa response cookie dapat menemukan percakapan awal.
- Pesan baru mengubah status menjadi open. Aksi tutup dari admin bukan penghapusan
  atau pemblokiran: pesan baru berikutnya dapat membuka percakapan kembali.
- Admin memilih percakapan melalui route; sender admin ditentukan server.
  Penanda baca maju secara monoton dan dibatasi ID pesan percakapan tersebut.
  Unread saat ini dibagi antar-admin melalui admin_read_id pada conversation.
- Inbox menampilkan 30 percakapan per halaman; cari berdasarkan nama/tema.
  Halaman awal mengambil 100 pesan terbaru; pesan lama dapat dimuat per 100.
  Endpoint pesan tamu mengambil per 100 melalui cursor after.
- Reverb mengirim sinyal conversation_id setelah transaksi; isi pesan diambil lagi
  melalui HTTP berotorisasi. Poll fallback berjalan 15 detik saat panel terlihat.
  Event tamu hanya melalui guest-session milik sesi aktif.
- Frontend maksimal empat percobaan pengiriman untuk kegagalan network/5xx,
  memakai ID yang sama; 4xx tidak otomatis diulang. Error harus tetap terlihat.

Kontrol laju tidak boleh dihapus diam-diam: start route 10/jam, pembuatan baru
3/jam per sesi browser, pesan tamu route 60/menit serta 10/menit per percakapan.
Cabang sesi/retry awal memiliki alur tersendiri di GuestChatController; jangan
mengklaim semua cabang start melalui limiter pesan per-percakapan.

Penerimaan: tamu tidak menyamar sebagai admin atau membaca chat lain; retry tidak
menggandakan pesan; akses expired ditolak; admin membalas percakapan route.
Test: ChatTest. Sumber: GuestChatController, InboxController, ChatAccess,
ChatMessages, useDelivery.ts, realtime.ts, Inbox.vue, GuestChat.vue.

## BP-07 — Verifikasi email dan pemulihan chat

1. Verify diminta dari sesi chat valid. Recover mencari percakapan terakhir aktif
   yang emailnya sudah diverifikasi. Respons generik tidak mengungkap akun/email.
2. Mailer array/log menghasilkan 503; UI tidak boleh mengklaim email benar-benar dikirim.
3. Tautan menggunakan token acak, hash tersimpan, masa berlaku 15 menit.
   GET hanya membuka halaman, tidak mengonsumsi token.
4. POST mengunci tautan dan percakapan. Verify wajib dari browser asal sesi chat.
5. Berhasil verify/recover menghapus sesi lama dan seluruh tautan percakapan,
   kemudian menerbitkan cookie sesi baru. Tautan tidak dapat dipakai ulang.

Batas aplikasi per email: 3 permintaan/jam; route memiliki limiter tambahan.
Notifikasi tautan diproses queue terenkripsi. Mengoperasikan queue dan mailer
nyata merupakan prasyarat pengiriman, bukan konsekuensi respons sukses HTTP saja.
Test: recovery, verify browser, unconfigured delivery pada ChatTest.

## BP-08 — Retensi, notifikasi, backup

- Scheduler menjalankan chat:maintenance setiap menit, tanpa overlap.
- Percakapan dengan aktivitas terakhir lebih dari 90 hari dihapus bersama data
  dependennya. UUID dicatat dahulu dalam file privat chat-deletions.txt.
- Sesi idle/expired dan tautan kedaluwarsa dibersihkan. Pesan tamu belum dibaca
  lebih dari 5 menit memicu email admin, maksimal sekali per 15 menit per percakapan
  menurut last_notified_at. Email tidak memuat body pesan.
- Backup database terenkripsi dijadwalkan harian 02:00 Asia/Bangkok, retensi
  file backup 30 hari. Batas SQL sebelum kompresi 16 MiB dan timeout dump 60 detik.
- Restore database tidak otomatis. Setelah restore harus menerapkan kembali
  daftar penghapusan; simpan daftar terbaru di luar snapshot database lama.
- Backup database ini tidak mencakup file media, daftar penghapusan, atau APP_KEY.
  Pemulihan lengkap membutuhkan penyimpanan dan kunci terpisah.

Test retensi yang tersedia: maintenance pada ChatTest. Backup/restore, pengiriman
email nyata, dan integrasi Reverb perlu validasi operasional terpisah.

## Rencana yang belum menjadi proses aktif

Editor undangan, format JSON desain, drag-and-drop bebas/blok, undo/redo, tenant
pelanggan, pembayaran, kolaborasi desain, dan publikasi undangan per pelanggan
belum ditetapkan sebagai perilaku implementasi. Jangan memasukkannya ke baseline
aktif sebelum keputusan dan implementasi yang terpisah.

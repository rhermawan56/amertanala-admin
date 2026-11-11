# Kontrak desain Amertanala Studio

Versi: **1.0.0**. Tanggal pencatatan: **9 November 2026 (Asia/Bangkok)**.
Lingkup: aplikasi `admin`. Baseline bersumber dari implementasi saat ini;
tidak memperkenalkan desain baru atau menyatakan semua detail pernah disetujui.

## 1. Karakter visual

Pertahankan workspace gelap dengan panel kaca, cahaya latar indigo/teal yang
lembut, aksen cyan, dan tipografi Inter. Tampilan operasional harus tenang,
ringkas, dan memiliki hierarki jelas. Identitas utama: `amertanala.` serta
`STUDIO WORKSPACE`. Ikon mengikuti `lucide-vue-next` yang sudah dipakai.

Sumber visual utama: `resources/css/app.css` dan
`resources/js/workspace/WorkspaceLayout.vue`. Pertahankan sistem ini sesuai
skill awesome-design-md; tidak mengambil gaya merek lain dari katalog.
Desain landingpage memiliki kontrak sendiri di repository `landingpage`.

## 2. Token yang dipertahankan

| Elemen | Nilai sumber |
| --- | --- |
| Latar body | `#090e1a` |
| Teks utama `--ink` | `#f1f5f9` |
| Teks sekunder `--muted` | `#a9b6cd` |
| Aksen/fokus `--cyan` | `#67e8f9` |
| Border `--border` | `rgba(191, 213, 255, 0.12)` |
| Panel `--panel` | `rgba(23, 34, 56, 0.54)` |
| Input `--input` | `#111b2e` |
| Panel kaca | Radius 18px, blur 20px, border 1px |
| Fallback tanpa backdrop-filter | Panel `#172238` |
| Tombol utama | Gradien 115deg `#9cf3f6` ke `#67ddec`, teks `#10242e` |
| Tombol sekunder | Latar `#ffffff05`, border `#ffffff16`, teks `#d4dfef` |
| Tombol bahaya | Latar `#fb718518`, teks `#fda4af`, border `#fb71852a` |
| Font | Inter 400/500/600/700, fallback system-ui/sans-serif |
| Body | 14px, line-height 1.5 |
| H1 desktop | `clamp(24px, 2.1vw, 32px)`, weight 600, line-height 1.25 |
| H2/H3 | 17px/14px, weight 500 |

Nilai khusus komponen dalam CSS tetap berlaku. Tabel ini bukan izin untuk
menyeragamkan semua radius, ukuran teks, warna status, atau jarak.
Peningkatan keterbacaan yang mengubah ukuran harus ditinjau secara visual.

## 3. Shell dan navigasi

- Desktop: shell padding 20px, gap 24px; sidebar fixed 224px, inset atas/bawah
  20px; konten utama margin kiri 248px; topbar tinggi 61px dan radius 14px.
- Sidebar mempertahankan urutan: Ringkasan, Konten website, Pustaka media,
  Live chat, Keamanan. Akun dan aksi Keluar berada di bagian bawah.
- Navigasi aktif memakai aksen cyan, border/gradien lembut, titik aktif,
  serta `aria-current="page"`. Jangan menggunakan warna sebagai satu-satunya penanda.
- Topbar memuat breadcrumb, pencarian percakapan, akses inbox, dan tautan website.
  Pencarian saat ini mencari percakapan, bukan pencarian global seluruh modul.
- Pertahankan heading, deskripsi singkat, aksi utama, flash keberhasilan, dan footer.

## 4. Kontrak tiap halaman

| Halaman | Struktur yang dipertahankan |
| --- | --- |
| Login | Cerita/identitas di kiri, kartu formulir di kanan; email, password, lupa password; tanpa registrasi atau MFA aktif |
| Ringkasan | Empat kartu statistik, grafik pesan tamu 7 hari, panel website, aktivitas dan aksi cepat |
| Konten website | Lima tab: Identitas & SEO; Intro & halaman; Koleksi tema; Fitur layanan; Konsultasi & footer |
| Konten website | Form utama, ringkasan publikasi, tinjauan isi, riwayat versi; dialog konfirmasi publikasi |
| Pustaka media | Form unggah dan alt text; grid gambar, nama, ukuran, salin URL, pagination |
| Live chat | Daftar percakapan di kiri, pesan dan composer di kanan; status kirim, unread, cari, muat pesan lama |
| Keamanan | Informasi sesi serta ubah password; tidak menampilkan enrollment MFA |
| Chat tamu | Halaman/iframe khusus; nama, pesan awal, konteks tema, percakapan, dan pemulihan email |

Grafik menunjukkan jumlah pesan pengunjung, bukan penjualan atau kunjungan.
Kartu website pada dashboard bersifat ilustrasi, bukan screenshot langsung.
Tinjauan draf adalah ringkasan isi; bukan render identik landingpage.

## 5. Responsif

Pertahankan breakpoint sumber. Aturan max-width bersifat kumulatif.

| Kondisi | Perilaku saat ini |
| --- | --- |
| Lebar >=1600px | Shell maksimal 1680px, sidebar 238px, margin utama 262px |
| Lebar <=1200px | Padding shell 14px, sidebar 205px, margin utama 221px; pencarian topbar disembunyikan |
| Lebar <=1000px | Statistik dua kolom, dashboard satu kolom, ilustrasi website disembunyikan; editor satu kolom dengan ringkasan publikasi di atas; media dua kolom |
| Lebar <=760px | Sidebar drawer 242px dengan backdrop; margin utama nol; topbar 55px; heading bertumpuk; login satu kolom |
| Lebar <=760px | Inbox menampilkan daftar atau percakapan aktif dengan tombol kembali; media tetap dua kolom |

Jangan menghilangkan aksi penting di ponsel. Pesan panjang harus membungkus,
panel pesan bisa digulir, dan composer tetap dapat digunakan.

## 6. Interaksi dan aksesibilitas

- Gunakan `.button.primary`, `.button.secondary`, dan `.button.danger` sesuai tujuan.
  Tombol normal minimal tinggi 39px dan radius 9px; override sumber tetap berlaku.
- Fokus keyboard memakai outline cyan 2px, offset 4px. Pertahankan label formulir,
  aria-label tombol ikon, teks status, dan pesan error yang terbaca.
- Pertahankan dialog native konfirmasi penerbitan. Draf kotor tidak boleh diterbitkan
  atau ditimpa restore melalui tombol UI sebelum disimpan.
- Selama request, tampilkan status dan cegah klik ganda sesuai komponen saat ini.
- Bubble tamu di kiri, admin di kanan; status kirim tidak boleh menyatakan berhasil
  sebelum server mengembalikan pesan tersimpan.
- Pertahankan fallback panel tanpa blur dan `prefers-reduced-motion`.
- Jangan merender input pengguna sebagai HTML mentah untuk mempertahankan tampilan.

## 7. Pemeriksaan perubahan desain

Bandingkan login, dashboard, konten, media, inbox, keamanan, dan chat tamu pada
390px, 760px, 1000px, 1200px, serta 1600px. Periksa kedua sisi setiap breakpoint
yang disentuh. Gunakan data dummy: kosong, banyak item, teks panjang, error,
loading, draf belum disimpan, dan koneksi terputus.

Periksa navigasi keyboard, dialog, fokus, drawer, scrolling pesan, overflow,
kontras teks, dan reduced motion. Ini kriteria pemeriksaan, bukan klaim sudah
lulus WCAG atau uji visual. Snapshot screenshot belum dibuat pada baseline ini.

Perubahan token, struktur, urutan navigasi, breakpoint, atau interaksi utama
mengikuti prosedur `docs/CHANGELOG.md`. Manifest sumber ada di
`docs/baseline/v1.0.0.json`; hash tidak menggantikan perbandingan visual.

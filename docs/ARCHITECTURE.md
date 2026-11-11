# Arsitektur dan batas sistem

Baseline v1.0.0, 9 November 2026. Dokumen ini menjelaskan sumber yang tersedia,
bukan jaminan konfigurasi deployment atau hasil audit keamanan.

## Topologi aktual

```text
Pengunjung -> landingpage (Laravel/Blade/GSAP; lokal :8000)
                  | server HTTP GET konten terbit
                  v
             admin (Laravel/Inertia/Vue/TypeScript; lokal :8001)
                  |-> MySQL admin
                  |-> disk public/media
                  |-> queue database -> email / event broadcast
                  |-> Reverb (lokal :8081)

Pengunjung -> iframe /chat pada origin admin -> cookie chat -> satu percakapan
Admin      -> login origin admin -> workspace -> konten/media/inbox/keamanan
Scheduler  -> maintenance setiap menit; backup database harian
```

Admin adalah repository mandiri dengan composer.json dan package.json sendiri.
Landingpage memiliki kontrak visual sendiri; perubahan admin tidak otomatis
mengizinkan perubahan kode, database, atau desain landingpage.

## Dependensi yang dinyatakan

| Area | Paket/fondasi |
| --- | --- |
| Backend | PHP ^8.3, Laravel ^13.0 |
| Bridge | inertiajs/inertia-laravel ^3.0, @inertiajs/vue3 ^3.0.0 |
| UI | Vue ^3.5.13, TypeScript ^5.2.2, Tailwind ^3.4.1, Inter, Lucide |
| Bundling | Vite ^6.0.3, plugin-vue, laravel-vite-plugin |
| Auth | Fortify ^1.30, session web; MFA tidak diaktifkan |
| Realtime | Reverb, laravel-echo, pusher-js |
| Test | PHPUnit ^12.5; file Pest.php tersisa bukan bukti Pest terpasang |

Ini constraint manifest, bukan daftar versi terpasang. Gunakan lockfile sebagai
acuan versi resolusi. Pinia dan mesin editor belum terpasang menurut package.json.
Tailwind admin versi mayor 3; jangan menyalin asumsi Tailwind 4 dari landingpage.

## Kepemilikan modul

| Lokasi | Tanggung jawab |
| --- | --- |
| routes/web.php | Route aktif workspace, konten publik, dan chat tamu |
| bootstrap/app.php | Pemasangan middleware, alias admin, route console/channel |
| app/Providers/FortifyServiceProvider.php | View auth, seleksi login admin, limiter |
| config/fortify.php | Fitur auth yang benar-benar aktif |
| app/Http/Controllers/ContentController.php | Draft, publish, restore, API published |
| app/Http/Requests/SaveContentRequest.php | Validasi skema konten |
| app/Services/ChatAccess.php | Cookie capability dan sesi chat |
| app/Services/ChatMessages.php | Transaksi, idempotensi, status open, event |
| GuestChatController / ChatRecoveryController | API tamu dan verifikasi/pemulihan |
| InboxController / DashboardController / MediaController | Workspace admin |
| app/Http/Middleware | Admin/session, CSRF, response headers, props Inertia |
| app/Events, app/Notifications | Sinyal realtime dan email |
| app/Console/Commands, routes/console.php | Provision admin, retensi, backup/export |
| resources/js/workspace | Layout, tipe, HTTP client, delivery, realtime |
| resources/js/pages/workspace | Halaman Vue aktif |
| resources/css/app.css | Token dan aturan visual aktual |

File routes/auth.php dan routes/settings.php tersedia, tetapi tidak dimuat oleh
bootstrap atau routes/web.php yang dibaca pada baseline. Jangan mendaftarkannya
secara otomatis: route lama dapat mengubah akses/registrasi yang sekarang ditutup.

## Data utama

| Entitas | Hubungan dan makna |
| --- | --- |
| users | Admin ditandai is_admin; data MFA lama tetap disimpan |
| site_contents | Key landing unik; draft, published, version, published_at |
| content_revisions | Snapshot draft, action, user pembuat; milik site_content |
| conversations | UUID, nama, konteks tema, email terverifikasi, status, aktivitas, read cursor |
| messages | Milik conversation; unique conversation/sender/client_id |
| chat_sessions | Milik conversation; token_hash unik, expires_at, last_seen_at |
| chat_access_links | Milik conversation; token_hash, purpose, email, kedaluwarsa |
| media_assets | Metadata file publik; bukan tabel file privat |

Penghapusan conversation menghapus pesan, sesi, dan tautan melalui foreign-key
cascade. Penghapusan user membuat user_id revisi null. Read cursor adalah milik
conversation, bukan masing-masing admin. Belum ada model tenant pelanggan.

## Batas kepercayaan yang harus dipertahankan

1. **Tamu ke admin:** server memeriksa identitas dan is_admin; UI bukan penjaga akses.
2. **Draf ke publik:** hanya published boleh keluar melalui API publik.
3. **Tamu ke chat lain:** akses berasal dari cookie sesi valid, bukan ID kiriman klien.
4. **Klien ke pengirim pesan:** server memilih sender; client_id hanya kunci retry.
5. **Browser ke pemulihan:** tautan berumur pendek, sekali pakai melalui POST,
   verify memerlukan browser asal, dan akses lama dicabut ketika berhasil.
6. **WebSocket ke data:** channel privat diperiksa; event hanya sinyal, data tetap
   diperoleh melalui HTTP yang berotorisasi.
7. **Unggahan ke publik:** validasi tipe, ukuran, dimensi; file media adalah publik.
8. **Landingpage ke admin:** API baca publik untuk published; origin embedding chat
   dibatasi melalui frame-ancestors dan pemeriksaan postMessage di frontend.

Aspek ini merupakan kontrak yang harus dijaga, bukan daftar kerentanan terkonfirmasi.
Pemeriksaan menyeluruh terhadap deployment, proxy, dependency, dan endpoint auth
di luar middleware workspace belum dilakukan dalam pekerjaan dokumentasi.

## Integrasi dan batas jaminan

- Browser tidak mengambil CMS lintas-origin secara langsung; landingpage melakukan
  HTTP server-side dan validasi payload. Tidak diperlukan penyatuan database.
- Chat berada pada origin admin. Gunakan origin konsisten; localhost dan 127.0.0.1
  bukan host cookie yang sama. Untuk produksi, SameSite Strict memerlukan evaluasi
  topologi same-site dan kebijakan cookie browser untuk iframe.
- Konfigurasi origin Reverb ada di config/reverb.php. Konfigurasi TLS/proxy,
  penerusan cookie/header, serta koneksi WS/WSS harus diuji pada deployment nyata.
- Tautan topbar di WorkspaceLayout.vue masih literal ke localhost:8000;
  DashboardController memakai workspace.landing_url. Ini catatan keterbatasan,
  bukan keputusan untuk mempertahankan localhost di produksi.
- Public API dan client pembacanya harus berubah secara kompatibel atau disertai
  perubahan kontrak dan rollback lintas repository yang eksplisit.

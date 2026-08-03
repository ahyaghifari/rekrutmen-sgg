# Design System — Website Rekrutmen (Syifa Global Group)

Dokumen acuan untuk UI website rekrutmen/karier publik (`resources/views/livewire/**`, `resources/views/components/**`, `resources/css/app.css`). Perbarui dokumen ini setiap kali ada pola baru yang dipakai bersama. Dokumen ini **tidak** mencakup panel admin Filament (`/admin`), yang memakai theming Filament sendiri.

## Stack

- Tailwind CSS v4 (`@import 'tailwindcss'` di `app.css`) + `@tailwindcss/forms`.
- Font: `Plus Jakarta Sans` (`body.site`). `--font-sans` di `@theme` adalah Instrument Sans tapi bukan yang benar-benar dirender situs — jangan mengandalkan itu.
- Ikon: Font Awesome 6 (`fa-solid`, `fa-regular`), dimuat via CDN link di layout/halaman login.
- Dikompilasi via Vite. **Pengguna menjalankan `npm run watch`/`npm run build` sendiri** — jangan pernah menjalankannya sendiri. Kalau perubahan style "tidak muncul", penyebab paling mungkin adalah bundle hasil compile (`public/build/assets/app-*.css`) yang basi, bukan bug kode. Cek mtime file dulu sebelum menganggap ada bug.

## Aturan wajib: inline style di atas Tailwind utility class baru

Codebase ini sudah berkali-kali kena masalah ini: Tailwind utility class yang baru diperkenalkan (`gap-x-*`, `mr-1.5`, arbitrary value seperti `text-[19px]`, `shrink-0`, `lg:col-span-2`, dst.) **tidak reliably muncul** sampai proses rebuild jalan, dan rebuild sering tertinggal dari perubahan kode. Atribut `style="..."` yang ditulis manual selalu langsung tampil karena tidak bergantung pada tahap content-scanning/JIT Tailwind.

**Konvensi di proyek ini:** untuk apa pun di luar utility class dasar yang sudah terbukti aman (`flex`, `items-center`, `justify-between`, `text-sm`, `text-slate-500`, `mb-6`, `p-5`, `rounded-xl`, `border`, `border-gray-200` — semuanya sudah terkonfirmasi jalan di tempat lain), lebih baik pakai inline `style="..."` untuk spacing, gap, grid, dan warna. Ini alasan kenapa sebagian besar file blade komponen di proyek ini terlihat berat inline-style ketimbang utility-class — ini disengaja, bukan asal-asalan.

Identitas visual yang dipakai berulang (family kartu, badge, tombol) tetap ditaruh di `resources/css/app.css` sebagai class CSS asli (lihat di bawah) — itu compile dengan andal karena murni CSS, bukan hasil generate Tailwind.

## Palet warna

| Peran | Hex | Penggunaan |
|---|---|---|
| Primary / navy brand | `#042c6c` | Navigasi, tombol utama, link, focus ring, CTA "Lamar Sekarang", aksen "tahap saat ini" di status lamaran |
| Primary hover | `#032356` | State hover tombol/link navy |
| Hijau sukses | `#047C54` | Identitas "Lamaran Diterima" / lulus tahap, tombol sukses, status "selesai/disetujui" |
| Aksen magenta (langka) | `#B9308C` | Hanya di garis gradient top nav dan beberapa bentuk dekoratif — jangan pernah jadi warna UI utama |
| Netral slate | `#1e293b` (heading), `#334155`/`#374151` (body/value), `#64748b` (secondary/meta — **minimum** untuk teks yang harus terbaca), `#94a3b8` (ikon dekoratif / placeholder native **saja**, lihat aturan kontras di bawah) |
| Latar halaman | `#f1f5f9` (`body.site`) |

### Aturan kontras (jangan sampai regresi)

`#94a3b8` / Tailwind `text-slate-400` punya kontras ~2,8:1 di atas background putih/terang — **gagal WCAG AA**. Ini sudah diaudit dan diperbaiki di seluruh aplikasi (2026-07-05). Aturan ke depannya:

- Teks apa pun yang memang untuk **dibaca** (label, caption, tanggal, header tabel, pesan empty-state, hint pada form lamaran) wajib `#64748b` (`text-slate-500`) atau lebih gelap — jangan pernah `#94a3b8`/`text-slate-400`.
- `#94a3b8` hanya boleh untuk glyph ikon yang murni dekoratif atau styling `input::placeholder` native — bukan untuk konten teks sungguhan.
- Teks identitas/subjudul yang lebih penting dari meta biasa (misalnya nama posisi/jabatan di bawah nama lowongan) harus satu tingkat lebih gelap lagi — `#475569`/`text-slate-600`.
- `.data-table thead th` di `app.css` adalah fix paling krusial di sini — dia mengendalikan setiap header tabel di aplikasi (mis. tabel daftar lamaran di dashboard kandidat).

## Dark mode (rilis 2026-07-11)

Dark mode berlaku di seluruh website rekrutmen. **Tidak mencakup `/admin` atau area internal HR** (theming Filament terpisah, tidak disentuh).

- **Toggle:** manual, key `localStorage` bernama `theme`, default mengikuti `prefers-color-scheme` saat kunjungan pertama. `<x-theme-toggle variant="icon">` ada di `<x-nav>` (desktop). `<x-theme-toggle variant="bar">` ada di `<x-pwa-app-bar>` di sebelah ikon notifikasi (mobile).
- **Mekanisme:** class `dark` di `<html>`. Script anti-FOUC inline di `layout.blade.php` `<head>` (sebelum `@vite`) mengatur ini saat load; `toggleTheme()` (blok script di bawah, file yang sama) membalik dan menyimpan ke `localStorage`.
- **Sistem token:** setiap warna yang theme-aware adalah CSS custom property di `app.css` — `:root { ... }` menyimpan nilai light (tidak berubah dari sebelum dark mode ada, risiko nol), `html.dark { ... }` meng-override-nya. Token inti: `--surface`, `--surface-alt`, `--surface-page`, `--border`, `--border-soft`, `--text-heading`, `--text-body`, `--text-muted`, `--text-faint`, `--brand`, `--brand-success`, `--shadow-card`, `--danger-text`, `--danger-soft-*`, `--info-soft-*`, `--warn-soft-*`, `--neuro-bg`/`--neuro-shadow-*`.

**Aturan ke depan: warna baru apa pun yang menempel di permukaan bertema (background kartu, teks body/meta, border) wajib pakai `var(--token)`, jangan pernah hex literal.** Dua hal yang perlu diketahui sebelum menambah token baru:

1. **Kartu identitas/gradient dikecualikan dengan sengaja** — `.hero-card`, `.job-highlight-card`, `.application-status-card`, `.interview-timeline-card`, `.page-hero*`, `.pwa-action-btn*`, `.pwa-card-header-gold/violet`, badge (`.badge-*`), dan tile aksen kecil yang berdiri sendiri (`.pwa-tile-icon`, `.stat-card-deco`) tetap pakai hex literal dengan sengaja — mereka adalah blok warna tetap yang kontrasnya sudah dibangun sejak awal (biasanya teks putih di atas fill solid/gradient), bukan untuk berbaur dengan tema halaman. Jangan "diperbaiki" dengan menambah `var()` ke elemen-elemen ini.
2. **`--brand`/`--brand-success` berganti ke tint lebih terang di dark mode** (`#042c6c` → `#6ea8e0`, `#047C54` → `#34d399`) khusus supaya teks/angka navy/hijau tetap terbaca di permukaan gelap. Kalau kamu memasukkan hex literal `#042c6c`/`#047C54` langsung ke prop komponen (mis. prop `color="..."` di `stat-card`), warnanya jadi nyaris tak terlihat di dark mode — **ini sudah pernah kejadian sekali** (perbaikan 2026-07-11). Lewatkan `color="var(--brand)"` / `color="var(--brand-success)"` lewat prop seperti ini, jangan pernah hex mentah.

Dua hal lagi yang perlu diketahui kalau kamu menyentuh dark mode lagi:

- **Utility class gray/slate Tailwind** (`bg-white`, `text-gray-500`, `border-gray-200`, dst. — kebanyakan di `job-detail.blade.php` / `application-form.blade.php` / `candidate-profile.blade.php`) tidak dikonversi satu-satu per kemunculan. Sebagai gantinya `app.css` punya aturan blanket `html.dark .bg-white { background-color: var(--surface) !important; }` (dan saudaranya untuk `bg-gray-*`, `text-gray-*`, `border-gray-*`, `text-slate-*`) — lebih murah daripada menulis ulang tiap file, dan aman karena `html.dark` tidak pernah diset di luar layout website rekrutmen (Filament merender root template-nya sendiri, tanpa toggle/script FOUC di sana).
- **Pill warna domain** (`.section-nav-btn.navy/green/rust/teal`, `.doc-type-btn`) butuh override `html.dark` sendiri: tampilan light mode adalah tint rgba alpha sangat rendah (`rgba(4,44,108,0.06)`) yang praktis tidak terlihat di permukaan gelap. Varian dark menaikkan alpha dan mengganti ke warna aksen lebih terang per domain — lihat blok `html.dark .section-nav-btn.*` tepat setelah `.section-nav-btn` di `app.css`. Kalau menambah pill warna domain baru, beri perlakuan yang sama.

## Sistem identitas kartu

Setiap "domain" di website rekrutmen punya **satu** treatment kartu gradient/solid gelap yang dikenali, dipakai ulang di mana pun domain itu muncul (Beranda, Detail Lowongan, Dashboard Kandidat, dst.) supaya pengguna mengenalinya sekilas tanpa perlu membaca teks. Jangan pakai ulang warna identitas domain lain untuk fitur baru — beri warna sendiri kalau memang domain baru, tapi jangan bikin baru untuk sesuatu yang sudah cocok dengan family kartu yang ada.

| Class | Domain | Tampilan |
|---|---|---|
| `.hero-card` | Sambutan / pencarian lowongan cepat | Gradient navy `#042c6c → #0b47a1`, teks putih, lingkaran dekoratif `.h-deco-*` |
| `.job-highlight-card` | Lowongan unggulan / rekomendasi (`.j-deco-*`) | Gradient hijau `#047C54 → #024732`, teks putih |
| `.application-status-card` | "Lamaran Butuh Tindakan Anda" (lamaran yang perlu dilengkapi/diproses — Dashboard Kandidat, area HR internal) | Gradient oranye solid-feeling `#9a3412 → #ea580c → #fb923c` (stop lebih gelap dijangkarkan di bawah teks header untuk kontras), teks putih, `.appr-deco-*` |
| `.interview-timeline-card` | Timeline tahap seleksi (`x-application-timeline` component: Lamaran Masuk → Screening → Wawancara → Offering → Diterima) | Kuning pastel solid `#FCF0D6` dengan border `#F0DBA3` (tanpa gradient — sengaja flat), teks **gelap** (`#422006` / `#374151` / `#64748b`), `.tl-deco-*` |
| `.neuro` | Widget statistik ringkas (jumlah lamaran masuk, lowongan aktif) | Neumorphic double-shadow terang (`6px 6px 14px #c5ccd8, -6px -6px 14px #fff`), base abu-abu sejuk `#e8edf6` |
| `.stat-card` | Kartu statistik putih polos | Putih flat, shadow tipis, `.stat-card-deco` lingkaran sudut samar |

Semua kartu identitas gelap/berwarna mengikuti anatomi yang sama: `border-radius:20px`, `position:relative;overflow:hidden`, 2–3 bentuk dekoratif (lingkaran/kotak rotasi) opasitas rendah (`.{prefix}-deco-1/2/3`), konten dibungkus dalam div sendiri `position:relative;z-index:1` supaya berada di atas dekorasi.

**Aturan warna teks per tipe kartu:** kartu gradient gelap/saturasi tinggi (navy, hijau, oranye tua) → teks putih. Kartu terang/pastel (kuning timeline seleksi) → teks bernuansa hangat gelap (family `#422006`), jangan pernah putih — putih di atas kuning langsung gagal kontras.

**Catatan Soft-UI (neumorphism):** neumorphism pada dasarnya teknik minim warna — ilusi "terangkat dari satu permukaan" bergantung pada elemen dan background yang berdekatan hue/lightness-nya, dengan kedalaman datang dari pasangan shadow terang/gelap, bukan warna. Kalau komponen butuh kesan raised-lembut sekaligus warna identitas kuat, pola yang berhasil di aplikasi ini: permukaan raised netral (shadow yang membawa kedalaman) + **ring border** berwarna + **ikon** berwarna — bukan fill warna solid. Lihat badge tahap timeline seleksi (`application-timeline.blade.php`) sebagai referensi implementasi.

## Komponen

- **Badge**: `.badge` + `.badge-diterima` (hijau) / `.badge-diproses` (amber) / `.badge-ditolak` (merah) / `.badge-dibatalkan` (abu-abu). Selalu pasangkan kedua class ini — dipakai untuk status lamaran (Diterima, Diproses/Screening, Ditolak, Dibatalkan).
- **Tombol**: `.btn-primary` (navy solid — untuk CTA "Lamar Sekarang", "Kirim Lamaran"), `.btn-ghost` (outline abu-abu, untuk konteks background terang), `.qa-btn` (pill putih translucent, untuk tombol di atas kartu gradient gelap — mis. di dalam `.hero-card`), `.section-nav-btn` (ikon + label, warna sesuai domain, active = fill solid — untuk berpindah antar bagian dalam satu halaman, lihat konvensi Layout di bawah).
- **Tabel**: `.data-table` — header uppercase `#64748b`, tint hover baris, baris terakhir tanpa border. Dipakai untuk setiap daftar (Daftar Lowongan, Riwayat Lamaran, Daftar Kandidat per posisi, dst.)
- **Form**: `.form-label`, `.form-input`, `.toggle`, `.time-grid`/`.date-grid` untuk input berpasangan, `.upload-zone` untuk area drop file (CV, surat lamaran, portofolio, dokumen pendukung). Ini berasal dari mockup HTML di `planning/web-attendance/demo/` dan sebaiknya tetap konsisten dengan desain sumber itu — jangan reinvent styling form per halaman.
- **Pola Field-row** (label kiri, value kanan, hairline divider): dipakai di tab profil kandidat (`candidate-profile.blade.php`) untuk daftar label/value yang mudah dipindai. Utamakan pola ini dibanding grid cell label-di-atas-value untuk apa pun dengan lebih dari ~4 field (data diri, riwayat pendidikan, pengalaman kerja).

## Konvensi layout

- Pola header halaman: **pakai `<x-page-header title="..." />`** (`resources/views/components/page-header.blade.php`) — setiap header halaman (tanggal + jam live + judul, prop opsional `subtitle`/`back`, `size="lg"` untuk Beranda/Dashboard) lewat satu komponen ini. Jangan hand-roll markup tanggal/judul per halaman lagi; itu state pra-refactor dan dulu drift (9 salinan hampir sama, sebagian tanpa jam live). Kalau ada halaman butuh bentuk header yang belum bisa dilakukan komponen, extend komponennya dengan prop baru, jangan bypass.
- Section nav (saat halaman punya beberapa concern, mis. Detail Lowongan dengan Deskripsi Pekerjaan / Persyaratan / Cara Melamar, atau Profil Kandidat dengan Data Diri / Riwayat Lamaran / Dokumen): plain Alpine (`x-data="{ tab: '...' }"`, `x-show`) untuk state, tapi dirender sebagai **pill button** sungguhan (`.section-nav` + `.section-nav-btn`), bukan underline tabs — underline tabs terbaca seperti teks polos di mobile dan gampang salah tap. `.section-nav` bersifat `flex-wrap`, bukan grid kolom tetap, jadi menambah tombol baru nanti cukup wrap ke baris berikutnya tanpa perlu perubahan CSS. Setiap tombol dapat warna domainnya sendiri (modifier `.navy`/`.green`/`.rust`, sesuai sistem identitas kartu di atas) plus ikon Font Awesome, active state = fill warna solid.
- Grid responsif multi-kartu: utamakan `display:grid;grid-template-columns:repeat(auto-fit,minmax(Npx,1fr));gap:...` inline dibanding class `.card-pair`/bento-grid saat halaman sekitarnya bukan bento grid Beranda — `.card-pair` khusus bergantung pada `display:contents` + parent bento grid dengan class penempatan `grid-column` bernama, dan tidak berfungsi apa-apa di luar konteks itu.
- Data multi-field padat (data diri kandidat, riwayat pekerjaan) pakai pola field-row di atas, bukan grid CSS mentah dari sel label/value yang ditumpuk — lebih mudah dipindai baris per baris.

## Navigasi mobile — bottom nav, bukan hamburger drawer (2026-07-12)

Versi mobile situs rekrutmen dulu punya tombol hamburger di `<x-pwa-app-bar>` yang membuka drawer off-canvas `<x-pwa-sidebar>`. **Keduanya sudah dihapus** — harus menjangkau pojok kiri atas di setiap navigasi ditandai sebagai UX mobile yang buruk. Navigasi mobile sekarang `<x-pwa-bottom-nav>` (`components/pwa-bottom-nav.blade.php`), dimasukkan sekali di `layout.blade.php` di dalam `<div class="md:hidden">`, tepat sebelum `@livewireScripts`.

- **5 slot tetap**, kiri ke kanan: Beranda (`dashboard`), Cari Lowongan (`job-listing`), **tombol tengah** melingkar terangkat, Lamaran Saya (`my-applications`) — diganti ke Kandidat Saya untuk pengguna internal HR yang mengelola lowongan (`canManageJobPostings()`), Akun (`akun`).
- **Tombol tengah** (`.pwa-bottom-nav-center-btn`, lingkaran navy solid mengambang di atas bar) membuka bottom sheet (`.pwa-menu-sheet` + `.pwa-menu-sheet-backdrop`, `togglePwaMenuSheet()` di komponen yang sama) alih-alih navigasi langsung — shortcut di dalamnya tergantung peran dan satu pengguna bisa punya lebih dari satu (mis. staf HR yang juga admin lowongan melihat tile Kelola Lowongan dan Kandidat sekaligus), jadi tidak bisa disederhanakan jadi satu tujuan saja.
- Konten sheet berupa **grid ikon 3 kolom** (`.pwa-menu-grid` / `.pwa-menu-grid-item` / `.pwa-menu-grid-icon.{navy,gold,violet,green,teal}`), satu tile warna solid per shortcut — variabel guard sama seperti sebelumnya (`canAccessRecruiterMenu()`, permission `SISTEM_REKRUTMEN`, `canPostJob`) yang mengatur Kelola Lowongan / Kandidat Masuk / Jadwal Wawancara / Laporan Rekrutmen.
- **Tidak** ada di sheet atau bottom nav: Ubah Password, Logout, Panel Admin. Ubah Password + Logout hanya ada di halaman Akun (`resources/views/livewire/pwa/akun.blade.php`) — jangan ditambahkan lagi ke sheet, itu akan duplikat. Panel Admin dihilangkan karena role Admin/Super Admin diarahkan langsung ke `/admin` saat login dan tidak pernah benar-benar berada di website publik.
- `.page-content` mendapat `padding-bottom` ekstra di bawah 768px (lihat media query tepat setelah aturan `.page-content` di `app.css`) supaya konten halaman tidak terender di bawah bottom bar fixed — kalau menambah elemen fixed-position mobile lain, cek padding ini masih menutupinya.
- Judul app bar (`.pwa-app-bar-title`) dinaikkan jadi `18px` sekarang tombol hamburger sudah hilang dan ada lebih banyak ruang.

## Persyaratan responsif — setiap halaman wajib punya layout mobile, bukan sekadar versi desktop yang diperkecil

Ini aturan wajib, bukan nice-to-have: **setiap halaman website rekrutmen harus didesain untuk mobile dan desktop**, bukan sekadar dibungkus `overflow-x:auto` lalu dianggap selesai. Breakpoint-nya Tailwind `md:` (768px), sama seperti pergantian `<x-pwa-app-bar>`+`<x-pwa-bottom-nav>` (mobile) dan `<x-nav>` (desktop) di `components/layout.blade.php`.

**Polanya** (lihat `JobListing`/`job-listing.blade.php`, `MyApplications`/`my-applications.blade.php`, dan `pending-applications-card.blade.php` sebagai referensi implementasi):
- Data yang enak dibaca sebagai tabel di desktop (Filament Table, `.data-table`) menjadi **layout kartu/list di mobile** (`.pwa-list` + `.pwa-list-row` + `.pwa-tile-icon`, komponen yang sama dari redesign PWA) — bukan tabel yang sama yang dipaksa scroll horizontal. Render keduanya, bungkus versi mobile dengan `<div class="md:hidden">` dan versi desktop dengan `<div class="hidden md:block">` (atau `md:grid`/`md:flex` kalau varian desktop butuh display type itu).
- Komponen yang muncul di banyak konteks (`<x-application-timeline>`, kartu statistik lowongan aktif/lamaran masuk) sebaiknya mengurus perilaku responsifnya sendiri secara internal (render kedua varian, toggle lewat pola `md:hidden`/`hidden md:block` yang sama) ketimbang menerima prop boolean `vertical`/`compact` yang memaksa pemanggil memilih satu — pemanggil seharusnya tidak perlu tahu atau menebak viewport.
- Saat komponen butuh mesin query/pagination Filament SEKALIGUS query manual terpisah untuk daftar kartu mobile (mis. `MyApplications`, `JobListing` untuk kandidat/recruiter): ekstrak query bersama ke satu method `baseQuery()`/protected, panggil dari method `table()` Filament maupun `getXProperty()` yang dipakai Blade mobile — jangan duplikasi logic scoping-nya.
- Pagination: kalau komponen sudah pakai `Filament\Tables\Concerns\InteractsWithTable` (yang secara internal menyusun `Livewire\WithPagination`, di-alias supaya tidak bentrok), **jangan** juga menambahkan `use WithPagination;` langsung — itu trait method collision (fatal error). `->paginate(n)` polos tetap jalan untuk query mobile tanpa itu.

**Jebakan cascade-layer yang harus dihindari:** jangan gabungkan `hidden`/`md:hidden` Tailwind dengan class `app.css` custom yang juga mengeset `display` tanpa syarat pada *elemen yang sama* — CSS custom di proyek ini unlayered (ditulis setelah `@import 'tailwindcss'` tanpa wrapper `@layer`), jadi selalu menang cascade dibanding utility Tailwind yang layered, bahkan lintas media query yang cocok. Konkretnya: `<div class="md:hidden my-custom-grid-class">` dengan `.my-custom-grid-class { display: grid; }` yang didefinisikan tanpa media query akan **tetap terlihat di atas breakpoint `md`** karena aturan unlayered mengalahkan aturan `.md\:hidden` yang layered. Perbaikan: beri class custom itu pasangan `display: none` + `@media (min-width: 768px) { display: grid }` sendiri (lihat `.bento-side-stats-compact`/`.bento-side-stats` di `app.css`) ketimbang mengandalkan utility visibility Tailwind untuk menyembunyikannya.

## Testing & safety (tidak terkait desain visual, tapi tetap krusial)

- `phpunit.xml` wajib selalu `DB_CONNECTION=sqlite` / `DB_DATABASE=:memory:`. Jangan pernah mengarahkannya ke database sungguhan — lihat `knowledge/changes/2026-07-05-*.md` untuk insiden yang pernah terjadi karena ini.
- Jangan menjalankan atau mengusulkan `php artisan test` kecuali diminta secara eksplisit, terutama saat iterasi visual/CSS cepat — itu noise yang tidak diminta pengguna saat itu.

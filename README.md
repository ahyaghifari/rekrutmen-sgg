# Rekrutmen SGG

Portal rekrutmen untuk Syifa Global Group (multi-company). Dipisah dari sistem HRIS internal karena fungsi rekrutmen (form lamaran, seleksi, wawancara, tes online, MCU, TTD & onboarding) berbeda kebutuhan dari HRIS (payroll, absensi, dll).

Lihat [`knowledge/PRD.md`](knowledge/PRD.md) untuk latar belakang dan alur bisnis.

## Stack

- **Laravel 12** + Breeze (Blade) + Tailwind CSS v3 + Alpine.js
- **Filament v3** — panel admin di `/kelola-rekrutmen`
- **Filament Shield** + **Spatie Permission** — RBAC (role: `Super Admin`, `Admin`)
- **MySQL** — primary database
- Deploy: aaPanel, domain `karir.syifaglobalgroup.com`

## Alur rekrutmen

```
Lamar → Seleksi Berkas → Shortlisted → Tes Online → Wawancara → MCU* → TTD & Onboarding
```

\* MCU kondisional per perusahaan (`Company.requires_mcu`). Psikotes kondisional per lowongan (`JobOpening.requires_psychotest`).

## Fitur yang sudah ada

| Fitur | Keterangan |
|---|---|
| Form lamaran | Menggantikan Google Form, auth wajib |
| Seleksi berkas | Admin filter pelamar, ubah status |
| Riwayat status | Otomatis dicatat via `JobApplication::booted()` |
| Tes online | Kuis in-system, multi-kuis per lowongan, timer, auto-submit, skor otomatis |
| Wawancara | Jadwal, undangan, hasil (lulus/tidak) |
| MCU | Kondisional per perusahaan, hasil + catatan kesehatan |
| TTD & Onboarding | Notifikasi undangan onboarding |
| Data diri kandidat | Form lengkap sisi pelamar (8 tabel anak kandidat) |
| Multi-company | Setiap Admin hanya lihat data perusahaannya |
| HRIS integration | Receiver API (Bearer token) — upsert lowongan + jumlah pelamar |
| Audit lowongan | `created_by/updated_by/deleted_by` + soft delete |
| Export pelamar | Export Excel per lowongan |

## Yang belum ada

- **Offering letter** — model + tabel sudah ada, belum ada UI/controller
- **Halaman Notifikasi** — dihapus sementara, belum dibuat ulang
- **Persetujuan digital kandidat** — `CandidateAgreement` model ada, belum ada halaman

## Setup lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
# isi DB_*, APP_URL, MAIL_*, REKRUTMEN_API_TOKEN di .env
php artisan migrate
php artisan db:seed
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan storage:link
npm install && npm run dev
```

## Deploy ke server (setelah `git pull`)

```bash
composer install --no-dev
php artisan migrate --force
php artisan optimize
chmod -R 775 storage bootstrap/cache
chown -R www:www storage bootstrap/cache
```

## Akses panel admin

URL: `/kelola-rekrutmen`

Role `Super Admin` — akses penuh semua perusahaan.
Role `Admin` — hanya data perusahaan yang ditautkan ke akun.

## Git workflow

Lihat [`GIT-WORKFLOW.md`](GIT-WORKFLOW.md) untuk panduan branch `dev` vs `main`.
Push ke `main` = langsung deploy ke server.

## Konfigurasi HRIS API

Tambah ke `.env`:

```
REKRUTMEN_API_TOKEN=<token>
```

Endpoint (Bearer token auth):
- `GET /api/hris-test` — health check
- `PUT /api/integrations/hris/job-openings/{id}` — upsert lowongan
- `POST /api/integrations/hris/job-openings/applicant-counts` — jumlah pelamar batch

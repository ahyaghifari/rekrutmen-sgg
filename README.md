# Rekrutmen SGG

Portal rekrutmen untuk Syifa Global Group (multi-company). Dipisah dari sistem HRIS internal (modul `/candidate`) karena fungsi rekrutmen (form lamaran, seleksi, wawancara, offering letter, kontrak digital) berbeda kebutuhan dari HRIS (payroll, absensi, dll).

Lihat [`knowledge/PRD.md`](knowledge/PRD.md) untuk latar belakang, alur bisnis (as-is/to-be), dan referensi skema data kandidat dari HRIS ([`knowledge/candidate_dump.sql`](knowledge/candidate_dump.sql)).

Project ini masih Laravel skeleton kosong (belum ada migration/model/route custom) — PRD di atas adalah acuan untuk pengembangan berikutnya.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run dev
```

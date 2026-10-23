# 2026-07-29 — Planning: Struktur Database Awal

Status: **planning, belum dieksekusi.** Hasil diskusi di sesi ini, dasar keputusan lengkap ada di [`knowledge/PRD.md`](../knowledge/PRD.md). Dokumen ini fokus ke *apa yang akan dibuat* (migration & urutan kerja), bukan alasan bisnisnya.

## Yang sudah beres (tidak perlu diulang)
- Laravel 12 + Tailwind v4 sudah terpasang & jalan.
- Laravel Breeze (stack Blade) sudah ter-install, migrasi `users` bawaan sudah jalan ke `rekrutmen_db` (MySQL lokal, terpisah dari `hris_db`).

## Prinsip struktur (lihat `knowledge/PRD.md` §4 untuk alasan lengkap)
- 3 lapis: `users` (auth) → `job_applications` (form lamaran, 1:1 field dengan Google Form lama) → `candidates` (profil resmi lengkap, dibuat setelah lolos wawancara).
- `candidates.user_id` unique — 1 orang = 1 profil, **bukan** 1 lamaran = 1 profil.
- `job_applications.candidate_id` nullable, diisi begitu status naik jadi `passed`.
- `job_openings` = cache lokal read-only (dummy/seed dulu, nanti sync dari API HRIS). `manpower_requests` tidak dibuat sama sekali — di luar cakupan.
- Aturan bisnis: 1 user hanya boleh punya 1 `job_applications` berstatus aktif (`applied`/`screening`/`interview`) dalam satu waktu — divalidasi di level aplikasi, bukan constraint DB.

## Daftar migration yang akan dibuat (urutan)

1. **`job_openings`** (cache lokal, dummy/seed)
   - `id` (ulid), `hris_job_opening_id` (string, nullable — id asli dari HRIS buat mapping pas sinkron nanti), `title`, `company_name`, `quota` (int), `is_active` (bool), `description` (text, nullable), `requirements` (text, nullable), timestamps.

2. **`job_applications`** (form lamaran — field lengkap ada di `knowledge/PRD.md` §4a)
   - `id` (ulid), `user_id` (FK `users`), `job_opening_id` (FK `job_openings`), `candidate_id` (FK `candidates`, nullable — diisi belakangan), `other_position_note`, `full_name`, `gender` (enum), `whatsapp_number`, `age`, `domicile_address`, `birth_place` (nullable), `birth_date`, `instagram_handle`, `linkedin_url` (nullable), `education_level` (enum), `major`, `school_name`, `graduation_year`, `str_valid_until` (nullable), `work_experience_note` (text), `willing_relocate` (bool), `last_salary` (decimal), `expected_salary` (decimal), `availability` (enum), `certificate_file_path` (nullable), `cv_file_path`, `status` (enum: `applied`/`screening`/`interview`/`passed`/`rejected`, default `applied`), timestamps, soft delete.

3. **`candidates`** (profil resmi, dibuat pas status `passed`)
   - `id` (ulid), `user_id` (FK `users`, unique), `name` (pre-filled dari `job_applications.full_name`, bisa dikoreksi), NIK/KK, alamat KTP resmi (village/district/city/province terpisah, beda dari `domicile_address` yang sudah ada di `job_applications`), data BPJS, rekening bank, foto profil, `overall_status`, timestamps, soft delete.
   - Field yang **tidak** diulang di sini karena sudah ada di `job_applications`: nama dasar, gender, WA, tanggal lahir, pendidikan terakhir (level/jurusan/universitas/tahun lulus).

4. **Child table kandidat** (ikut pola `candt_*` HRIS, semua FK ke `candidates.id`):
   - `candidate_family_members`
   - `candidate_educations` (riwayat pendidikan tambahan selain yang sudah di `job_applications`, mis. jenjang sebelumnya)
   - `candidate_trainings`
   - `candidate_organizations`
   - `candidate_work_experiences` (histori terstruktur — beda dari `job_applications.work_experience_note` yang freetext)
   - `candidate_references`
   - `candidate_additional_infos`
   - `candidate_self_assessments`
   - `candidate_documents` (KTP, ijazah, dll — beda dari `cv_file_path`/`certificate_file_path` yang sudah di `job_applications`)

5. **Fitur baru (belum ada di HRIS sama sekali):**
   - `offering_letters` — FK `job_application_id`, template/isi surat, status kirim (`draft`/`sent`/`viewed`), timestamps.
   - `candidate_agreements` — FK `candidate_id`, jenis persetujuan (kontrak/peraturan/tata tertib), `agreed_at`, `ip_address`, timestamps. (Masih pending klarifikasi kekuatan hukum — lihat pertanyaan terbuka di PRD §8.)
   - Tabel kontrak final (nama & struktur belum diputuskan — nunggu diskusi lanjutan soal penandatanganan kontrak).

## Belum diputuskan / nunggu konfirmasi sebelum eksekusi
- Nama pasti kolom-kolom di atas (di atas masih usulan, boleh dikoreksi sebelum migration ditulis).
- Struktur tabel kontrak final.
- Mekanisme sync `job_openings` dari HRIS (cron vs on-demand) — untuk sekarang cukup seeder dummy.
- Role/permission (HC recruiter vs admin) — belum ada tabel `roles`/`permissions` di rencana ini, ditambah nanti kalau dibutuhkan.

## Next step (menunggu izin eksekusi)
Setelah struktur di atas dikonfirmasi, urutan kerja: migration `job_openings` → `job_applications` → `candidates` + child tables → `offering_letters`/`candidate_agreements`, lalu model + factory/seeder dummy untuk `job_openings`.

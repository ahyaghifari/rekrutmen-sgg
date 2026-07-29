# PRD — Portal Rekrutmen Syifa Global Group (SGG)

Status: draft awal, disusun dari diskusi 2026-07-29. Update dokumen ini seiring keputusan berubah.

## 1. Latar belakang

Rekrutmen SGG saat ini menyatu dengan sistem HRIS internal (multi-company). Modul kandidat di HRIS hidup di route `/candidate`, dengan skema tabel yang didokumentasikan di [`candidate_dump.sql`](./candidate_dump.sql) (dump dari `hris_db`). Karena fungsi rekrutmen (sourcing, seleksi, wawancara, kontrak) berbeda kebutuhan dari fungsi HRIS (payroll, absensi, struktur organisasi, dst — lihat [`DESIGN.md`](./DESIGN.md) untuk referensi UI portal HRIS yang sudah ada), rekrutmen dipisah jadi aplikasi sendiri: **repo ini**.

Perusahaan: Syifa Global Group (holding, multi-company — lihat `m_companies` di HRIS).

## 2. Alur saat ini (as-is)

1. Pelamar melihat info lowongan dari post Instagram.
2. Pelamar mengisi [Google Form](https://docs.google.com/forms/d/e/1FAIpQLSfubuULwdl_w1nHcBuD0KJ12FoJItM76JzBQa6MzkN1t_2P_w/viewform).
3. Tim HC memfilter jawaban form secara manual dan menyeleksi kandidat.
4. Kandidat terpilih dipanggil wawancara (prosesnya di HRIS).
5. Kandidat yang diterima diarahkan ke `/candidate` di HRIS untuk membuat akun, mengisi data diri lengkap (data pribadi, keluarga, pendidikan, pengalaman kerja, dst — lihat skema di §4).
6. Proses berakhir di penandatanganan kontrak (fisik/manual) → onboarding.

Titik lemah: form Google Form terlepas dari sistem (data harus dipindah manual), tidak ada tracking status lamaran yang terpusat, kontrak & aturan masih manual saat onboarding.

## 3. Alur ke depan (to-be) — cakupan aplikasi ini

1. **Form pelamar dipindah ke aplikasi ini**, menggantikan Google Form. Pelamar **daftar akun dulu** (auth: Laravel Breeze, stack Blade — sudah terpasang), lalu mengisi form lamaran (`job_applications`, field 1:1 dengan Google Form lama — lihat §4a).
2. Data lamaran difilter oleh HC langsung di sini (ubah `status`/`current_stage` di `job_applications`) menjadi "kandidat".
3. Kandidat dipanggil **wawancara** — status & jadwal wawancara di-track di aplikasi (menggantikan `recruitment_processes` step manual di HRIS).
4. Kandidat yang **lolos wawancara**:
   - Baru di titik ini row `candidates` dibuat (profil lengkap: NIK, KK, alamat resmi, keluarga, dst — lihat §4b), pre-filled dari data yang sudah ada di `job_applications`/`users` supaya tidak tanya ulang.
   - Menerima **offering letter** terkirim lewat sistem (fitur baru).
   - Diminta membaca dan **menyetujui kontrak kerja, peraturan, dan tata tertib secara digital** (persetujuan/consent digital — fitur baru) — tujuannya memangkas waktu tanda tangan fisik saat onboarding.
5. Setelah offering + persetujuan digital selesai, kandidat lanjut ke **penandatanganan kontrak** → selesai (serah terima ke HRIS untuk onboarding/payroll dst., di luar cakupan app ini).

### Fitur baru yang direncanakan
- Form pelamar mandiri (replace Google Form).
- Filtering/seleksi pelamar → kandidat oleh HC (mirip proses manual saat ini, tapi terpusat).
- Manajemen wawancara & hasil wawancara.
- Pengiriman **offering letter** (kemungkinan lewat email, template + tracking status terkirim/dibuka).
- **Digital agreement**: kandidat membaca kontrak/peraturan/tata tertib dan memberi persetujuan digital (bukan e-signature legal penuh, cukup checkbox/consent record — perlu diklarifikasi kebutuhan hukumnya).
- Penandatanganan kontrak (final step sebelum handoff ke HRIS `/candidate` untuk onboarding).

## 4. Struktur data — app ini

Tiga lapis, supaya tidak ada data yang diketik dua kali (lihat diskusi §6):

1. **`users`** (Breeze, auth only): `name` (nama akun/tampilan, bukan nama resmi), `email`, `password`. `email` adalah satu-satunya sumber email — tidak diduplikasi ke tabel lain, selalu diakses lewat relasi `user_id`.
2. **`job_applications`** — form lamaran, dipakai **saat submit**, field 1:1 dengan Google Form lama (source: `knowledge/google form/*.png`). Ini yang menggantikan Google Form.
3. **`candidates`** — profil resmi lengkap, **baru dibuat saat status lolos wawancara**. Field-nya adalah field yang belum tercakup di `job_applications` (NIK, KK, alamat KTP resmi per kelurahan/kecamatan, data keluarga, BPJS, rekening, dst — mengikuti pola `candidates` + `candt_*` di HRIS, §5). `candidates.name` di-pre-fill dari `job_applications.full_name` (bisa dikoreksi, tidak wajib ketik ulang dari nol).

### 4a. `job_applications` — field sesuai Google Form (urutan asli)

| # | Field form | Kolom | Tipe/catatan |
|---|---|---|---|
| — | Email | *(tidak disimpan di sini — dari `users.email` via `user_id`)* | |
| 1 | Nama Lengkap | `full_name` | string, wajib |
| 2 | Jenis Kelamin | `gender` | enum `laki_laki`/`perempuan`, wajib |
| 3 | No WA | `whatsapp_number` | string, wajib |
| 4 | Usia | `age` | unsigned int, wajib |
| 5 | Alamat Domisili | `domicile_address` | text, wajib |
| 6 | Tempat Lahir | `birth_place` | string, opsional |
| 7 | Tanggal Lahir | `birth_date` | date, wajib |
| 8 | Instagram | `instagram_handle` | string, wajib |
| 9 | Akun LinkedIn | `linkedin_url` | string, opsional |
| 10 | Jenjang Pendidikan | `education_level` | enum `sma_smk`/`d3`/`d4`/`s1`/`s2`/`profesi`, wajib |
| 11 | Jurusan | `major` | string, wajib |
| 12 | Nama Universitas/Instansi | `school_name` | string, wajib |
| 13 | Tahun Lulus | `graduation_year` | year, wajib |
| 14 | Tanggal Berlaku STR (nakes) | `str_valid_until` | date, opsional — khusus posisi tenaga kesehatan |
| 15 | Posisi yang dilamar | `job_opening_id` | FK ke `job_openings` (cache dari HRIS, §4c), wajib |
| 16 | Posisi lain | `other_position_note` | string, opsional |
| 17 | Pengalaman Kerja | `work_experience_note` | text bebas (form aslinya freetext, bukan tabel terstruktur) |
| 18 | Bersedia ditempatkan Luar Kota | `willing_relocate` | boolean, wajib |
| 19 | Gaji Terakhir | `last_salary` | decimal, wajib |
| 20 | Gaji yang diharapkan | `expected_salary` | decimal, wajib |
| 21 | Bersedia Bergabung | `availability` | enum `immediately`/`2_weeks`/`1_month`/`more_than_2_months`, wajib |
| 22 | File Sertifikat Keahlian | `certificate_file_path` | opsional |
| 23 | File CV | `cv_file_path` | wajib, PDF |
| — | — | `status` | enum `applied`/`screening`/`interview`/`passed`/`rejected`, default `applied` — menggantikan filter manual HC |
| — | — | `user_id` | FK `users`, wajib |

**Aturan bisnis: 1 pelamar hanya boleh punya 1 lamaran aktif dalam satu waktu.** Tidak bisa melamar lowongan lain sebelum lamaran yang berjalan selesai (`status` jadi `passed` atau `rejected`). Ini bukan constraint DB (MySQL tidak punya partial unique index untuk "unique selama status aktif") — di-enforce di level aplikasi: sebelum insert `job_applications` baru, cek dulu tidak ada row lain milik `user_id` yang sama dengan `status` masih `applied`/`screening`/`interview`.

### 4b. `candidates` — dibuat setelah lolos wawancara

`candidates.user_id` — **unique FK ke `users`, bukan ke `job_applications`/`job_openings`.** Data di `candidates` (NIK, KK, alamat resmi, keluarga, dst) melekat ke orangnya, bukan ke lamaran tertentu — kalau di-FK ke `job_application`, orang yang melamar 2 posisi dan dua-duanya lolos wawancara akan punya 2 row `candidates` duplikat. Jadi arah relasinya: satu orang = satu `candidates` (dibuat sekali, saat lamaran pertamanya lolos wawancara), lalu `job_applications.candidate_id` (nullable FK ke `candidates`) diisi begitu status naik jadi `passed` — kalau orang yang sama melamar lagi nanti, tinggal disambungkan ke `candidates` yang sudah ada, tidak bikin baru.

Field detail identitas resmi + child table (`candt_educations`, `candt_family_members`, `candt_work_experiences`, `candt_references`, `candt_additional_infos`, `candt_self_assessments`, `candidate_documents`) mengikuti pola HRIS di §5 — dikurangi field yang sudah ada di `job_applications`/`users` (tidak diulang). Field baru khusus app ini menyusul di `candidates`/tabel terkait: **offering letter**, **candidate_agreements** (persetujuan digital kontrak & tata tertib).

### 4c. `job_openings` (dan `manpower_requests`)

`manpower_requests` tidak relevan di app ini (murni internal HC di HRIS). `job_openings` tetap perlu ada **sebagai cache lokal read-only** (agar `job_applications.job_opening_id` punya FK yang valid) — bukan sumber kebenaran. Untuk sekarang diisi data dummy/seed; ke depan disinkron dari API HRIS (bukan ditulis manual dari app ini).

## 5. Referensi data model (dari HRIS)

Skema di bawah adalah tabel-tabel `hris_db` yang menyimpan data kandidat saat ini (lihat dump lengkap di [`candidate_dump.sql`](./candidate_dump.sql)). Aplikasi ini **tidak otomatis mewarisi database HRIS** — skema ini jadi acuan desain tabel baru di app ini (nama tabel/kolom boleh disesuaikan, migrasi Laravel akan dibuat dari nol).

| Tabel HRIS | Fungsi |
|---|---|
| `manpower_requests` | Permintaan penambahan tenaga kerja per posisi/company (approval internal HC/manajemen) |
| `job_openings` | Lowongan aktif per company + posisi, kuota, deskripsi & requirement |
| `candidates` | Data induk pelamar/kandidat: identitas, alamat, pendidikan terakhir, BPJS, dll. Login pakai email+password sendiri (bukan `users` HRIS) |
| `job_applications` | Lamaran: relasi candidate ↔ job_opening, `current_stage`, `status`, `approval_status` |
| `recruitment_processes` | Tahapan seleksi per lamaran (`process_type`, `sequence`, `status`, `score`, `feedback`) — ini yang mewakili wawancara & tahap lain |
| `candidate_documents` | Dokumen upload kandidat (KTP, ijazah, dll) |
| `candt_family_members` | Data keluarga |
| `candt_educations` | Riwayat pendidikan |
| `candt_trainings` | Riwayat pelatihan |
| `candt_organizations` | Riwayat organisasi |
| `candt_work_experiences` | Riwayat kerja |
| `candt_references` | Referensi/rekomendasi |
| `candt_additional_infos` | Info tambahan (kesehatan, hobi, bahasa, ekspektasi gaji) |
| `candt_self_assessments` | Penilaian diri (kekuatan, kelemahan, kepuasan) |

**Catatan pemetaan ke app baru:**
- `candidates` di HRIS sebenarnya adalah gabungan "pelamar awal" + "kandidat yang sudah isi profil lengkap" (dibedakan oleh `overall_status`, `is_onboarded`). Di app ini, alur baru butuh titik masuk lebih awal (form pelamar dari Instagram) — perlu diputuskan apakah pakai satu tabel kandidat dengan status bertingkat (`new` → `screening` → `interview` → `offering` → `contract` → `hired`) mengikuti pola `candidates.overall_status` + `job_applications.current_stage`/`status`, atau tabel terpisah `job_applicants` yang baru "naik kelas" jadi `candidates` setelah lolos filter HC (sesuai istilah yang dipakai user di diskusi).
- `job_applications.current_stage` default `'onboarding'` dan `status` default `'in_progress'` di HRIS — pola state machine ini bisa dicontoh untuk tracking status di app baru.
- Fitur baru (offering letter, digital agreement) belum ada representasi tabel di HRIS — akan jadi tabel baru murni di app ini, kemungkinan terhubung ke `job_applications`/kandidat lewat `application_id`.

## 6. Di luar cakupan (tetap di HRIS)
- Proses onboarding penuh (pembuatan data karyawan, payroll, dll).
- Struktur organisasi, absensi, cuti — semua modul portal karyawan lain (lihat `DESIGN.md`).
- `/candidate` di HRIS tetap dipakai untuk kandidat yang **sudah diterima** melengkapi data diri final sebelum jadi karyawan — app ini menghasilkan kandidat yang sudah lolos kontrak, lalu diserahkan/di-handoff ke titik itu (mekanisme handoff/integrasi masih perlu didefinisikan: import manual, API, atau shared DB).

## 7. Keputusan yang sudah diambil
- **Auth**: Laravel Breeze, stack Blade (cocok dengan Tailwind yang sudah terpasang). `users` murni untuk login, bukan identitas kandidat.
- **Struktur tabel**: 3 lapis `users` → `job_applications` → `candidates`, lihat §4. Tidak ada field yang diminta dua kali.
- **DB**: MySQL lokal terpisah (`rekrutmen_db`), bukan akses langsung ke `hris_db`.

## 8. Pertanyaan terbuka
- Integrasi ke HRIS setelah kontrak ditandatangani: API antar sistem, export/import, atau akses DB bersama?
- Digital agreement: cukup consent checkbox + timestamp/IP log, atau perlu e-signature/meterai elektronik yang sah secara hukum?
- Offering letter: dikirim via email otomatis dari sistem, atau digenerate PDF untuk dikirim manual dulu?
- Siapa saja role pengguna di app ini (HC recruiter, hiring manager approval, admin)?
- Sinkronisasi `job_openings` dari API HRIS: pull terjadwal (cron) atau on-demand saat pelamar buka form?

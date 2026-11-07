# Planning: Biodata Pelamar di Tahap Wawancara

Tanggal: 2026-11-05 · Status: **sudah dieksekusi di working tree (belum di-commit); lihat bagian 9 untuk selisih dari rencana** · Sumber acuan: `knowledge/Form Biodata Pelamar.docx`

## 1. Tujuan

Pengisian data diri kandidat dimajukan dari tahap `onboarding` ke **saat pelamar sudah dijadwalkan wawancara pertama**, dalam
bentuk **Biodata Pelamar** yang bisa diunduh sebagai **PDF** (dirender langsung dari isian) sesuai form
`Form Biodata Pelamar.docx`. **Keputusan akhir: tidak ada unduhan Word**; bagian di bawah yang menyebut Word sudah tidak berlaku. Data yang tadinya diminta lagi di form offering
letter (tempat/tanggal lahir dan alamat) dipindah ke biodata supaya pelamar tidak mengisi dua kali.

### Yang dipindah ke wawancara pertama

Identitas · Alamat · Data Keluarga · Riwayat Pendidikan · Riwayat Pelatihan · Pengalaman Organisasi · Pengalaman Kerja ·
Referensi · Informasi Tambahan · Penilaian Diri.

### Yang tetap di tahap onboarding

BPJS & Rekening. Tab **Dokumen** (KTP/KK) diasumsikan ikut tetap di onboarding (lihat Pertanyaan Terbuka #1).

### Yang tetap di offering letter

Hanya **Nama Lengkap dengan Gelar**. Nama di biodata adalah nama sesuai KTP, yaitu nama pada user/akun. Form offering sendiri tetap ada: kalau biodata belum berisi tempat/tanggal lahir dan alamat (pelamar yang sudah di tahap offering), field lamanya tetap diminta (lihat 4.3).

### Batasan: proses rekrutmen sedang berjalan

Sudah ada pelamar yang melewati wawancara (`offering`, `mcu`, `onboarding`, `passed`). Form data diri yang sekarang **tidak
boleh hilang atau berubah perilakunya** untuk mereka. Rencana ini hanya menambah, tidak menghapus.

## 2. Kondisi sekarang (fakta di kode)

| Hal | Sekarang | Lokasi |
|---|---|---|
| Baris `candidates` dibuat | Saat status berubah ke `onboarding` / `passed` | `JobApplication::booted()` (`app/Models/JobApplication.php`) |
| Form data diri | 12 tab, satu form, hanya bisa dibuka kalau `Auth::user()->candidate` ada, dan setelah semua persetujuan dicentang | `CandidateProfileController`, `resources/views/candidate-profile/edit.blade.php` |
| Daftar kelengkapan wajib | `Candidate::requiredDataChecklist()` (dipakai juga untuk flag `data_diri_belum_lengkap` di API notifikasi HRIS) | `app/Models/Candidate.php`, `HrisIntegrationController` |
| Form offering | Nama dengan gelar, tempat lahir, tanggal lahir, alamat + `x-wilayah-picker` (API wilayah.id), disimpan ke `job_applications.offering_*` | `OfferingLetterController::updateData`, `dashboard.blade.php` (~baris 306) |
| Candidate dibuat dengan alamat | `offering_*` dituang ke `candidates.ktp_address/village/district/city/province` saat Candidate dibuat | `JobApplication::booted()` |
| Alamat di form data diri | Input teks biasa (belum pakai wilayah picker) | tab 2 `edit.blade.php` |
| Menu "Data Diri" di navbar | Tampil kalau `auth()->user()->candidate` ada | `resources/views/layouts/app.blade.php` |
| PDF | `barryvdh/laravel-dompdf` sudah terpasang | `composer.json` |
| Word | Tidak dipakai (diputuskan cukup PDF) | — |
| HRIS | Membaca tabel `candidates` + 8 tabel anak langsung dari database. Perubahan skema harus **aditif** | `knowledge/INTEGRASI-HRIS.md` §2.3, §2.4 |

## 3. Gap: form Biodata (docx) vs sistem

| Bagian docx | Sudah ada | Perlu ditambah |
|---|---|---|
| Nama (sesuai KTP), panggilan, gol. darah, TB & BB, agama, kebangsaan, No KTP | `name`, `nickname`, `blood_type`, `body_height_weight`, `religion`, `nationality`, `nik` | — (tampil atau tidaknya mengikuti master perusahaan, 4.6) |
| L/P | — | `candidates.gender` |
| Tempat, tanggal lahir | Hanya di `job_applications.offering_birth_place/date` | `candidates.birth_place`, `candidates.birth_date` |
| Status pernikahan (Belum Menikah / Menikah / Janda / Duda) + tanggal pernikahan | `status_marital` (hanya Belum/Sudah Menikah), `marriage_date` | Opsi **Janda** dan **Duda**. Nilai `Sudah Menikah` dipertahankan (tampil "Menikah") |
| Email, No HP | `users.email`, `users.phone` | Ditampilkan dari user, tidak diinput ulang |
| Nama & nomor darurat | — | `candidates.emergency_contact_name`, `emergency_contact_phone`. **Berbeda per perusahaan** (lihat 4.6) |
| Pendidikan terakhir | Turunan dari `candidate_educations` | Dihitung saat cetak (jenjang tertinggi), tanpa kolom baru |
| Alamat / tempat tinggal | `ktp_address`, `village`, `district`, `city`, `province` | Ganti input teks jadi `x-wilayah-picker` |
| Akun media sosial (tidak ada di docx; ada di form holding) | `social_media_accounts` | Hanya tampil untuk perusahaan yang mengaktifkannya (4.6), mis. holding |
| STR (aktif/tidak, masa berlaku) | `no_str`, `str_seumur_hidup`, `str_berlaku_*` | "Aktif/tidak" dihitung dari tanggal, hanya untuk jabatan nakes |
| Pas foto | Kolom `profile_picture` ada, belum dipakai | **Tidak diunggah.** Hasil cetak menyediakan kotak kosong "Pas Foto Terbaru"; foto ditempel manual setelah dicetak, seperti tanda tangan |
| Hubungan keluarga (Ayah, Ibu, Saudara 1–5, Pasangan, Anak 1–3: L/P, nama, tgl lahir, pendidikan, pekerjaan) | `candidate_family_members` (relation, gender, name, birth_date, education, job) | Pilihan relasi baku (Ayah/Ibu/Saudara/Pasangan/Anak). Slot bernomor dihasilkan saat cetak |
| Pendidikan formal (tingkat, sekolah, jurusan, IPK, tahun dari–sampai) | `candidate_educations`: cocok persis | — |
| Pelatihan (tipe, institusi, materi, waktu, tempat) | `candidate_trainings` (`duration` = waktu): cocok | — |
| Organisasi (organisasi, bidang, jabatan, waktu, tempat) | `candidate_organizations`: cocok | — |
| Riwayat pekerjaan (perusahaan, jenis usaha, alamat, posisi, lama kerja, **gaji awal & akhir**, alasan berhenti) | `candidate_work_experiences` punya satu kolom `salary` | `salary_end` (kolom lama `salary` = gaji awal) |
| Tugas & tanggung jawab, gambaran posisi di struktur organisasi (pekerjaan sekarang/terakhir) | — | `job_description`, `org_position_description` (diisi pada baris `is_current`) |
| Referensi + "keberatan dihubungi?" | `candidate_references`, `additional_info.allow_reference_check` | — |
| Riwayat penyakit, waktu luang, bahasa, benefit terakhir, gaji harapan, tanggal mulai kerja | `candidate_additional_infos`: semua ada | — |
| Penilaian diri (kelebihan, kekurangan, kepuasan 5 level, alasan) | `candidate_self_assessments`: cocok | — |
| Pernyataan benar + tanggal + tanda tangan | — | Checkbox pernyataan, disimpan `candidates.biodata_confirmed_at`. Tanda tangan basah di hasil cetak |

## 4. Rancangan

### 4.1 Pemicu, akses, dan pembuatan `Candidate`

**Pemicu:** biodata terbuka begitu lamaran **sudah punya jadwal wawancara pertama yang terbit**, yaitu ada baris
`interview_invitees` yang jadwalnya (`interview_schedules.status`) = `published`. Draf jadwal yang belum diterapkan tidak
memicu. Wawancara boleh berkali-kali (HRIS mengirim `tahap`); yang dihitung hanya yang **pertama**, setelah itu biodata tetap
terbuka.

- Tambah di `JobApplication`:
  - relasi `interviewInvitees()` (hasMany; `interviewInvitee()` yang ada hasOne dan hanya mengambil satu baris)
  - `hasInterviewScheduled()`: ada invitee dengan jadwal `published`
  - `biodataOpen()` = `hasInterviewScheduled()` **atau** status `offering`, `mcu`, `onboarding`, `passed` (jaring pengaman untuk
    pelamar lama yang melewati wawancara tanpa baris jadwal)
  - `onboardingStage()` = status `onboarding` atau `passed`
- **Pembuatan `Candidate`:** satu method `Candidate::ensureFor(JobApplication)` (firstOrCreate per user + prefill). Dipanggil dari
  `booted()` (perilaku sekarang untuk onboarding/passed, tidak diubah) dan dari **lazy-create** di
  `CandidateProfileController::currentCandidate()` serta di kartu dashboard saat `biodataOpen()`. `booted()` **tidak** dipakai
  untuk memicu biodata: pada `InterviewSchedule::apply()` status lamaran diubah ke `interview` *sebelum* jadwalnya diberi
  status `published`, jadi event itu terlalu dini. Lazy-create juga menutup kasus HRIS menulis database langsung tanpa event
  model. Kalau `biodataOpen()` salah, tetap 403 seperti sekarang.
- Tambah helper `User::hasBiodataAccess()` dan pakai di `layouts/app.blade.php` menggantikan `auth()->user()->candidate`.
  Menu Persetujuan tetap hanya muncul di tahap onboarding+ dan kalau `requires_agreement`.
- Prefill di `ensureFor()` dari `answersMap()` (jawaban form lamaran), kalau key-nya ada: `full_name`, `nik`, `gender`,
  `birth_place`, `birth_date`, `domicile_address`. Tujuan: pelamar tidak mengetik ulang yang sudah pernah diisi.
- Banner di dashboard: "Kamu dijadwalkan wawancara, lengkapi biodata" begitu `biodataOpen()`. Teks email undangan wawancara
  ada di template notifikasi (sisi HRIS); menambah kalimat ajakan mengisi biodata di sana opsional.

### 4.2 Satu form, tab dibuka per tahap

Tidak membuat form kedua. `/data-diri-kandidat` tetap, tab yang tampil bergantung tahap lamaran terbaru (predikat di 4.1):

| Tahap | Tab tampil | Gerbang persetujuan |
|---|---|---|
| `biodataOpen()` dan belum `onboarding`/`passed` | 1, 2, 4–11 (tanpa 3 BPJS & Rekening, tanpa 12 Dokumen) | Tidak ada |
| `onboardingStage()` | Semua 12 tab (perilaku sekarang) | Ada, seperti sekarang |

- `update()` hanya menyimpan kolom BPJS/rekening dan dokumen kalau ikut terkirim (di tahap biodata tabnya tidak ada).
- `Candidate::requiredDataChecklist()` **tetap mengembalikan gabungan seperti sekarang** (HRIS bergantung pada flag
  `data_diri_belum_lengkap`). Tambahkan kunci `stage` (`biodata` / `onboarding`) pada tiap item, plus `biodataChecklist()` dan
  `biodataComplete()`. Item tahap onboarding: nomor rekening, dokumen KTP, dokumen KK. Sisanya tahap biodata.
- Form menampilkan checklist sesuai tahap saat ini saja.

### 4.3 Form offering: tetap ada, isinya menyesuaikan biodata

Form offering di dashboard **tidak dihapus**. Yang berubah hanya field apa yang ditampilkan, tergantung apakah tempat/tanggal
lahir dan alamat sudah terisi di biodata (`Candidate`):

| Kondisi saat pelamar membuka form offering | Yang tampil dan bisa diisi |
|---|---|
| Biodata **sudah** punya tempat lahir, tanggal lahir, dan alamat lengkap (provinsi sampai kelurahan) | Hanya **Nama Lengkap dengan Gelar**. Tempat/tanggal lahir dan alamat tidak bisa diisi lagi di sini; tampil sebagai ringkasan baca-saja dengan tautan "Ubah di Biodata" |
| Biodata **belum** lengkap (mis. pelamar yang sudah di tahap offering sebelum fitur ini jalan) | Form seperti sekarang: nama dengan gelar, tempat lahir, tanggal lahir, alamat + `x-wilayah-picker`. Wajib diisi seperti biasa |

- Penentuannya dihitung di server (mis. `Candidate::biodataIdentityFilled()`), bukan hanya di tampilan.
- `OfferingLetterController::updateData`: `offering_full_name` selalu wajib. Field tempat/tanggal lahir/alamat divalidasi
  wajib **hanya** kalau biodata belum lengkap. Kalau biodata sudah lengkap, nilai kiriman untuk field itu **diabaikan**
  (cegah data biodata tertimpa lewat request manual) dan diambil dari `Candidate`.
- Hasil akhirnya sama di kedua kasus: nilai tempat/tanggal lahir/alamat **disalin (snapshot)** ke `job_applications.offering_*`,
  sehingga `renderOfferingLetterPdf()` dan template surat **tidak perlu diubah**.
- Pada kasus form lama terisi, nilainya ditulis juga ke `Candidate` (hanya kolom yang masih kosong), supaya pelamar tidak diminta
  mengisi lagi di biodata dan data konsisten.
- Pelamar yang sudah mengonfirmasi offering (`offering_data_confirmed_at` terisi) tidak berubah perilakunya; "Perbarui Data Diri"
  mengikuti aturan tabel di atas.
- Kolom `job_applications.offering_*` tidak dihapus.

### 4.4 Skema database (semua aditif)

Satu migrasi per tabel. Semua kolom tambahan di tabel yang sudah ada bersifat `nullable`:

- `candidates`: `gender` (string 10), `birth_place`, `birth_date` (date), `emergency_contact_name`,
  `emergency_contact_phone` (string 30), `biodata_confirmed_at` (timestamp)
- `candidate_work_experiences`: `salary_end` (decimal 15,2), `job_description` (text), `org_position_description` (text)
- tabel baru `company_biodata_fields`: `company_id`, `field_key`, `is_enabled`, `is_required` (rincian di 4.6)

Tanpa kolom `NOT NULL` baru dan tanpa perubahan tipe kolom lama. `status_marital` tetap string; yang bertambah hanya nilai
yang diterima validasi (`Janda`, `Duda`).

### 4.5 Cetak biodata (PDF saja)

- Route baru (middleware `auth`, hanya pemilik): `GET /data-diri-kandidat/biodata.pdf`,
  name `data-diri.biodata`. Tombol "Pratinjau PDF" (tab baru, PDF inline) di halaman form dan di kartu dashboard; unduh lewat penampil PDF browser.
- **Satu sumber data:** `Candidate::biodataData()` mengembalikan array terstruktur untuk dua format: baris data pribadi,
  keluarga dinormalisasi ke slot tetap (Ayah, Ibu, Saudara 1–5, Pasangan, Anak 1–3), pendidikan, pelatihan, organisasi,
  3 pekerjaan terakhir, referensi, info tambahan, penilaian diri.
- **PDF** (langsung dari isian, tidak lewat Word): view `candidate-profile/biodata-pdf.blade.php` memakai dompdf yang sudah ada. Tata letak meniru docx (A4, judul
  "FORM BIODATA PELAMAR", kotak pas foto **kosong** untuk ditempel manual, tabel berbingkai, pernyataan, tempat/tanggal, kolom tanda
  tangan).
- Teks "RSU Syifa Medika" di template docx bersifat spesifik perusahaan. Di cetak diganti nama perusahaan lowongan
  (`jobOpening->company->name`).

### 4.6 Master isian biodata per perusahaan (DIBATALKAN, lihat bagian 9)

Tidak semua perusahaan meminta isian yang sama (mis. kontak darurat, atau akun media sosial yang hanya diminta holding).
**Penentuan master dilakukan di HRIS dan akan menyusul.** Repo ini hanya menyediakan tabel, pembaca (resolver), dan tampilan;
HRIS yang menulis barisnya. Sampai HRIS siap, semua perusahaan memakai default katalog dan semuanya tetap berjalan.

Isian tetap disimpan di kolom `candidates` yang sudah dibaca HRIS (bukan EAV seperti form lamaran), jadi cetak biodata dan
HRIS tidak terpengaruh.

**Katalog** (`config/biodata.php`): urutan bawaan isian per bagian (Identitas, Alamat, dst.), `key => [label, bagian,
default_aktif, default_wajib]`.

- *Selalu tampil dan wajib (tidak bisa dimatikan):* nama, NIK, gender, tempat/tanggal lahir, status pernikahan, alamat.
- *Bisa diatur master:* `nickname`, `blood_type`, `body_height_weight`, `religion`, `nationality`, `no_kk`, `mother_name`,
  `emergency_contact` (nama + nomor, satu pasang), `social_media_accounts`.
- Default katalog mengikuti form docx: semua aktif, **kecuali** `social_media_accounts` yang default mati.
- **Tidak ada tab baru.** Isian yang berbeda per perusahaan memakai tab yang sudah ada di antara yang dipindahkan ke wawancara
  pertama. Tab bawaan katalog: `emergency_contact` di Identitas (tab 1), `social_media_accounts` di Alamat (tab 2, tempatnya
  sekarang).
- Kunci tab yang sah untuk `section_key` (hanya tab berisi isian tunggal, bukan tab daftar berulang): `identitas` (tab 1),
  `alamat` (2), `informasi_tambahan` (10), `penilaian_diri` (11). Tab daftar berulang (keluarga, pendidikan, pelatihan,
  organisasi, pengalaman kerja, referensi) dan tab tahap onboarding (BPJS, Dokumen) tidak bisa jadi tujuan.

**Tabel baru** `company_biodata_fields` (aditif, ditulis HRIS):

| Kolom | Tipe | Arti |
|---|---|---|
| `company_id` | FK | Perusahaan |
| `field_key` | string | Kunci dari katalog |
| `is_enabled` | bool, default true | Tampil atau tidak |
| `is_required` | bool, default sesuai katalog | Wajib diisi atau tidak |
| `section_key` | string, nullable | **Tab/kategori tujuan** (kunci sah di atas). Kosong = tab bawaan katalog |
| `after_field_key` | string, nullable | **Muncul setelah isian apa**, di dalam tab tujuan. Kosong = di akhir tab |
| `sort_order` | int, default 0 | Urutan kalau beberapa isian menempel pada isian yang sama atau sama-sama di akhir tab |

Unique `(company_id, field_key)`. Perusahaan **tanpa baris** memakai default katalog, jadi perusahaan yang sudah ada tidak
rusak dan tidak perlu seed massal.

**Penempatan** (dua langkah, HRIS menentukan keduanya):

1. **Tab:** isian masuk ke `section_key` kalau diisi, kalau kosong ke tab bawaan katalog. Isian dari tab yang tidak sah
   (daftar berulang atau tahap onboarding) jatuh ke tab bawaan katalog.
2. **Posisi di tab itu:** kalau `after_field_key` diisi dan isian itu **ada di tab tujuan yang sama**, isian disisipkan tepat di
   bawahnya. Kalau kosong, tidak dikenal, berada di tab lain, atau melingkar (menempel pada isian master yang belum
   ditempatkan), isian jatuh ke **akhir tab**, urut `sort_order`. Tidak boleh melempar error.

Dengan begitu pertanyaan "boleh menempel pada isian di tab lain" selesai: yang berbeda tab dipindahkan lewat `section_key`,
bukan lewat `after_field_key`.

**Tampilan: satu isian master = satu blok, satu baris penuh.** Isian yang **ditempatkan master** (punya `section_key` atau
`after_field_key`) tidak ikut grid dua kolom; ia tampil sendiri selebar penuh (label di atas,
kolom isian di bawahnya) supaya tidak membingungkan di tengah isian lain. Di PDF juga satu baris sendiri
(label : isian), di bagian yang sama dengan tab tujuannya. Baris master yang
hanya mengatur `is_enabled`/`is_required` tanpa penempatan tetap tampil di grid biasa pada posisi bawaannya.

**Satu resolver:** `App\Support\BiodataFields::for(Company $company)` mengembalikan daftar isian akhir per bagian, lengkap
dengan tab, urutan, `enabled`, `required`, dan penanda `block` (ditempatkan master). Dipakai di semua tempat berikut, supaya aturannya
tidak tersebar:

- `CandidateProfileController::edit/update`: isian mati tidak ditampilkan, tidak divalidasi, tidak ditimpa saat simpan;
  isian wajib mendapat aturan `required`, selebihnya `nullable`. Data lama pada isian yang kemudian dimatikan **tidak dihapus**.
- `Candidate::biodataChecklist()` dan `requiredDataChecklist()`: hanya memuat isian yang aktif dan wajib untuk perusahaan itu.
  Untuk perusahaan tanpa baris master, hasilnya sama seperti sekarang.
- View form (tab 1, tab 2) dan cetak PDF (4.5): urutan dan blok mengikuti resolver; isian yang mati dihilangkan.

**Perusahaan yang berlaku:** perusahaan dari lowongan pada lamaran terbaru kandidat
(`Candidate->jobApplication->jobOpening->company`). Kalau kandidat melamar lowongan lain di perusahaan berbeda, form mengikuti
perusahaan terbaru dan data yang sudah ada tetap tersimpan.

**Kontrak untuk HRIS** (dicatat di `knowledge/INTEGRASI-HRIS.md`): nama tabel dan kolom di atas, daftar `field_key` yang sah,
arti `after_field_key`, dan bahwa kunci/tipe tidak dijamin selain yang tertulis. Repo ini tidak punya layar pengelolaan master
(admin Filament sudah dihapus di commit `3cc1dd0`). Seeder kecil untuk holding (`social_media_accounts` aktif) disiapkan hanya
sebagai jembatan sampai HRIS menulis datanya sendiri.

## 5. Langkah eksekusi (berurutan)

1. **Migrasi** (4.4) dan update `$fillable` / `$casts` di `Candidate` dan `CandidateWorkExperience`. Jalankan hanya migrasi
   baru; jangan ikut menjalankan migrasi pending lain milik HRIS (mis. `split_offering_compensation_items_table`) kecuali
   diminta.
2. **Master isian (4.6):** migrasi `company_biodata_fields` (termasuk `section_key`, `after_field_key`, `sort_order`), model `CompanyBiodataField`,
   `config/biodata.php`, resolver `BiodataFields::for()` (urutan + penanda blok), seeder jembatan untuk holding. Tanpa layar
   pengelolaan: itu tugas HRIS. Kerjakan sebelum controller/view supaya semuanya memakai resolver yang sama.
3. **Model:** `interviewInvitees()`, `hasInterviewScheduled()`, `biodataOpen()`, `onboardingStage()` di `JobApplication`; `Candidate::ensureFor()`
   (dipanggil dari `booted()` dan lazy-create) dengan prefill dari `answersMap()`;
   `Candidate::biodataChecklist()`, `biodataComplete()`, `biodataData()`; `User::hasBiodataAccess()`.
4. **Controller:** `currentCandidate()` lazy-create; `edit()` menentukan tahap dan tab; gerbang persetujuan hanya tahap
   onboarding; `update()` menambah validasi field baru yang mengikuti master 4.6 (`gender`, `birth_*`, `emergency_*`, `salary_end`, deskripsi pekerjaan,
   checkbox pernyataan, `status_marital` + Janda/Duda) dan menyimpan `biodata_confirmed_at`.
5. **View `edit.blade.php`:** daftar tab dinamis per tahap; isian per perusahaan mengikuti resolver 4.6 (tab dari `section_key`, blok satu baris penuh, urutan `after_field_key`); field baru di tab 1 (gender, tempat/tanggal lahir, kontak darurat,
   opsi status pernikahan); tab 2 pakai `x-wilayah-picker` (prefix kosong, sama komponennya dengan form offering); tab 4
   pilihan relasi baku; tab 8 gaji awal/akhir plus dua kolom teks untuk baris pekerjaan sekarang; checkbox pernyataan di tab
   terakhir; tombol unduh.
6. **Dashboard + navbar:** kartu "Lengkapi Biodata" begitu `biodataOpen()` (progres checklist, tombol, unduh); navbar
   memakai `hasBiodataAccess()`.
7. **Offering letter:** form tetap ada; tampilan dan validasi `updateData` bergantung pada kelengkapan biodata sesuai 4.3 (satu method pengecek di `Candidate`, dipakai view dan controller).
8. **Cetak:** view PDF, route + method controller, tombol unduh.
9. **Backfill** (perintah artisan sekali jalan, aman diulang): buat `Candidate` untuk lamaran yang `biodataOpen()` (jadwal
   wawancara terbit, `offering`, `mcu`) dan belum punya; salin `offering_birth_*` dan `offering_domicile_*` ke `Candidate` yang kolomnya masih kosong.
   Jalankan manual setelah deploy, bukan di dalam migrasi.
10. **Dokumentasi:** perbarui `knowledge/INTEGRASI-HRIS.md` (§2.3 waktu pembuatan `Candidate` dan pemicu biodata, kontrak tabel master 4.6, §2.4 kolom baru, §5 tabel
   route) dan beri tahu pihak HRIS bahwa baris `candidates` sudah ada sejak tahap interview.

## 6. Pengujian dan kriteria selesai

- Pelamar belum punya jadwal wawancara terbit (draf jadwal, atau `shortlisted`): biodata tertutup (403). Begitu jadwal pertama
  diterapkan: menu Data Diri muncul, `Candidate` terbentuk sendiri, hanya 10 tab biodata tampil, tidak diminta
  persetujuan, bisa simpan tanpa BPJS/rekening/dokumen.
- Pelamar `onboarding`/`passed` (termasuk akun Ijat, Badrol, Budi, Dewi di data lokal): 12 tab dan gerbang persetujuan sama
  seperti sebelumnya; data yang sudah tersimpan tidak hilang.
- Offering dengan biodata lengkap: hanya nama dengan gelar yang bisa diisi; request manual yang mengirim tempat/tanggal lahir/alamat tidak menimpa biodata; PDF surat tetap memuat TTL dan alamat yang benar.
- Offering dengan biodata kosong (pelamar lama): form lengkap seperti sekarang tetap wajib diisi, dan datanya ikut masuk ke `Candidate`.
- `requiredDataChecklist()` / `requiredDataComplete()` mengembalikan hasil yang sama seperti sebelumnya untuk kandidat lama.
- Unduh PDF: file terbuka, semua bagian docx ada, kotak pas foto kosong tercetak, nama = nama KTP, kolom kosong tidak merusak tata letak
  (kandidat tanpa pelatihan/organisasi/pekerjaan).
- Cek visual PDF dengan merender ke gambar dan membandingkan dengan `Form Biodata Pelamar.docx`.
- Master perusahaan: isian dengan `section_key` pindah ke tab itu; dengan `after_field_key` muncul tepat di bawah isian itu sebagai
  satu baris penuh (form, PDF); `section_key` tidak sah, atau `after_field_key` yang tidak ada di tab tujuan atau melingkar,
  jatuh ke akhir tab tanpa error; perusahaan tanpa override tampil seperti default katalog; holding menampilkan kolom media sosial dan
  perusahaan lain tidak; mematikan `emergency_contact` menyembunyikannya di form dan cetak, tidak menghapus data lama, dan tidak
  lagi dihitung di checklist; isian wajib yang kosong ditolak saat simpan.
- Uji E2E dengan akun uji (cara yang sama seperti uji DISC), lalu hapus data uji.

## 7. Risiko

- **HRIS menulis DB langsung:** event model bisa tidak jalan, sehingga lazy-create wajib ada (4.1). `apply()` juga mengubah
  status sebelum jadwal terbit, jadi pemicu biodata tidak boleh bergantung pada event `booted()`.
- **Skema dipakai HRIS:** hanya kolom nullable baru. Tidak ada rename/drop.
- **Master menunggu HRIS:** sampai HRIS menulis `company_biodata_fields`, semua perusahaan memakai default katalog. Kontrak tabelnya
  harus disepakati dulu supaya HRIS tidak menulis kunci yang tidak dikenal.
- **Satu `Candidate` per user:** biodata dipakai ulang kalau user melamar lowongan lain. Ini keuntungan, tapi `ktp_address`
  tidak ditimpa otomatis oleh data offering lamaran berikutnya.

## 8. Pertanyaan terbuka (asumsi default tertulis)

1. **Tab Dokumen (KTP/KK)**: ikut BPJS & Rekening di onboarding. *Asumsi: ya.*
2. **Wajib sebelum wawancara?** Pemicu sudah ditetapkan (jadwal wawancara pertama terbit), tapi apakah biodata wajib lengkap
   sebelum hari wawancara? Status diubah HRIS, jadi aplikasi ini tidak bisa memblokir. *Asumsi: tidak memblokir;
   hanya banner, checklist, dan flag `data_diri_belum_lengkap` untuk HRIS.*
3. **Teks pernyataan dan kota tanda tangan**: docx menyebut "RSU Syifa Medika" dan "Banjarbaru". *Asumsi: nama
   perusahaan lowongan; kota "Banjarbaru" tertulis tetap sampai ada kolom kota di perusahaan.*
4. ~~Cara membuat Word~~ **Diputuskan:** tidak ada unduhan Word, cukup PDF.
5. **Nama dengan gelar** tetap hanya di offering letter. *Asumsi: ya.*
6. ~~Pas foto~~ **Diputuskan:** tidak diunggah; ditempel manual pada hasil cetak.
7. **Unduhan oleh staf**: perlu route unduh biodata untuk staf seperti `admin.offering-letter.download`? *Asumsi: tidak;
   HRIS membaca database.*
8. **Isian per perusahaan:** isian mana yang diminta tiap perusahaan akan ditentukan HRIS (menyusul). *Asumsi: sampai itu ada, default katalog (semua aktif kecuali media sosial).*
9. **Kontrak tabel master untuk HRIS:** apakah nama tabel/kolom di 4.6 cocok bagi HRIS, atau HRIS punya konvensi sendiri? *Asumsi: pakai usulan 4.6.*
10. **Isian bebas per perusahaan** (pertanyaan baru di luar katalog, disimpan EAV seperti form lamaran)? *Asumsi: tidak untuk versi pertama.*
11. ~~Pindah bagian~~ **Diputuskan:** tidak ada tab khusus baru. Isian master memakai tab yang sudah ada, dan HRIS menentukan tab lewat
    `section_key` serta posisinya lewat `after_field_key` (4.6). Pilihan tab dibatasi ke tab berisi isian tunggal.

## 9. Catatan eksekusi (selisih dari rencana)

Dikerjakan 2026-11-05. Yang berbeda dari tulisan di atas:

- **Gaji:** `candidate_work_experiences.salary` tetap = gaji **terakhir** (itu arti kolom di form lama), dan kolom baru bernama
  `salary_start` = gaji awal. Rencana awal menyebut `salary_end`; dibalik supaya data lama tidak berubah arti.
- **`company_biodata_fields.is_required`** nullable (NULL = default katalog), bukan default di kolom.
- **Validasi server tetap `nullable`.** "Wajib" hanya berlaku sebagai checklist kelengkapan, tidak memblokir simpan sebagian
  antar-tab (sama dengan perilaku form lama). Isian yang dimatikan master tidak ikut disimpan.
- **Tab 10 (Informasi Tambahan) dan 11 (Penilaian Diri)** isinya tidak ada di katalog, jadi `after_field_key` tidak berlaku di sana:
  isian master yang ditempatkan ke tab itu muncul di **akhir tab**.
- **Seeder holding** (`BiodataFieldsSeeder`) mencari perusahaan bernama "Syifa Global Group"; database dev lokal tidak punya perusahaan
  itu (dilewati dengan peringatan). Jalankan di lingkungan yang namanya cocok, atau tulis barisnya dari HRIS.
- **Bug lama yang ikut diperbaiki:** di `edit.blade.php` atribut `x-data="repeater(@json(...))"` memakai tanda kutip ganda, sehingga
  atribut terpotong dan baris keluarga/pendidikan/pelatihan/organisasi/kerja/referensi yang sudah tersimpan **tidak termuat** di form;
  menyimpan ulang lalu menghapus semuanya (replace-all). Sekarang memakai kutip tunggal.
- **Word dibatalkan** setelah eksekusi awal (PhpWord dan `BiodataDocx` sempat dibuat lalu dibuang). **Dependensi:** tidak ada yang baru; `composer.lock` repo ini sudah tidak memuat Filament sejak commit `3cc1dd0`;
  di mesin yang vendornya masih lama perlu `composer install` (cache view terkompilasi dari era Livewire juga perlu dibuang).

### Verifikasi yang dijalankan

- `tests/Feature/BiodataTest.php`: 16 tes (pemicu dan akses, tab per tahap, kolom BPJS tidak tersimpan di tahap biodata, master
  mematikan/menempatkan isian, checklist lama tidak berubah, offering dengan/ tanpa biodata, unduh PDF). Butuh MySQL dengan
  skema terisi: migrasi proyek tidak bisa dijalankan dari nol (ALTER ... MODIFY, dan nama indeks terlalu panjang pada migrasi offering
  yang masih pending), jadi skema DB dev disalin ke `rekrutmen_test` dan tes memakai `DatabaseTransactions`.
- E2E dengan Playwright (akun uji, sudah dihapus): kartu dashboard, menu "Biodata", 10 tab tanpa BPJS/Dokumen, blok master satu baris
  penuh, simpan dengan banyak tab, baris tersimpan termuat ulang, offering hanya meminta nama saat biodata lengkap dan form lengkap saat
  kosong, unduh PDF (HTTP 200, tipe benar).
- **Belum dilihat secara visual:** isi PDF (tidak ada perender PDF di mesin ini). Cek tampilannya dengan membuka hasil unduhan.

### Font dan tata letak PDF

- Judul bagian **Calibri 10 pt**, isi **Verdana 10 pt**. dompdf tidak memuat font sistem: file `calibri.ttf`, `calibrib.ttf`, `calibrii.ttf`, `verdana.ttf`, `verdanab.ttf`, `verdanai.ttf` disalin manual ke `storage/fonts/` (di server: dari `C:\Windows\Fonts` atau salinan berlisensi). File font dan cache dompdf di sana masuk `.gitignore` (lisensi Microsoft, tidak di-commit). Kalau file tidak ada, view jatuh ke DejaVu Sans tanpa error.
- Render PDF diperiksa dengan membaca PDF asli lewat pdf.js (bukan HTML), 4 halaman, dibandingkan dengan `knowledge/form-biodata-1..4.png`.

### Revisi setelah eksekusi: nama dan NIK dari akun

- Nama (sesuai KTP) dan **NIK** diambil dari akun (`users.name`, `users.nik`, diisi saat daftar) dan **dikunci** di form. Kiriman manual diabaikan di server; `candidates.name`/`nik` disalin dari akun setiap `ensureFor()` supaya HRIS tetap membaca nilai yang sama. PDF juga memakai nilai akun.
- Permintaan awal menyebut "no kk"; akun tidak punya No. KK (hanya NIK), jadi yang dikunci adalah NIK dan **No. KK tetap bisa diisi**. Kalau maksudnya memang No. KK, perlu sumber datanya.

### Revisi: master per perusahaan dibatalkan

- Tabel `company_biodata_fields`, model, seeder, dan resolver override dihapus (migrasinya di-rollback dan filenya dibuang; belum pernah di-commit). Bagian 4.6 di atas tidak berlaku lagi. Pertanyaan terbuka #8, #9, #10 gugur.
- Gantinya aturan tetap berdasarkan `companies.is_hospital` (lihat INTEGRASI-HRIS.md §2.4a): STR, kotak uraian pekerjaan dan kalimat "di <RS>" hanya untuk rumah sakit; Akun Media Sosial hanya untuk perusahaan lain, di Data Pribadi di bawah Email dan No HP.

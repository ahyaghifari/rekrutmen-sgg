# Planning: Alur Perubahan Status Lamaran

## Status yang Sudah Jalan

| Dari | Ke | Mekanisme | Draft System | Notifikasi |
|------|----|-----------|-------------|------------|
| `applied` | `screening` | Bulk action banner (modal konfirmasi) | ❌ langsung | ❌ |
| `screening` | `shortlisted` / `rejected` | Seleksi Berkas → ScreeningDecision | ✅ | ✅ draft email |
| `shortlisted` | `interview` | Jadwal Wawancara → InterviewSchedule | ✅ | ✅ draft undangan |

---

## Yang Perlu Dilanjutkan

### 1. Hasil Wawancara: `interview` → `passed` / `rejected`

**Flow:**
- Rekruter membuka daftar pelamar berstatus `interview`
- Banner muncul: "Catat Hasil Wawancara"
- Modal: checkbox list pelamar → centang yang **lulus** → sisanya rejected
- Draft system: simpan dulu sebelum diterapkan
- Terapkan → status berubah, notifikasi draft dibuat

**Tabel baru:**
- `interview_results` — mirip `screening_decisions`
  - `job_application_id` (unique FK)
  - `result` enum(`passed`, `rejected`)
  - `notes` nullable (catatan per pelamar, opsional)

**Notifikasi:**
- Event baru: `interview_passed`, `interview_rejected`
- Tambah ke enum `company_message_templates.event` dan `application_notifications.event`
- Template default untuk masing-masing

**File yang perlu dibuat/ubah:**
- Migration: `create_interview_results_table`
- Migration: `add_interview_result_events_to_notification_enums`
- Model: `InterviewResult`
- `ApplicantsPage.php`: method `hasilWawancaraAction()`, `publishInterviewResultAction()`
- `applicants-page.blade.php`: banner untuk pelamar `interview`
- `ApplicationNotification.php`: tambah `EVENT_INTERVIEW_PASSED`, `EVENT_INTERVIEW_REJECTED`
- `CompanyMessageTemplate.php`: tambah konstanta event baru

---

### 2. Pembuatan Kandidat setelah `passed`

**Flow (sesuai PRD):**
- Saat status berubah ke `passed`, buat record `candidates` otomatis
- Kandidat ini yang akan dikelola di sistem HRIS / proses selanjutnya

**Yang perlu dikerjakan:**
- Di `InterviewResult::apply()` (atau model/service terkait): setelah update status ke `passed`, buat `Candidate`
- Pastikan tidak duplikat (cek `candidates.job_application_id`)

---

### 3. Template Email Wawancara di CompanyResource

**Saat ini:**
- CompanyResource edit page sudah punya section "Template Email Notifikasi"
- Hanya ada 2 event: `screening_rejected`, `screening_passed`
- Perlu tambah form field untuk `interview_scheduled`, `interview_passed`, `interview_rejected`

**File yang perlu diubah:**
- `CompanyResource.php`: tambah fieldset untuk event wawancara
- `EditCompany.php`: tambah `mutateFormDataBeforeFill` + `afterSave` untuk 3 event baru

---

### 4. Halaman Notifikasi — Filter per Event

**Saat ini:**
- Notifikasi ditampilkan semua draft tanpa filter event
- Perlu tampilkan section terpisah atau filter: "Undangan Wawancara", "Lolos Berkas", dll.

---

### 5. Update `sendNotificationsForOpening` — Support Event Interview

**Saat ini:**
- Urutan kirim: `screening_passed` dulu, lalu `screening_rejected`
- Perlu extend untuk `interview_scheduled`, `interview_passed`, `interview_rejected`
- Urutan yang masuk akal: passed → scheduled → rejected

---

## Urutan Pengerjaan yang Disarankan

1. **Hasil Wawancara** (paling kritikal untuk melengkapi alur utama)
2. **Pembuatan Kandidat otomatis** (sudah ada tabel `candidates`, tinggal trigger)
3. **Template email event wawancara** di CompanyResource
4. **Filter/grouping notifikasi** per event
5. **Update urutan kirim** `sendNotificationsForOpening`

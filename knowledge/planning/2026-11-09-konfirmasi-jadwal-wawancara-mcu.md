# Planning: Konfirmasi Kehadiran & Reschedule Jadwal Wawancara / MCU

Tanggal: 2026-11-09 · Status: **rencana awal, belum dikerjakan**

## 1. Tujuan

Saat pelamar (sudah berstatus kandidat) dijadwalkan **wawancara** atau **MCU**, ia bisa memberi **satu respons** langsung dari
halaman beranda (dashboard):

1. **Hadir**
2. **Tidak hadir dengan alasan** (mis. sakit / berhalangan) — alasan wajib diisi
3. **Reschedule / Permintaan Menjadwalkan Ulang** — mengajukan tanggal/jam baru + alasan

Pada form reschedule tampil kalimat tetap:

> Jadwal reschedule yang diajukan perlu disetujui terlebih dahulu oleh sistem. Jika tidak disetujui silahkan bisa hadir sesuai jadwal awal yang sudah ditentukan

**Aturan utama:** respons hanya bisa diberikan **1 kali**. Setelah dikirim tidak bisa diubah atau dikirim ulang oleh pelamar.

## 2. Kondisi sekarang (fakta di kode)

- Wawancara: `interview_schedules` (tanggal, jam, lokasi, catatan, `status` draft/published) dan `interview_invitees` (`interview_schedule_id`, `job_application_id` **unique**).
- MCU: `mcu_schedules` dan `mcu_invitees` (punya `status_mcu`, `catatan_mcu`). Model `McuInvitee`, `InterviewInvitee`.
- Pelamar sudah dapat notifikasi jadwal (`ApplicationNotification`, event `interview_scheduled`; template pesan di `CompanyMessageTemplate`).
- Zona waktu app `Asia/Makassar`; jadwal ditampilkan dengan label WITA.
- Belum ada tempat menyimpan respons pelamar terhadap jadwal.

## 3. Rancangan data

Respons melekat pada **invitee** (satu pelamar × satu jadwal), jadi cukup menambah kolom di `interview_invitees` dan `mcu_invitees`
(kolom identik, satu migrasi untuk keduanya):

| Kolom | Tipe | Keterangan |
|---|---|---|
| `response` | enum `attend`, `absent_with_reason`, `reschedule`, nullable | null = belum menjawab |
| `response_reason` | text, nullable | wajib untuk `absent_with_reason` dan `reschedule` |
| `proposed_date` | date, nullable | hanya `reschedule` |
| `proposed_time` | time, nullable | hanya `reschedule` |
| `responded_at` | timestamp, nullable | penanda sudah menjawab (dasar aturan 1 kali) |
| `reschedule_status` | enum `pending`, `approved`, `rejected`, nullable | hanya `reschedule` |
| `reschedule_decided_by` | foreign id user, nullable | siapa yang memutuskan |
| `reschedule_decided_at` | timestamp, nullable | |
| `reschedule_note` | text, nullable | catatan penolakan/persetujuan dari sistem/admin |

Catatan:
- Jadwal asli **tidak ditimpa** saat pengajuan. Hanya saat disetujui, invitee dipindahkan/jadwal diperbarui (lihat 5).
- "Disetujui oleh sistem" diartikan **disetujui oleh staf/admin rekrutmen** lewat panel (Filament). Lihat Pertanyaan Terbuka #1.

## 4. Sisi pelamar (beranda)

Kartu jadwal di dashboard (yang sudah menampilkan jadwal wawancara/MCU) mendapat blok **"Konfirmasi Kehadiran"**:

- Tampil bila: ada invitee pada jadwal `published`, `responded_at` null, dan jadwal belum lewat.
- Tiga pilihan (radio/tombol):
  - **Hadir** — langsung kirim setelah popup konfirmasi.
  - **Tidak hadir dengan alasan** — textarea alasan (wajib, maks 500 karakter).
  - **Reschedule** — tanggal, jam, alasan (wajib) + kalimat peringatan di atas.
- **Popup konfirmasi sebelum kirim**: "Jawaban hanya dapat dikirim 1 kali dan tidak dapat diubah."
- Setelah terkirim, blok berubah **baca-saja**: menampilkan jawaban, alasan, dan (untuk reschedule) status pengajuan:
  *Menunggu persetujuan* / *Disetujui — jadwal baru: …* / *Tidak disetujui — silakan hadir sesuai jadwal awal*.
- Validasi tanggal reschedule: harus **setelah hari ini**, dan sebelum batas yang ditentukan (lihat Pertanyaan Terbuka #3).

Wawancara dan MCU memakai komponen Blade yang sama (`partials/schedule-response.blade.php`), dibedakan parameter tipe.

## 5. Sisi admin / HR

- Daftar pelamar & halaman jadwal (Filament, `applicants-page`, `wawancara-page`) menampilkan **badge respons**: Hadir / Tidak hadir (alasan) / Reschedule (menunggu) / Belum menjawab.
- Detail pelamar: alasan, tanggal/jam yang diajukan.
- Aksi pada reschedule **pending**: **Setujui** atau **Tolak** (+ catatan).
  - **Setujui:** jadwal pelamar dipindah ke jadwal baru. Karena invitee terikat ke satu `interview_schedule`, ada dua opsi
    (lihat Pertanyaan Terbuka #2): pindahkan invitee ke jadwal lain yang sudah ada / buat jadwal baru, atau simpan tanggal-jam
    override di invitee.
  - **Tolak:** jadwal awal tetap berlaku.
- Setiap keputusan mengirim **notifikasi** ke pelamar (in-app + email), memakai pola `ApplicationNotification` + template di `CompanyMessageTemplate`.
- Notifikasi ke HR saat ada reschedule baru masuk (supaya tidak terlewat).

## 6. Backend

- Route (auth, role pelamar): `POST /jadwal/wawancara/{invitee}/respons`, `POST /jadwal/mcu/{invitee}/respons`.
- Controller satu: `ScheduleResponseController@store` — otorisasi: invitee milik user login; tolak bila `responded_at` sudah terisi (**403/422 + pesan**, ini penegak aturan "1 kali", jangan hanya mengandalkan UI); tolak bila jadwal draft atau sudah lewat.
- Penegakan atomik: `UPDATE ... WHERE responded_at IS NULL` supaya klik ganda/dua tab tidak menimpa.
- Throttle (mis. `throttle:10,1`).
- Aksi admin: `approve` / `reject` di Filament (policy: super admin atau staf perusahaan yang sama, mengikuti pola `adminSignedBiodata`).
- Event baru untuk notifikasi: `schedule_response_received` (ke HR), `reschedule_approved`, `reschedule_rejected` (ke pelamar); tambahkan ke enum notifikasi (pola migrasi `add_interview_scheduled_to_notification_enums`).

## 7. Integrasi HRIS

Cek `HrisIntegrationController`: bila jadwal wawancara/MCU diekspos lewat API, tambahkan field respons
(`response`, `reschedule_status`) sekaligus memperbarui `knowledge/INTEGRASI-HRIS.md`. Bila tidak diekspos, tidak ada perubahan.

## 8. Pengujian

`tests/Feature/ScheduleResponseTest.php` (jalankan dengan `--filter`, bukan seluruh suite; lihat catatan DB test di memori):

- Hadir / tidak hadir dengan alasan / reschedule tersimpan benar untuk wawancara dan MCU.
- Alasan wajib untuk dua opsi terakhir; tanggal/jam wajib untuk reschedule; tanggal masa lalu ditolak.
- **Kirim kedua kali ditolak** dan data pertama tidak berubah.
- Pelamar lain tidak bisa menjawab invitee orang lain.
- Jadwal draft atau sudah lewat tidak bisa dijawab.
- Setujui → jadwal berubah + notifikasi; Tolak → jadwal awal tetap + notifikasi.

## 9. Tahapan kerja

1. Migrasi kolom respons (dua tabel) + update `$fillable`/cast model.
2. Controller + route + validasi + aturan 1 kali.
3. Komponen UI di dashboard (form, popup konfirmasi, status baca-saja).
4. Badge & aksi setujui/tolak di panel admin.
5. Notifikasi + template pesan.
6. Tes, lalu update dokumentasi (HRIS bila perlu).

## 10. Pertanyaan terbuka

1. **Siapa yang menyetujui reschedule?** Rencana ini mengasumsikan staf/admin rekrutmen lewat panel. Atau perlu aturan otomatis (mis. disetujui otomatis bila slot kosong)?
2. **Cara menerapkan jadwal baru setelah disetujui:** pindah ke jadwal lain yang sudah ada, buat jadwal baru, atau override tanggal/jam per pelamar? Rekomendasi: override di invitee (paling sederhana, jadwal massal tidak terganggu).
3. **Batas waktu menjawab/mengajukan reschedule**, mis. paling lambat H-1 sebelum jadwal? Setelah batas, form dikunci dan otomatis dianggap mengikuti jadwal awal?
4. **Pelamar yang tidak menjawab:** hanya ditandai "Belum menjawab", atau perlu pengingat otomatis (email H-2)?
5. **Reschedule ditolak:** apakah pelamar boleh mengajukan lagi? (Aturan saat ini: tidak, hanya 1 kali; jadwal awal tetap.)
6. **Tidak hadir dengan alasan:** apakah pelamar otomatis gugur/ditandai tidak lanjut, atau hanya informatif dan HR yang menindaklanjuti? (Rencana: informatif saja, HR memutuskan manual.)
7. Apakah berlaku untuk **semua** jadwal, atau hanya untuk wawancara pertama dan MCU (bukan wawancara lanjutan/user)?

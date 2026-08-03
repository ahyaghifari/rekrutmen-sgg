# Perencanaan Fitur Forum

## Latar Belakang

Pelamar dan kandidat sering memiliki pertanyaan kepada tim HR/PIC selama proses rekrutmen dan onboarding. Saat ini tidak ada saluran resmi di dalam sistem — komunikasi terjadi melalui WhatsApp pribadi yang tidak terstruktur. Fitur forum menyediakan ruang terpusat dan terdokumentasi untuk tanya-jawab antara pengguna dan tim internal.

---

## Dua Ruang Forum

| Ruang | Siapa yang bisa akses | Topik umum |
|---|---|---|
| **Ruang Pelamar** | Semua user yang sudah login (punya `job_applications`) | Proses seleksi, jadwal wawancara, dokumen yang dibutuhkan |
| **Ruang Kandidat** | User yang punya record di tabel `candidates` (status `passed`) | Onboarding, kontrak, penempatan, fasilitas |

---

## Model Visibilitas Thread

**Hybrid** — thread bisa publik atau privat:

- **Publik** (default): semua member ruang yang sama bisa membaca thread & balasannya → efek FAQ, HR tidak perlu menjawab pertanyaan berulang
- **Privat**: hanya pengirim + HR yang bisa melihat → untuk hal sensitif (gaji, kondisi pribadi, negosiasi)

Pilihan visibilitas ditentukan saat membuat thread, tidak bisa diubah setelah dikirim.

---

## Siapa yang Bisa Membalas

| Aktor | Ruang Pelamar | Ruang Kandidat |
|---|---|---|
| Pengguna (pelamar/kandidat) | Bisa buat thread, tidak bisa balas thread orang lain | Bisa buat thread, tidak bisa balas thread orang lain |
| HR / Admin | Bisa balas semua thread di kedua ruang | Bisa balas semua thread di kedua ruang |

> Catatan: Untuk fase awal, user biasa tidak bisa membalas thread orang lain. Ini mencegah diskusi tidak terkontrol dan menjaga forum sebagai saluran Q&A, bukan komunitas chat.

---

## Skema Database

```sql
-- Thread utama
forum_threads
  id              ULID PK
  user_id         FK → users.id
  tier            ENUM('applicant', 'candidate')
  title           VARCHAR(255)
  body            TEXT
  is_private      BOOLEAN DEFAULT false
  is_pinned       BOOLEAN DEFAULT false   -- HR bisa pin FAQ
  is_closed       BOOLEAN DEFAULT false   -- HR bisa tutup thread
  created_at, updated_at, deleted_at

-- Balasan
forum_replies
  id              ULID PK
  thread_id       FK → forum_threads.id CASCADE DELETE
  user_id         FK → users.id
  body            TEXT
  created_at, updated_at, deleted_at
```

Tidak ada tabel khusus untuk "HR role" di fase ini — solusi sementara: kolom `is_staff` di tabel `users`, atau gunakan email domain `@syifaglobalgroup.co.id` sebagai penanda.

---

## Aturan Akses

```
Ruang Pelamar  → middleware: auth + punya minimal 1 job_application
Ruang Kandidat → middleware: auth + punya record di tabel candidates

Thread privat  → hanya tampil untuk: pemilik thread + is_staff = true
Thread orang lain → hanya read (tidak ada tombol balas untuk non-staff)
```

---

## Halaman yang Dibutuhkan

| Route | View | Keterangan |
|---|---|---|
| `GET /forum` | `forum/index` | Daftar ruang (pilih Pelamar / Kandidat) |
| `GET /forum/{tier}` | `forum/threads` | Daftar thread dalam satu ruang |
| `GET /forum/{tier}/{thread}` | `forum/show` | Detail thread + balasan |
| `POST /forum/{tier}` | — | Buat thread baru |
| `POST /forum/{tier}/{thread}/reply` | — | Kirim balasan (HR only untuk fase awal) |

---

## Fitur Fase 1 (MVP)

- [ ] Migration: `forum_threads`, `forum_replies`
- [ ] Model: `ForumThread`, `ForumReply` dengan relasi
- [ ] Middleware akses per tier
- [ ] Halaman daftar thread per ruang (publik saja)
- [ ] Halaman detail thread + balasan HR
- [ ] Form buat thread (judul, isi, pilihan privat/publik)
- [ ] Kolom `is_staff` di tabel `users` untuk menandai HR
- [ ] Notifikasi (in-app atau email) ke HR saat thread baru masuk

## Fitur Fase 2 (nice to have)

- [ ] Thread di-pin oleh HR sebagai FAQ
- [ ] HR bisa tutup thread (`is_closed`)
- [ ] Pengguna bisa edit/hapus thread sendiri (selama belum ada balasan)
- [ ] Unread badge di navbar
- [ ] Search thread

---

## Pertanyaan Terbuka

1. **Role HR**: Apakah perlu sistem role/permission yang lebih formal (misal pakai `spatie/laravel-permission`), atau cukup kolom `is_staff` di `users` untuk sekarang?
2. **Notifikasi**: Email atau cukup in-app badge dulu?
3. **Attachment**: Apakah pengguna perlu bisa upload file/gambar di thread?
4. **Moderasi**: Apakah HR perlu bisa hapus thread pengguna?

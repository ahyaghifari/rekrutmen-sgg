<?php

namespace App\Support;

/**
 * Definisi form lamaran dokter, dipakai view (render) dan controller (validasi).
 * Fase rekrutmen dari knowledge/data-dokter.md. SIP & credentialing sengaja tidak ada: pasca-rekrutmen.
 * Berkas bertanda req wajib saat kirim; sisanya opsional dan bisa dilengkapi kemudian dari halaman riwayat.
 */
class DokterForm
{
    private static function f(string $name, string $label, string $type = 'text', bool $req = true, string $extra = ''): array
    {
        return compact('name', 'label', 'type', 'req', 'extra');
    }

    private static function sel(string $name, string $label, array $opts, bool $req = true, string $extra = ''): array
    {
        return ['name' => $name, 'label' => $label, 'type' => 'select', 'req' => $req, 'opts' => $opts, 'extra' => $extra];
    }

    /** @return array<int, array{0:string,1:string,2:array}> [judul, ikon, fields] */
    public static function sections(): array
    {
        $f = [self::class, 'f'];
        $s = [self::class, 'sel'];

        return [
            ['Identitas', 'fa-id-card', [
                $f('gelar_depan', 'Gelar depan', 'text', false), $f('nama', 'Nama lengkap (tanpa gelar)'), $f('gelar_belakang', 'Gelar belakang', 'text', false),
                $f('tempat_lahir', 'Tempat lahir'), $f('tanggal_lahir', 'Tanggal lahir', 'date'),
                $f('nik', 'NIK'),
                $s('jenis_kelamin', 'Jenis kelamin', ['Laki-laki', 'Perempuan']),
                $s('agama', 'Agama', ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']),
                $s('status_pernikahan', 'Status pernikahan', ['Belum menikah', 'Menikah', 'Cerai']),
                $f('kewarganegaraan', 'Kewarganegaraan'), $s('golongan_darah', 'Golongan darah', ['A', 'B', 'AB', 'O'], false),
                $f('alamat_ktp', 'Alamat sesuai KTP', 'textarea'), $f('alamat_domisili', 'Alamat domisili', 'textarea'),
            ]],
            ['Profesi & Spesialisasi', 'fa-user-doctor', [
                $s('jenis_dokter', 'Jenis dokter', ['Spesialis', 'Subspesialis'], true, 'spesialis'),
                $f('spesialisasi', 'Spesialisasi utama', 'text', true, 'spesialis'), $f('subspesialisasi', 'Subspesialisasi', 'text', false, 'spesialis'),
                $s('status_kepegawaian', 'Status kepegawaian yang diminati', ['Tetap', 'Kontrak', 'Mitra (visiting)']),
            ]],
            ['STR (Surat Tanda Registrasi)', 'fa-certificate', [
                $f('str_nomor', 'Nomor STR'), $f('str_kolegium', 'Kolegium / penerbit', 'text', false),
                $f('str_terbit', 'Tanggal terbit', 'date'), $f('str_file', 'Scan STR', 'file'),
            ]],
            ['STR-KT (Kewenangan Tambahan, opsional)', 'fa-award', [
                $f('strkt_nomor', 'Nomor STR-KT', 'text', false), $f('strkt_berlaku', 'Berlaku sampai', 'date', false), $f('strkt_file', 'Scan STR-KT', 'file', false),
            ]],
            ['Rekening & Pajak', 'fa-wallet', [
                $f('npwp', 'NPWP', 'text', false), $f('nama_bank', 'Nama bank'), $f('nomor_rekening', 'Nomor rekening'), $f('atas_nama', 'Atas nama'),
            ]],
            ['Kontak', 'fa-phone-volume', [
                $f('no_hp', 'No. HP / WhatsApp', 'tel'), $f('email', 'Email', 'email'),
            ]],
            ['Dokumen', 'fa-folder-open', [
                $f('d_cv', 'CV', 'file'), $f('d_ktp', 'KTP', 'file'), $f('d_npwp', 'NPWP', 'file', false),
                $f('d_ijazah_dokter', 'Ijazah dokter', 'file'), $f('d_ijazah_spesialis', 'Ijazah spesialis', 'file', false), $f('d_ijazah_subspesialis', 'Ijazah subspesialis', 'file', false),
                $f('d_transkrip', 'Transkrip', 'file', false), $f('d_kompetensi', 'Sertifikat kompetensi', 'file', false),
                $f('d_rekening', 'Buku rekening', 'file', false),
            ]],
        ];
    }

    /** @return array<int, array{0:string,1:string,2:string,3:array,4:array}> [key, judul, ikon, kolom, kolom wajib] */
    public static function repeaters(): array
    {
        return [
            ['pendidikan', 'Pendidikan', 'fa-graduation-cap', [['jenjang', 'Jenjang (Profesi/Spesialis/Subspesialis)'], ['institusi', 'Institusi'], ['program', 'Program / jurusan'], ['tahun_masuk', 'Tahun masuk', 'number'], ['tahun_lulus', 'Tahun lulus', 'number'], ['ipk', 'IPK'], ['negara', 'Negara']], ['jenjang', 'institusi', 'program', 'tahun_lulus']],
            ['pelatihan', 'Pelatihan & Sertifikat (ACLS, ATLS, BTCLS, dll)', 'fa-award', [['nama', 'Nama pelatihan'], ['penyelenggara', 'Penyelenggara'], ['tanggal', 'Tanggal', 'date'], ['nomor', 'Nomor sertifikat'], ['berlaku', 'Berlaku sampai', 'date']], []],
            ['pengalaman', 'Pengalaman Kerja', 'fa-briefcase', [['faskes', 'Nama fasilitas kesehatan'], ['jabatan', 'Jabatan'], ['jenis_faskes', 'Jenis faskes'], ['mulai', 'Mulai', 'date'], ['selesai', 'Selesai', 'date'], ['alasan', 'Alasan berhenti'], ['referensi', 'Referensi (nama & kontak)']], []],
        ];
    }

    /** Key berkas yang wajib saat kirim (pas_foto selalu). */
    public static function requiredDocuments(): array
    {
        $req = ['pas_foto'];
        foreach (self::sections() as [, , $fields]) {
            foreach ($fields as $fl) {
                if ($fl['type'] === 'file' && $fl['req']) {
                    $req[] = $fl['name'];
                }
            }
        }

        return $req;
    }

    /** Semua berkas yang bisa dikumpulkan: [key => label]. pas_foto wajib saat kirim, sisanya opsional. */
    public static function documents(): array
    {
        $docs = ['pas_foto' => 'Pas foto'];
        foreach (self::sections() as [, , $fields]) {
            foreach ($fields as $fl) {
                if ($fl['type'] === 'file') {
                    $docs[$fl['name']] = $fl['label'];
                }
            }
        }

        return $docs;
    }
}

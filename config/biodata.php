<?php

/**
 * Katalog isian biodata (tab isian tunggal). Tidak ada master per perusahaan; perbedaan antar perusahaan hanya:
 *   hospital_only     => true : hanya rumah sakit (companies.is_hospital), mis. STR
 *   non_hospital_only => true : hanya selain rumah sakit, mis. akun media sosial
 * Isian yang tidak berlaku tidak tampil di form/cetak dan tidak ikut disimpan (datanya tidak dihapus).
 *
 * type: text | date | select | pair | str | address   (str & address = blok khusus di view)
 * new : true = isian baru biodata; tidak ikut Candidate::requiredDataChecklist() lama (flag HRIS tidak berubah).
 */
return [
    // Kota pada baris tanda tangan hasil cetak (docx: "Banjarbaru, ..."). Belum ada kolom kota per perusahaan.
    'signing_city' => 'Banjarbaru',

    // Tab tujuan yang sah untuk section_key (hanya tab isian tunggal): key => nomor tab di form.
    'sections' => [
        'identitas' => 1,
        'alamat' => 2,
        'informasi_tambahan' => 10,
        'penilaian_diri' => 11,
    ],

    'fields' => [
        'name'               => ['label' => 'Nama Lengkap (sesuai KTP)', 'section' => 'identitas', 'type' => 'text', 'core' => true, 'required' => true],
        'nickname'           => ['label' => 'Nama Panggilan', 'section' => 'identitas', 'type' => 'text', 'required' => false],
        'nik'                => ['label' => 'NIK', 'section' => 'identitas', 'type' => 'text', 'core' => true, 'required' => true, 'maxlength' => 16],
        'gender'             => ['label' => 'Jenis Kelamin', 'section' => 'identitas', 'type' => 'select', 'core' => true, 'required' => true, 'new' => true, 'options' => ['Laki-laki', 'Perempuan']],
        'birth_place'        => ['label' => 'Tempat Lahir', 'section' => 'identitas', 'type' => 'text', 'core' => true, 'required' => true, 'new' => true],
        'birth_date'         => ['label' => 'Tanggal Lahir', 'section' => 'identitas', 'type' => 'date', 'core' => true, 'required' => true, 'new' => true],
        'no_kk'              => ['label' => 'No. Kartu Keluarga', 'section' => 'identitas', 'type' => 'text', 'required' => true, 'maxlength' => 16],
        'blood_type'         => ['label' => 'Golongan Darah', 'section' => 'identitas', 'type' => 'text', 'required' => false],
        'body_height_weight' => ['label' => 'Tinggi/Berat Badan', 'section' => 'identitas', 'type' => 'text', 'required' => false, 'placeholder' => 'Contoh: 165cm / 55kg'],
        'religion'           => ['label' => 'Agama', 'section' => 'identitas', 'type' => 'text', 'required' => false],
        'nationality'        => ['label' => 'Kewarganegaraan', 'section' => 'identitas', 'type' => 'text', 'required' => false, 'default' => 'Indonesia'],
        'status_marital'     => ['label' => 'Status Pernikahan', 'section' => 'identitas', 'type' => 'select', 'core' => true, 'required' => true, 'options' => ['Belum Menikah', 'Sudah Menikah', 'Janda', 'Duda']],
        'marriage_date'      => ['label' => 'Tanggal Menikah', 'section' => 'identitas', 'type' => 'date', 'required' => false],
        'social_media_accounts' => ['label' => 'Akun Media Sosial', 'section' => 'identitas', 'type' => 'text', 'required' => false, 'non_hospital_only' => true],
        'mother_name'        => ['label' => 'Nama Ibu Kandung', 'section' => 'identitas', 'type' => 'text', 'required' => true],
        'emergency_contact'  => ['label' => 'Kontak Darurat', 'section' => 'identitas', 'type' => 'pair', 'required' => false, 'new' => true, 'columns' => ['emergency_contact_name', 'emergency_contact_phone']],
        'str'                => ['label' => 'STR', 'section' => 'identitas', 'type' => 'str', 'core' => true, 'required' => true, 'hospital_only' => true, 'columns' => ['no_str', 'str_seumur_hidup', 'str_berlaku_mulai', 'str_berlaku_sampai']],

        'address'            => ['label' => 'Alamat Sesuai KTP', 'section' => 'alamat', 'type' => 'address', 'core' => true, 'required' => true],
    ],
];

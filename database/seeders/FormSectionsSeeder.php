<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\FormField;
use App\Models\FormSection;
use Illuminate\Database\Seeder;

class FormSectionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHolding();
        $this->seedRsu();
    }

    // ---------------------------------------------------------------
    // Syifa Global Group (Holding) — sesuai google form/
    // ---------------------------------------------------------------
    private function seedHolding(): void
    {
        $company = Company::firstOrCreate(
            ['name' => 'Syifa Global Group'],
            ['brand_color' => '#042c6c']
        );

        // Hapus sections lama sebelum re-seed
        $company->formSections()->delete();

        $sections = [
            [
                'title' => 'Data Diri',
                'sort_order' => 1,
                'fields' => [
                    ['name' => 'full_name',        'label' => 'Nama Lengkap',            'type' => 'text',     'is_required' => true,  'sort_order' => 1],
                    ['name' => 'gender',            'label' => 'Jenis Kelamin',           'type' => 'radio',    'is_required' => true,  'sort_order' => 2, 'options' => ['choices' => ['Laki-laki', 'Perempuan']]],
                    ['name' => 'whatsapp_number',   'label' => 'No WA',                   'type' => 'text',     'is_required' => true,  'sort_order' => 3],
                    // ['name' => 'usia',              'label' => 'Usia',                    'type' => 'number',   'is_required' => true,  'sort_order' => 4],
                    ['name' => 'domicile_address',  'label' => 'Alamat Domisili',         'type' => 'textarea', 'is_required' => true,  'sort_order' => 4],
                    ['name' => 'birth_place',       'label' => 'Tempat Lahir',            'type' => 'text',     'is_required' => true,  'sort_order' => 5],
                    ['name' => 'birth_date',        'label' => 'Tanggal Lahir',           'type' => 'date',     'is_required' => true,  'sort_order' => 7],
                    ['name' => 'instagram_handle',  'label' => 'Instagram',               'type' => 'text',     'is_required' => true,  'sort_order' => 6],
                    ['name' => 'linkedin_url',      'label' => 'Akun LinkedIn',           'type' => 'text',     'is_required' => false, 'sort_order' => 7],
                ],
            ],
            [
                'title' => 'Pendidikan',
                'sort_order' => 2,
                'fields' => [
                    ['name' => 'education_level',  'label' => 'Jenjang Pendidikan',                   'type' => 'radio',  'is_required' => true,  'sort_order' => 1, 'options' => ['choices' => ['SMA/SMK', 'D-III', 'D-IV', 'S-I', 'S-II', 'Profesi']]],
                    ['name' => 'major',            'label' => 'Jurusan',                              'type' => 'text',   'is_required' => true,  'sort_order' => 2],
                    ['name' => 'school_name',      'label' => 'Nama Universitas/Instansi Pendidikan', 'type' => 'text',   'is_required' => true,  'sort_order' => 3],
                    ['name' => 'graduation_year',  'label' => 'Tahun Lulus',                          'type' => 'number', 'is_required' => true,  'sort_order' => 4],
                    ['name' => 'str_valid_until',  'label' => 'Tanggal Berlaku STR (Tenaga Kesehatan)', 'type' => 'date', 'is_required' => false, 'sort_order' => 5],
                ],
            ],
            [
                'title' => 'Posisi & Pengalaman',
                'sort_order' => 3,
                'fields' => [
                    ['name' => 'other_position_note',  'label' => 'Posisi Lain yang Diminati',              'type' => 'text',     'is_required' => true,  'sort_order' => 1],
                    ['name' => 'work_experience_note', 'label' => 'Pengalaman Kerja',                       'type' => 'textarea', 'is_required' => true,  'sort_order' => 2],
                    ['name' => 'willing_relocate',     'label' => 'Bersedia Ditempatkan di Luar Kota',      'type' => 'radio',    'is_required' => true,  'sort_order' => 3, 'options' => ['choices' => ['Ya', 'Tidak']]],
                ],
            ],
            [
                'title' => 'Ekspektasi & Ketersediaan',
                'sort_order' => 4,
                'fields' => [
                    ['name' => 'last_salary',     'label' => 'Gaji Terakhir',        'type' => 'number', 'is_required' => true, 'sort_order' => 1],
                    ['name' => 'expected_salary', 'label' => 'Gaji yang Diharapkan', 'type' => 'number', 'is_required' => true, 'sort_order' => 2],
                    ['name' => 'availability',    'label' => 'Bersedia Bergabung',   'type' => 'radio',  'is_required' => true, 'sort_order' => 3, 'options' => ['choices' => ['Segera', '2 Minggu', '1 Bulan', '>2 Bulan']]],
                ],
            ],
            [
                'title' => 'Dokumen',
                'sort_order' => 5,
                'fields' => [
                    ['name' => 'certificate_file', 'label' => 'File Sertifikat Keahlian (Jika ada)', 'type' => 'file', 'is_required' => false, 'sort_order' => 1, 'options' => ['mimes' => ['pdf', 'jpg', 'jpeg', 'png'], 'max_kb' => 10240]],
                    ['name' => 'cv_file',          'label' => 'File CV (PDF + dokumen pendukung)',   'type' => 'file', 'is_required' => true,  'sort_order' => 2, 'options' => ['mimes' => ['pdf'], 'max_kb' => 10240]],
                ],
            ],
        ];

        $this->createSections($company, $sections);
    }

    // ---------------------------------------------------------------
    // RSU Syifa Medika Banjarbaru — sesuai google-form-rs/
    // ---------------------------------------------------------------
    private function seedRsu(): void
    {
        $company = Company::firstOrCreate(
            ['name' => 'RSU Syifa Medika Banjarbaru'],
            ['brand_color' => '#0f6e3d']
        );

        $company->formSections()->delete();

        $sections = [
            [
                'title' => 'Data Diri',
                'sort_order' => 1,
                'fields' => [
                    ['name' => 'full_name',       'label' => 'Nama Lengkap (Sesuai KTP)',            'type' => 'text',     'is_required' => true,  'sort_order' => 1],
                    ['name' => 'nik',             'label' => 'NIK',                                  'type' => 'text',     'is_required' => true,  'sort_order' => 2],
                    ['name' => 'gender',          'label' => 'Jenis Kelamin',                        'type' => 'radio',    'is_required' => true,  'sort_order' => 3, 'options' => ['choices' => ['Laki-laki', 'Perempuan']]],
                    ['name' => 'birth_place',     'label' => 'Tempat Lahir',                         'type' => 'text',     'is_required' => true,  'sort_order' => 4],
                    ['name' => 'birth_date',      'label' => 'Tanggal Lahir',                        'type' => 'date',     'is_required' => true,  'sort_order' => 5],
                    ['name' => 'domicile_address','label' => 'Alamat Domisili',                      'type' => 'textarea', 'is_required' => true,  'sort_order' => 6],
                    ['name' => 'whatsapp_number', 'label' => 'Nomor Telepon (Terhubung WhatsApp)',   'type' => 'text',     'is_required' => true,  'sort_order' => 7],
                ],
            ],
            [
                'title' => 'Pendidikan',
                'sort_order' => 2,
                'fields' => [
                    ['name' => 'education_level', 'label' => 'Pendidikan Terakhir',  'type' => 'text',   'is_required' => true,  'sort_order' => 1],
                    ['name' => 'school_name',     'label' => 'Instansi Pendidikan',  'type' => 'text',   'is_required' => true,  'sort_order' => 2],
                    ['name' => 'graduation_year', 'label' => 'Tahun Lulus',          'type' => 'number', 'is_required' => true,  'sort_order' => 3],
                    ['name' => 'str_valid_until', 'label' => 'Masa Berlaku STR',     'type' => 'date',   'is_required' => false, 'sort_order' => 4],
                ],
            ],
            [
                'title' => 'Data Fisik & Kesehatan',
                'sort_order' => 3,
                'fields' => [
                    ['name' => 'tinggi_badan',  'label' => 'Tinggi Badan (cm)',    'type' => 'number',   'is_required' => true, 'sort_order' => 1],
                    ['name' => 'berat_badan',   'label' => 'Berat Badan (kg)',     'type' => 'number',   'is_required' => true, 'sort_order' => 2],
                    ['name' => 'riwayat_sakit', 'label' => 'Riwayat Sakit/Alergi','type' => 'textarea', 'is_required' => true, 'sort_order' => 3],
                ],
            ],
            [
                'title' => 'Pengalaman & Pelatihan',
                'sort_order' => 4,
                'fields' => [
                    ['name' => 'work_experience_note', 'label' => 'Pengalaman Kerja dalam 5 Tahun Terakhir (Bukan PKL/magang/tugas kuliah)', 'type' => 'textarea', 'is_required' => true, 'sort_order' => 1],
                    ['name' => 'pelatihan',            'label' => 'Pelatihan (Dalam 3 Tahun Terakhir)',                                      'type' => 'textarea', 'is_required' => true, 'sort_order' => 2],
                ],
            ],
            [
                'title' => 'Ekspektasi & Informasi Tambahan',
                'sort_order' => 5,
                'fields' => [
                    ['name' => 'expected_salary',    'label' => 'Nominal Gaji yang Diharapkan',                                          'type' => 'number',   'is_required' => true, 'sort_order' => 1],
                    ['name' => 'last_salary',        'label' => 'Nominal Gaji Terakhir',                                                 'type' => 'number',   'is_required' => true, 'sort_order' => 2],
                    ['name' => 'motivasi',           'label' => 'Jelaskan mengapa RSU Syifa Medika harus menerima Anda',                 'type' => 'textarea', 'is_required' => true, 'sort_order' => 3],
                    ['name' => 'hubungan_keluarga',  'label' => 'Hubungan keluarga dengan karyawan/pihak manajemen (jika ada, sebutkan)', 'type' => 'textarea', 'is_required' => true, 'sort_order' => 4],
                ],
            ],
            [
                'title' => 'Dokumen',
                'sort_order' => 6,
                'fields' => [
                    ['name' => 'dokumen_gabungan', 'label' => 'Upload Dokumen Lengkap (surat lamaran, CV, scan KTP, pas foto, ijazah, STR/KTA, SKCK, sertifikat) — 1 file PDF, format: "Nama Lengkap_Posisi"', 'type' => 'file', 'is_required' => true, 'sort_order' => 1, 'options' => ['mimes' => ['pdf'], 'max_kb' => 5120]],
                ],
            ],
        ];

        $this->createSections($company, $sections);
    }

    // ---------------------------------------------------------------

    private function createSections(Company $company, array $sections): void
    {
        foreach ($sections as $sectionData) {
            $fields = $sectionData['fields'];
            unset($sectionData['fields']);

            $section = $company->formSections()->create($sectionData);

            foreach ($fields as $fieldData) {
                $section->fields()->create($fieldData);
            }
        }
    }
}

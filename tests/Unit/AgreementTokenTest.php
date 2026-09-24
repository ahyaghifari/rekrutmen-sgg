<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\CompanyAgreement;
use App\Models\JobOpening;
use Tests\TestCase;

/**
 * Naskah Persetujuan Onboarding punya bagian yang berbeda per jabatan & divisi. Sebelumnya bagian
 * itu harus diketik manual per lowongan; sekarang naskahnya boleh memakai token {jabatan} dan
 * {divisi} yang diisi saat naskah dirakit (JobOpening::effectiveAgreements).
 *
 * ponytail: sengaja tanpa database. Relasi dipasang manual lewat setRelation() supaya loadMissing()
 * tidak menyentuh DB sama sekali - suite Feature project ini tidak bisa jalan di sqlite (belasan
 * migrasi memakai "ALTER TABLE ... MODIFY COLUMN ... ENUM" khas MySQL), dan membetulkan semuanya
 * jauh di luar lingkup fitur ini.
 */
class AgreementTokenTest extends TestCase
{
    private function openingWith(string $content, array $attributes = []): JobOpening
    {
        $company = new Company();
        $company->setRelation('agreements', collect([
            new CompanyAgreement(['title' => 'PEMBERIAN TUGAS', 'content' => $content]),
        ]));

        $opening = new JobOpening($attributes);
        $opening->setRelation('company', $company);
        $opening->setRelation('agreements', collect());

        return $opening;
    }

    public function test_tokens_are_replaced_with_the_opening_position_and_division(): void
    {
        $opening = $this->openingWith(
            '<p>Penugasan sebagai {jabatan} pada divisi {divisi}.</p>',
            ['title' => 'Perawat Rawat Inap Lantai 3', 'division_name' => 'Keperawatan'],
        );

        $content = $opening->effectiveAgreements()->first()['content'];

        $this->assertStringContainsString('sebagai Perawat Rawat Inap Lantai 3', $content);
        $this->assertStringContainsString('divisi Keperawatan', $content);
        $this->assertStringNotContainsString('{jabatan}', $content);
        $this->assertStringNotContainsString('{divisi}', $content);
    }

    /** {jabatan} memakai nama lowongan yang bisa dikustom, bukan nama jabatan master HRIS. */
    public function test_the_position_token_uses_the_customised_opening_title(): void
    {
        $opening = $this->openingWith('<p>{jabatan}</p>', ['title' => 'Perawat Rawat Inap Lantai 3']);

        $this->assertStringContainsString(
            'Perawat Rawat Inap Lantai 3',
            $opening->effectiveAgreements()->first()['content'],
        );
    }

    /** Divisi kosong (perusahaan mode Bebas) tidak boleh membocorkan tokennya ke kandidat. */
    public function test_a_missing_division_falls_back_to_a_dash(): void
    {
        $opening = $this->openingWith('<p>Divisi {divisi}.</p>', ['title' => 'Staf', 'division_name' => null]);

        $content = $opening->effectiveAgreements()->first()['content'];

        $this->assertStringContainsString('Divisi —.', $content);
        $this->assertStringNotContainsString('{divisi}', $content);
    }

    /** Huruf besar-kecil tidak boleh bikin token lolos tanpa terganti. */
    public function test_tokens_are_matched_regardless_of_case(): void
    {
        $opening = $this->openingWith('<p>{JABATAN} - {Divisi}</p>', ['title' => 'Staf', 'division_name' => 'Umum']);

        $this->assertSame('<p>Staf - Umum</p>', $opening->effectiveAgreements()->first()['content']);
    }

    /** Naskah tanpa token sama sekali tidak boleh berubah sedikit pun. */
    public function test_content_without_tokens_is_untouched(): void
    {
        $asli = '<p>ANDA wajib mengikuti ketentuan jam kerja PERUSAHAAN.</p>';
        $opening = $this->openingWith($asli, ['title' => 'Staf']);

        $this->assertSame($asli, $opening->effectiveAgreements()->first()['content']);
    }
}

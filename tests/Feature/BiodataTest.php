<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\InterviewInvitee;
use App\Models\InterviewSchedule;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\User;
use App\Support\BiodataData;
use App\Support\BiodataFields;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Biodata pelamar sejak wawancara pertama. Butuh MySQL dengan skema terisi (migrasi proyek memakai ALTER ... MODIFY dan
 * tidak bisa dijalankan dari nol): salin skema DB dev ke rekrutmen_test lalu jalankan dengan transaksi (tanpa mengubah data).
 * DB_CONNECTION=mysql DB_DATABASE=rekrutmen_test php artisan test --filter=BiodataTest
 */
class BiodataTest extends TestCase
{
    use DatabaseTransactions;

    private function company(): Company
    {
        return Company::create(['name' => 'PT Uji ' . Str::random(4), 'hris_code' => strtoupper(Str::random(5))]);
    }

    /** @return array{0:User,1:JobApplication} */
    private function applicant(string $status = 'shortlisted', ?string $scheduleStatus = null, ?Company $company = null): array
    {
        $company ??= $this->company();
        $opening = JobOpening::create([
            'id' => (string) Str::ulid(), 'company_id' => $company->id, 'company_name' => $company->name,
            'title' => 'Perawat', 'quota' => 1, 'is_active' => true,
        ]);
        $user = User::factory()->create();
        $app = JobApplication::create(['user_id' => $user->id, 'job_opening_id' => $opening->id, 'status' => $status]);

        if ($scheduleStatus) {
            $schedule = InterviewSchedule::create([
                'job_opening_id' => $opening->id, 'interview_date' => now()->addDays(3)->toDateString(), 'status' => $scheduleStatus,
            ]);
            InterviewInvitee::create(['interview_schedule_id' => $schedule->id, 'job_application_id' => $app->id]);
        }

        return [$user, $app];
    }

    // ── Pemicu & akses ───────────────────────────────────────────────────────

    public function test_biodata_closed_until_first_interview_is_published(): void
    {
        [$shortlisted] = $this->applicant('shortlisted');
        [$draft] = $this->applicant('shortlisted', 'draft');

        $this->actingAs($shortlisted)->get(route('data-diri.edit'))->assertForbidden();
        $this->actingAs($draft)->get(route('data-diri.edit'))->assertForbidden();
    }

    public function test_biodata_opens_with_published_schedule_and_creates_candidate(): void
    {
        [$user, $app] = $this->applicant('interview', 'published');
        $this->assertNull($user->candidate);

        $this->actingAs($user)->get(route('data-diri.edit'))
            ->assertOk()
            ->assertSee('Biodata Pelamar')
            ->assertDontSee('BPJS &amp; Rekening', false)
            ->assertDontSee('Dokumen');

        $this->assertNotNull($user->fresh()->candidate);
    }

    public function test_rejected_application_does_not_open_biodata(): void
    {
        [$user] = $this->applicant('rejected', 'published');

        $this->actingAs($user)->get(route('data-diri.edit'))->assertForbidden();
    }

    public function test_onboarding_stage_keeps_full_form(): void
    {
        [$user] = $this->applicant('onboarding');

        $this->actingAs($user)->get(route('data-diri.edit'))
            ->assertOk()
            ->assertSee('Data Diri Kandidat')
            ->assertSee('BPJS &amp; Rekening', false);
    }

    public function test_biodata_stage_does_not_save_bpjs_or_bank_columns(): void
    {
        $rs = $this->company();
        $rs->update(['is_hospital' => true]);
        [$user] = $this->applicant('interview', 'published', $rs);

        $this->actingAs($user)->patch(route('data-diri.update'), [
            'name' => 'Budi', 'birth_place' => 'Banjarbaru', 'birth_date' => '1995-02-03', 'gender' => 'Laki-laki',
            'status_marital' => 'Duda', 'emergency_contact_name' => 'Ani', 'emergency_contact_phone' => '0812',
            'bsi_bank_number' => '999', 'no_bpjskes' => '111', 'biodata_confirmed' => 1,
            'work' => [['company_name' => 'RS A', 'salary_start' => 3000000, 'salary' => 5000000, 'is_current' => 1, 'job_description' => 'Merawat']],
        ])->assertRedirect(route('data-diri.edit'));

        $c = $user->fresh()->candidate;
        $this->assertSame('Banjarbaru', $c->birth_place);
        $this->assertSame('Duda', $c->status_marital);
        $this->assertSame('Ani', $c->emergency_contact_name);
        $this->assertNull($c->bsi_bank_number);
        $this->assertNull($c->no_bpjskes);
        $this->assertNotNull($c->biodata_confirmed_at);
        $work = $c->workExperiences()->first();
        $this->assertEquals(3000000, $work->salary_start);
        $this->assertSame('Merawat', $work->job_description);
    }

    // ── Isian per jenis perusahaan (RS / bukan RS) ───────────────────────────

    public function test_social_media_only_for_non_hospital_in_personal_data_below_email_and_phone(): void
    {
        $rs = $this->company();
        $rs->update(['is_hospital' => true]);
        $other = $this->company();

        $keysFor = function ($company) {
            [$user, $app] = $this->applicant('interview', 'published', $company);
            $c = Candidate::ensureFor($app);
            $html = $this->actingAs($user)->get(route('data-diri.edit'))->assertOk()->getContent();

            return [array_column(BiodataData::for($c->fresh())['personal'], 2), $html, $user, $c];
        };

        [$rsKeys, $rsHtml] = $keysFor($rs);
        $this->assertNotContains('social_media_accounts', $rsKeys);
        $this->assertStringNotContainsString('Akun Media Sosial', $rsHtml);

        [$keys, $html, $user, $c] = $keysFor($other);
        $this->assertStringContainsString('Akun Media Sosial', $html);
        $this->assertSame(array_search('phone', $keys) + 1, array_search('social_media_accounts', $keys)); // tepat di bawah Email dan No HP

        $this->actingAs($user)->patch(route('data-diri.update'), ['social_media_accounts' => '@budi'])->assertRedirect();
        $this->assertSame('@budi', $c->fresh()->social_media_accounts);
    }

    public function test_catalog_hides_hospital_only_and_non_hospital_only_fields(): void
    {
        $rs = $this->company();
        $rs->update(['is_hospital' => true]);

        $this->assertArrayHasKey('str', BiodataFields::flat($rs));
        $this->assertArrayNotHasKey('social_media_accounts', BiodataFields::flat($rs));
        $this->assertArrayNotHasKey('str', BiodataFields::flat($this->company()));
        $this->assertArrayHasKey('social_media_accounts', BiodataFields::flat($this->company()));
        $this->assertArrayHasKey('emergency_contact', BiodataFields::flat(null));
    }

    // ── Checklist ────────────────────────────────────────────────────────────

    public function test_legacy_checklist_excludes_new_biodata_items_and_flag_is_unchanged(): void
    {
        [$user, $app] = $this->applicant('onboarding');
        $candidate = Candidate::ensureFor($app);

        $legacy = $candidate->requiredDataChecklist();
        $full = $candidate->requiredDataChecklist(true);

        foreach (['gender', 'birth_place', 'birth_date'] as $key) {
            $this->assertArrayNotHasKey($key, $legacy);
            $this->assertArrayHasKey($key, $full);
        }
        $this->assertArrayHasKey('bank_number', $legacy);
        $this->assertFalse($candidate->requiredDataComplete());
        $this->assertArrayNotHasKey('bank_number', $candidate->biodataChecklist());
    }

    // ── Offering ─────────────────────────────────────────────────────────────

    private function completeBiodata($user, Candidate $c): void
    {
        $this->fillIdentity($c);
        $c->update(['no_kk' => '6371000000000001', 'gender' => 'Laki-laki', 'mother_name' => 'Siti', 'status_marital' => 'Belum Menikah',
            'emergency_contact_name' => 'Ani', 'emergency_contact_phone' => '0812']);
        $user->forceFill(['nik' => '6371000000000011'])->save();
        $c->familyMembers()->create(['relation' => 'Ayah', 'gender' => 'Laki-laki', 'name' => 'A']);
        $c->educations()->create(['level' => 'S1']);
    }

    private function fillIdentity(Candidate $c): void
    {
        $c->update([
            'birth_place' => 'Martapura', 'birth_date' => '1990-05-06', 'ktp_address' => 'Jl. A No. 1',
            'village' => 'Kel A', 'district' => 'Kec B', 'city' => 'Kota C', 'province' => 'Prov D',
        ]);
    }

    public function test_offering_asks_only_name_when_biodata_identity_is_filled_and_ignores_manual_fields(): void
    {
        [$user, $app] = $this->applicant('offering');
        $this->fillIdentity(Candidate::ensureFor($app));

        $this->actingAs($user)->post(route('offering-letter.data'), [
            'offering_full_name' => 'Budi, S.Kep., Ners',
            'offering_birth_place' => 'Palsu', 'offering_domicile_address' => 'Palsu',
        ])->assertSessionHasNoErrors();

        $app->refresh();
        $this->assertSame('Budi, S.Kep., Ners', $app->offering_full_name);
        $this->assertSame('Martapura', $app->offering_birth_place); // disalin dari biodata
        $this->assertSame('Jl. A No. 1', $app->offering_domicile_address);
        $this->assertSame('Martapura', $user->fresh()->candidate->birth_place); // biodata tidak tertimpa
        $this->assertNotNull($app->offering_data_confirmed_at);
    }

    public function test_offering_keeps_full_form_when_biodata_is_empty_and_fills_biodata(): void
    {
        [$user, $app] = $this->applicant('offering');

        $this->actingAs($user)->post(route('offering-letter.data'), ['offering_full_name' => 'Ani'])
            ->assertSessionHasErrors(['offering_birth_place', 'offering_birth_date', 'offering_domicile_address']);

        $this->actingAs($user)->post(route('offering-letter.data'), [
            'offering_full_name' => 'Ani, A.Md.Keb', 'offering_birth_place' => 'Barabai', 'offering_birth_date' => '1992-01-02',
            'offering_domicile_address' => 'Jl. B', 'offering_village' => 'V', 'offering_district' => 'D', 'offering_city' => 'C', 'offering_province' => 'P',
        ])->assertSessionHasNoErrors();

        $candidate = $user->fresh()->candidate;
        $this->assertSame('Barabai', $candidate->birth_place);
        $this->assertSame('Jl. B', $candidate->ktp_address);
        $this->assertTrue($candidate->biodataIdentityFilled());
    }

    public function test_form_loads_saved_rows_with_dates_the_date_inputs_can_read(): void
    {
        [$user, $app] = $this->applicant('interview', 'published');
        $c = Candidate::ensureFor($app);
        $c->familyMembers()->create(['relation' => 'Ayah', 'name' => 'Pak Budi', 'birth_date' => '1965-01-01']);
        $c->workExperiences()->create(['company_name' => 'RS A', 'start_date' => '2019-01-01', 'end_date' => '2020-02-02']);

        $html = $this->actingAs($user)->get(route('data-diri.edit'))->assertOk()->getContent();

        $this->assertStringContainsString('Pak Budi', $html); // baris tersimpan termuat (bukan atribut x-data terpotong)
        $this->assertStringContainsString('1965-01-01', $html);
        $this->assertStringContainsString('2019-01-01', $html);
        $this->assertStringNotContainsString('1965-01-01T', $html); // ISO datetime membuat <input type=date> kosong
    }

    public function test_print_always_has_three_work_blocks_and_form_limits_work_to_three(): void
    {
        [$user, $app] = $this->applicant('interview', 'published');
        $c = Candidate::ensureFor($app);
        $titles = fn () => array_column(BiodataData::for($c->fresh())['works'], 'title');
        $expected = ['Pekerjaan Sekarang / Terakhir', 'Pekerjaan Sebelumnya 1', 'Pekerjaan Sebelumnya 2'];

        $this->assertSame($expected, $titles()); // kosong: tetap 3 blok seperti template
        $c->workExperiences()->create(['company_name' => 'RS A', 'is_current' => true]);
        $this->assertSame($expected, $titles());
        $this->assertSame('RS A', BiodataData::for($c->fresh())['works'][0]['company']);
        $this->assertNull(BiodataData::for($c->fresh())['works'][2]['company']);

        $four = array_map(fn ($n) => ['company_name' => "P{$n}"], [1, 2, 3, 4]);
        $this->actingAs($user)->patch(route('data-diri.update'), ['work' => $four])->assertSessionHasErrors('work');
        $this->actingAs($user)->patch(route('data-diri.update'), ['work' => array_slice($four, 0, 3)])->assertSessionHasNoErrors();
        $this->assertSame(3, $c->workExperiences()->count());
    }

    public function test_gender_birth_and_education_come_from_the_application_and_are_locked(): void
    {
        [$user, $app] = $this->applicant('interview', 'published');
        foreach (['gender' => 'Perempuan', 'birth_place' => 'Barabai', 'birth_date' => '1999-05-06', 'education_level' => 'D-III'] as $k => $v) {
            $app->answers()->create(['field_key' => $k, 'value' => $v]);
        }

        $html = $this->actingAs($user)->get(route('data-diri.edit'))->assertOk()->getContent();
        $c = $user->fresh()->candidate;

        $this->assertSame('Perempuan', $c->gender);
        $this->assertSame('Barabai', $c->birth_place);
        $this->assertSame('1999-05-06', $c->birth_date->toDateString());
        $this->assertDoesNotMatchRegularExpression('/<(input|select)[^>]*name="(gender|birth_place|birth_date)"/', $html);

        $this->actingAs($user)->patch(route('data-diri.update'), ['gender' => 'Laki-laki', 'birth_place' => 'Palsu', 'birth_date' => '2000-01-01'])->assertRedirect();
        $this->assertSame('Barabai', $c->fresh()->birth_place); // kiriman manual diabaikan

        $this->assertSame('D-III', BiodataData::for($c->fresh())['raw']['last_education'] ?? 'D-III');
    }

    public function test_candidate_can_upload_signed_biodata_until_it_is_locked(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        [$user, $app] = $this->applicant('interview', 'published');
        $file = \Illuminate\Http\UploadedFile::fake()->create('ttd.pdf', 100, 'application/pdf');

        $this->actingAs($user)->post(route('data-diri.biodata.upload-signed'), ['file' => \Illuminate\Http\UploadedFile::fake()->create('x.exe', 10)])->assertSessionHasErrors('file');

        $this->actingAs($user)->post(route('data-diri.biodata.upload-signed'), ['file' => $file])->assertSessionHas('success');
        $c = $user->fresh()->candidate;
        $this->assertNotNull($c->biodata_signed_path);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($c->biodata_signed_path);
        $this->actingAs($user)->get(route('data-diri.biodata.signed'))->assertOk();

        // Hanya sekali: unggahan kedua ditolak dan file pertama tetap.
        $this->actingAs($user)->post(route('data-diri.biodata.upload-signed'), ['file' => \Illuminate\Http\UploadedFile::fake()->create('lagi.pdf', 10, 'application/pdf')])->assertSessionHas('warning');
        $this->assertSame($c->biodata_signed_path, $c->fresh()->biodata_signed_path);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('tidak bisa diunggah ulang');
    }

    public function test_biodata_is_read_only_from_offering_stage(): void
    {
        [$user, $app] = $this->applicant('interview', 'published');
        $this->actingAs($user)->patch(route('data-diri.update'), ['no_kk' => '6371000000000001'])->assertRedirect();
        $this->assertSame('6371000000000001', $user->fresh()->candidate->no_kk);

        $app->update(['status' => 'offering']);
        $user = $user->fresh();
        // Biodata belum lengkap di offering: tetap terbuka supaya bisa dilengkapi.
        $this->assertFalse($user->candidate->biodataComplete());
        $this->actingAs($user)->patch(route('data-diri.update'), ['no_kk' => '6371000000000009'])->assertRedirect();
        $this->assertSame('6371000000000009', $user->candidate->fresh()->no_kk);
        $this->actingAs($user)->get(route('data-diri.edit'))->assertOk()->assertSee('Simpan Data Diri');

        // Sudah lengkap: terkunci.
        $this->completeBiodata($user, $user->candidate->fresh()); // model segar: model di memori tidak tahu no_kk sudah berubah
        $this->assertTrue($user->candidate->fresh()->biodataComplete());
        $this->actingAs($user)->patch(route('data-diri.update'), ['no_kk' => '6371000000000002'])->assertRedirect();
        $this->assertSame('6371000000000001', $user->candidate->fresh()->no_kk); // tidak tersimpan

        $this->actingAs($user)->get(route('data-diri.edit'))->assertOk()->assertSee('Biodata sudah dikunci')->assertDontSee('Simpan Data Diri');
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee('Isi Biodata')->assertDontSee('Perbarui Biodata')->assertDontSee('Pratinjau PDF');
    }

    public function test_onboarding_with_complete_biodata_only_accepts_bpjs_and_bank(): void
    {
        [$user, $app] = $this->applicant('onboarding');
        $c = Candidate::ensureFor($app);

        // Biodata belum lengkap: form onboarding masih terbuka penuh.
        $this->actingAs($user)->patch(route('data-diri.update'), ['no_kk' => '6371000000000003'])->assertRedirect();
        $this->assertSame('6371000000000003', $c->fresh()->no_kk);

        // Lengkap: dibekukan, hanya rekening yang tersimpan.
        $this->completeBiodata($user, $c);
        $this->actingAs($user)->patch(route('data-diri.update'), ['no_kk' => '6371000000000005', 'bsi_bank_number' => '7001234567'])->assertRedirect();

        $c->refresh();
        $this->assertSame('6371000000000001', $c->no_kk); // biodata tidak berubah
        $this->assertSame('7001234567', $c->bsi_bank_number); // rekening tersimpan
    }

    public function test_name_and_nik_come_from_the_account_and_are_locked(): void
    {
        [$user, $app] = $this->applicant('interview', 'published');
        $user->forceFill(['name' => 'Budi Akun', 'nik' => '6371000000000099'])->save();

        $html = $this->actingAs($user)->get(route('data-diri.edit'))->assertOk()->getContent();
        $c = $user->fresh()->candidate;

        $this->assertSame('Budi Akun', $c->name); // disalin dari akun
        $this->assertSame('6371000000000099', $c->nik);
        $this->assertStringContainsString('6371000000000099', $html);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="(name|nik)"/', $html); // tidak ada input yang terkirim

        $this->actingAs($user)->patch(route('data-diri.update'), ['name' => 'Palsu', 'nik' => '1111111111111111', 'no_kk' => '6371000000000077'])->assertRedirect();

        $c->refresh();
        $this->assertSame('Budi Akun', $c->name); // kiriman manual diabaikan
        $this->assertSame('6371000000000099', $c->nik);
        $this->assertSame('6371000000000077', $c->no_kk); // No. KK tetap bisa diisi

        $user->forceFill(['name' => 'Budi Baru'])->save();
        $this->actingAs($user->fresh())->get(route('data-diri.edit'))->assertOk(); // instance baru: tes memakai satu objek User lintas request
        $this->assertSame('Budi Baru', $c->fresh()->name); // ikut berubah bila akun berubah
    }

    public function test_father_is_always_male_and_mother_always_female(): void
    {
        [$user] = $this->applicant('interview', 'published');

        $this->actingAs($user)->patch(route('data-diri.update'), ['family' => [
            ['relation' => 'Ayah', 'name' => 'A', 'gender' => 'Perempuan'],
            ['relation' => 'ibu', 'name' => 'B', 'gender' => 'Laki-laki'],
            ['relation' => 'Saudara', 'name' => 'C', 'gender' => 'Perempuan'],
        ]])->assertRedirect();

        $g = $user->fresh()->candidate->familyMembers()->pluck('gender', 'name')->all();
        $this->assertSame(['A' => 'Laki-laki', 'B' => 'Perempuan', 'C' => 'Perempuan'], $g);
    }

    public function test_training_and_organization_are_limited_to_five_and_references_to_three(): void
    {
        [$user] = $this->applicant('interview', 'published');
        $rows = fn (int $n, string $key) => array_map(fn ($i) => [$key => "X{$i}"], range(1, $n));

        $this->actingAs($user)->patch(route('data-diri.update'), ['training' => $rows(6, 'type')])->assertSessionHasErrors('training');
        $this->actingAs($user)->patch(route('data-diri.update'), ['organization' => $rows(6, 'organization_name')])->assertSessionHasErrors('organization');
        $this->actingAs($user)->patch(route('data-diri.update'), ['reference' => $rows(4, 'name')])->assertSessionHasErrors('reference');

        $this->actingAs($user)->patch(route('data-diri.update'), [
            'training' => $rows(5, 'type'), 'organization' => $rows(5, 'organization_name'), 'reference' => $rows(3, 'name'),
        ])->assertSessionHasNoErrors();
        $c = $user->fresh()->candidate;
        $this->assertSame([5, 5, 3], [$c->trainings()->count(), $c->organizations()->count(), $c->references()->count()]);
    }

    public function test_print_phone_falls_back_to_whatsapp_from_the_application_form(): void
    {
        [$user, $app] = $this->applicant('interview', 'published');
        $c = Candidate::ensureFor($app);
        $phone = fn () => collect(BiodataData::for($c->fresh())['personal'])->firstWhere(2, 'phone')[1];

        $this->assertNull($phone());
        $app->answers()->create(['field_key' => 'whatsapp_number', 'value' => '08111222333']);
        $this->assertSame('08111222333', $phone());
        $user->forceFill(['phone' => '0899'])->save();
        $this->assertSame('0899', $phone()); // nomor akun didahulukan
    }

    public function test_str_and_work_description_are_only_for_hospitals(): void
    {
        $rs = $this->company();
        $rs->update(['is_hospital' => true]);
        $other = $this->company();

        foreach ([[$rs, true], [$other, false]] as [$company, $isHospital]) {
            [$user, $app] = $this->applicant('interview', 'published', $company);
            $app->jobOpening->update(['is_nakes' => true]);
            $c = Candidate::ensureFor($app);

            $html = $this->actingAs($user)->get(route('data-diri.edit'))->assertOk()->getContent();
            $keys = array_column(BiodataData::for($c->fresh())['personal'], 2);
            $d = BiodataData::for($c->fresh());

            $this->assertSame($isHospital, str_contains($html, 'No. STR'));
            $this->assertSame($isHospital, in_array('str', $keys, true));
            $this->assertSame($isHospital, array_key_exists('no_str', $c->fresh()->requiredDataChecklist()));
            $this->assertSame($isHospital, str_contains($html, 'Tugas &amp; Tanggung Jawab'));
            $this->assertSame($isHospital, $d['hospital']);

            $this->actingAs($user)->patch(route('data-diri.update'), ['work' => [['company_name' => 'X', 'is_current' => 1, 'job_description' => 'Uraian', 'org_position_description' => 'Posisi']]])->assertRedirect();
            $w = $c->fresh()->workExperiences()->first();
            $this->assertSame($isHospital ? 'Uraian' : null, $w->job_description);
            $this->assertSame($isHospital ? 'Posisi' : null, $w->org_position_description);
        }
    }

    public function test_pdf_wording_differs_between_hospital_and_other_companies(): void
    {
        $rs = $this->company();
        $rs->update(['is_hospital' => true, 'name' => 'RSU Uji']);
        [$user, $app] = $this->applicant('interview', 'published', $rs);
        $html = fn ($u) => view('candidate-profile.biodata-pdf', ['d' => BiodataData::for($u->fresh()->candidate)])->render();
        Candidate::ensureFor($app);

        $out = $html($user);
        $this->assertStringContainsString('Kapan Anda dapat mulai bekerja di RSU Uji?', $out);
        $this->assertStringContainsString('Jelaskan secara singkat tugas', $out);
        $this->assertStringContainsString('Sebutkan gaji dan fasilitas yang Anda harapkan di RSU Uji?', $out);

        [$user2, $app2] = $this->applicant('interview', 'published');
        Candidate::ensureFor($app2);
        $out2 = $html($user2);
        $this->assertStringContainsString('Kapan Anda dapat mulai bekerja bersama kami?', $out2);
        $this->assertStringNotContainsString('Jelaskan secara singkat tugas', $out2);
        $this->assertStringContainsString('Sebutkan gaji dan fasilitas yang Anda harapkan di ' . $app2->jobOpening->company_name . '?', $out2); // nama perusahaan, bukan "perusahaan kami"
    }

    // ── Cetak ────────────────────────────────────────────────────────────────

    public function test_biodata_previews_as_inline_pdf(): void
    {
        [$user, $app] = $this->applicant('interview', 'published');
        $candidate = Candidate::ensureFor($app);
        $this->fillIdentity($candidate);
        $candidate->familyMembers()->create(['relation' => 'Ayah', 'name' => 'Pak Budi']);
        $candidate->educations()->create(['level' => 'D3', 'school_name' => 'Poltekkes']);

        $pdf = $this->actingAs($user)->get(route('data-diri.biodata'));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringStartsWith('inline', $pdf->headers->get('content-disposition')); // pratinjau, bukan unduh langsung
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $this->actingAs($user)->get('/data-diri-kandidat/biodata.docx')->assertNotFound(); // Word tidak disediakan
    }

    public function test_biodata_can_be_downloaded_as_attachment(): void
    {
        [$user] = $this->applicant('interview', 'published');

        $r = $this->actingAs($user)->get(route('data-diri.biodata.unduh'))->assertOk();
        $this->assertStringStartsWith('attachment', $r->headers->get('content-disposition'));
        $this->assertSame('application/pdf', $r->headers->get('content-type'));
    }

    public function test_biodata_download_is_closed_when_not_open(): void
    {
        [$user] = $this->applicant('shortlisted');

        $this->actingAs($user)->get(route('data-diri.biodata'))->assertForbidden();
    }
}

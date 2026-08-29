<x-filament-panels::page>

@php
$app = $this->invitee->jobApplication;
$applicantName = $app->answersMap()['full_name'] ?? $app->user->name;
$nurse  = $this->invitee->nurseAssessment;
$lab    = $this->invitee->labResult;
$radio  = $this->invitee->radiologyResult;
$doctor = $this->invitee->doctorExamination;

$fmt = function ($value) {
    if (is_bool($value)) return $value ? 'Ya' : 'Tidak';
    if ($value === null || $value === '') return '—';
    return $value;
};
@endphp

<x-filament::link icon="heroicon-m-arrow-left" href="{{ route('filament.admin.pages.mcu-page') }}">
    Kembali ke MCU
</x-filament::link>

<x-filament::section>
    <div class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $app->jobOpening->title }}</div>
    <div class="text-lg font-extrabold text-gray-950 dark:text-white mt-1">{{ $applicantName }}</div>
</x-filament::section>

<x-filament::section heading="Perawat" icon="heroicon-m-heart" collapsible :collapsed="! $nurse">
    @if($nurse)
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm">
        @foreach([
            'Tinggi Badan' => $nurse->tinggi_badan . ' cm',
            'Berat Badan' => $nurse->berat_badan . ' kg',
            'Tekanan Darah' => $nurse->tekanan_darah,
            'Nadi' => $nurse->nadi,
            'Suhu' => $nurse->suhu . ' °C',
            'Pernafasan' => $nurse->pernafasan,
        ] as $label => $value)
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $label }}</div>
            <div class="text-gray-900 dark:text-gray-100">{{ $fmt($value) }}</div>
        </div>
        @endforeach
    </div>

    <div class="mt-4 text-sm">
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Keluhan Kesehatan</div>
        <div class="text-gray-900 dark:text-gray-100">{{ $fmt($nurse->keluhan_kesehatan) }}</div>
    </div>

    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
        @foreach([
            'Riwayat Penyakit Keluarga: Hipertensi' => $nurse->rpk_hipertensi,
            'Riwayat Penyakit Keluarga: Stroke' => $nurse->rpk_stroke,
            'Riwayat Penyakit Keluarga: Jantung' => $nurse->rpk_jantung,
            'Riwayat Penyakit Keluarga: Diabetes' => $nurse->rpk_diabetes,
            'Riwayat Penyakit Keluarga: Lainnya' => $nurse->rpk_lainnya,
            'Alergi Makanan' => $nurse->alergi_makanan,
            'Alergi Obat' => $nurse->alergi_obat,
            'Alergi Debu' => $nurse->alergi_debu,
            'Alergi Lainnya' => $nurse->alergi_lainnya,
            'Kebiasaan Olahraga' => $nurse->kebiasaan_olahraga,
            'Kebiasaan Merokok' => $nurse->kebiasaan_merokok,
            'Kebiasaan Alkohol' => $nurse->kebiasaan_alkohol,
            'Kebiasaan Obat-obatan' => $nurse->kebiasaan_obat,
            'Kebiasaan Lainnya' => $nurse->kebiasaan_lainnya,
        ] as $label => $value)
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $label }}</div>
            <div class="text-gray-900 dark:text-gray-100">{{ $fmt($value) }}</div>
        </div>
        @endforeach
    </div>

    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Riwayat Penyakit Dahulu</div>
            <div class="text-gray-900 dark:text-gray-100">{{ $fmt($nurse->riwayat_penyakit_dahulu) }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Riwayat Operasi</div>
            <div class="text-gray-900 dark:text-gray-100">{{ $fmt($nurse->riwayat_operasi) }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Hasil EKG</div>
            <div class="text-gray-900 dark:text-gray-100">{{ $fmt($nurse->hasil_ekg) }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Keterangan EKG</div>
            <div class="text-gray-900 dark:text-gray-100">{{ $fmt($nurse->keterangan_ekg) }}</div>
        </div>
    </div>

    @if($nurse->file_ekg)
    <div class="mt-4">
        <x-filament::link icon="heroicon-m-document" :href="asset('storage/' . $nurse->file_ekg)" target="_blank">File EKG</x-filament::link>
    </div>
    @endif

    <div class="mt-4 text-sm">
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Catatan</div>
        <div class="text-gray-900 dark:text-gray-100">{{ $fmt($nurse->catatan) }}</div>
    </div>
    @else
    <p class="text-sm text-gray-400">Belum diisi.</p>
    @endif
</x-filament::section>

<x-filament::section heading="Laboratorium" icon="heroicon-m-beaker" collapsible :collapsed="! $lab">
    @if($lab)
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-3 text-sm">
        @foreach([
            'Hemoglobin' => $lab->hemoglobin, 'Leukosit' => $lab->leukosit, 'Eritrosit' => $lab->eritrosit,
            'Trombosit' => $lab->trombosit, 'Hematokrit' => $lab->hematokrit, 'Basofil' => $lab->basofil,
            'Eosinofil' => $lab->eosinofil, 'Stab' => $lab->stab, 'Segmen' => $lab->segmen,
            'Limfosit' => $lab->limfosit, 'Monosit' => $lab->monosit, 'MCV' => $lab->mcv,
            'MCH' => $lab->mch, 'MCHC' => $lab->mchc, 'GDS' => $lab->gds, 'GDA' => $lab->gda,
        ] as $label => $value)
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $label }}</div>
            <div class="text-gray-900 dark:text-gray-100">{{ $fmt($value) }}</div>
        </div>
        @endforeach
    </div>

    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">HBsAg</div>
            <div class="text-gray-900 dark:text-gray-100">{{ $fmt($lab->hbsag) }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">HIV</div>
            <div class="text-gray-900 dark:text-gray-100">{{ $fmt($lab->hiv) }}</div>
        </div>
    </div>

    <div class="mt-4 text-sm">
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Kesimpulan</div>
        <div class="text-gray-900 dark:text-gray-100">{{ $fmt($lab->kesimpulan) }}</div>
    </div>

    @if($lab->file_path)
    <div class="mt-4">
        <x-filament::link icon="heroicon-m-document" :href="asset('storage/' . $lab->file_path)" target="_blank">File Hasil Lab</x-filament::link>
    </div>
    @endif
    @else
    <p class="text-sm text-gray-400">Belum diisi.</p>
    @endif
</x-filament::section>

<x-filament::section heading="Radiologi" icon="heroicon-m-camera" collapsible :collapsed="! $radio">
    @if($radio)
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
        @foreach([
            'Jenis Pemeriksaan' => $radio->jenis_pemeriksaan,
            'Foto Thorax' => $radio->foto_thorax,
            'Jantung' => $radio->jantung,
            'Paru Kanan' => $radio->paru_kanan,
            'Paru Kiri' => $radio->paru_kiri,
            'Diafragma' => $radio->diafragma,
            'Tulang' => $radio->tulang,
        ] as $label => $value)
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $label }}</div>
            <div class="text-gray-900 dark:text-gray-100">{{ $fmt($value) }}</div>
        </div>
        @endforeach
    </div>

    <div class="mt-4 text-sm">
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Kesimpulan</div>
        <div class="text-gray-900 dark:text-gray-100">{{ $fmt($radio->kesimpulan) }}</div>
    </div>

    @if($radio->file_path)
    <div class="mt-4">
        <x-filament::link icon="heroicon-m-document" :href="asset('storage/' . $radio->file_path)" target="_blank">File Hasil Radiologi</x-filament::link>
    </div>
    @endif
    @else
    <p class="text-sm text-gray-400">Belum diisi.</p>
    @endif
</x-filament::section>

<x-filament::section heading="Dokter" icon="heroicon-m-user-circle" collapsible :collapsed="! $doctor">
    @if($doctor)
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm">
        @foreach([
            'Kulit' => $doctor->kulit, 'Rambut' => $doctor->rambut, 'Visus Mata' => $doctor->visus_mata,
            'Buta Warna' => $doctor->buta_warna, 'Lapang Pandang' => $doctor->lapang_pandang, 'Hidung' => $doctor->hidung,
            'Telinga' => $doctor->telinga, 'Tonsil' => $doctor->tonsil, 'Faring' => $doctor->faring,
            'Gigi' => $doctor->gigi, 'Kelenjar Gondok' => $doctor->kelenjar_gondok, 'Suara Pernafasan' => $doctor->suara_pernafasan,
            'Ronchi' => $doctor->ronchi, 'Wheezing' => $doctor->wheezing, 'Lainnya (Paru)' => $doctor->lainnya_paru,
            'Jantung' => $doctor->jantung, 'Hati' => $doctor->hati, 'Limpa' => $doctor->limpa,
            'Epigastrium' => $doctor->epigastrium, 'Ginjal' => $doctor->ginjal, 'Lainnya (Abdomen)' => $doctor->lainnya_abdomen,
            'Ekstremitas' => $doctor->ekstremitas, 'Neurologi' => $doctor->neurologi,
        ] as $label => $value)
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $label }}</div>
            <div class="text-gray-900 dark:text-gray-100">{{ $fmt($value) }}</div>
        </div>
        @endforeach
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 text-sm">
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Diagnosa</div>
            <div class="text-gray-900 dark:text-gray-100">{{ $fmt($doctor->diagnosa) }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Rekomendasi/Saran Dokter</div>
            <div class="text-gray-900 dark:text-gray-100 font-semibold">{{ $fmt($doctor->rekomendasi) }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Catatan</div>
            <div class="text-gray-900 dark:text-gray-100">{{ $fmt($doctor->catatan) }}</div>
        </div>
    </div>
    @else
    <p class="text-sm text-gray-400">Belum diisi.</p>
    @endif
</x-filament::section>

</x-filament-panels::page>

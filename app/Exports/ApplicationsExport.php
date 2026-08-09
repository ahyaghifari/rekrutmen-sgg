<?php

namespace App\Exports;

use App\Models\JobApplication;
use App\Models\JobOpening;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class ApplicationsExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        private string  $jobOpeningId,
        private ?string $status = null,
    ) {}

    private function getFieldMap(): array
    {
        $jobOpening = JobOpening::with('company.formSections.fields')->find($this->jobOpeningId);
        $map = [];
        foreach ($jobOpening?->company?->formSections ?? [] as $section) {
            foreach ($section->fields as $field) {
                $label = $field->type === 'file' ? $field->label . ' (Link)' : $field->label;
                $map[$field->name] = ['label' => $label, 'type' => $field->type];
            }
        }
        return $map;
    }

    public function headings(): array
    {
        return array_merge(
            ['No', 'Status', 'Tanggal Daftar'],
            array_column($this->getFieldMap(), 'label')
        );
    }

    public function collection(): Collection
    {
        $fieldMap     = $this->getFieldMap();
        $fieldKeys    = array_keys($fieldMap);
        $statusLabels = [
            'applied'     => 'Lamaran Masuk',
            'screening'   => 'Peninjauan Berkas',
            'shortlisted' => 'Lolos Seleksi Berkas',
            'interview'   => 'Wawancara',
            'passed'      => 'Diterima',
            'rejected'    => 'Tidak Dilanjutkan',
        ];

        $applications = JobApplication::with('answers')
            ->where('job_opening_id', $this->jobOpeningId)
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->get();

        return $applications->map(function ($app, $i) use ($fieldKeys, $fieldMap, $statusLabels) {
            $answers = $app->answersMap();
            $row = [
                $i + 1,
                $statusLabels[$app->status] ?? $app->status,
                $app->created_at->format('d/m/Y H:i'),
            ];
            foreach ($fieldKeys as $key) {
                $value = $answers[$key] ?? '';
                if ($fieldMap[$key]['type'] === 'file' && $value) {
                    $value = route('lamaran.attachment', ['application' => $app->id, 'field' => $key]);
                }
                $row[] = $value;
            }
            return $row;
        });
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

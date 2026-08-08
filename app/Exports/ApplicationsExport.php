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
        private string $jobOpeningId
    ) {}

    private function getFieldMap(): array
    {
        $jobOpening = JobOpening::with('company.formSections.fields')->find($this->jobOpeningId);
        $map = [];
        foreach ($jobOpening?->company?->formSections ?? [] as $section) {
            foreach ($section->fields as $field) {
                if ($field->type === 'file') continue;
                $map[$field->name] = $field->label;
            }
        }
        return $map;
    }

    public function headings(): array
    {
        return array_merge(
            ['No', 'Status', 'Tanggal Daftar'],
            array_values($this->getFieldMap())
        );
    }

    public function collection(): Collection
    {
        $fieldMap     = $this->getFieldMap();
        $fieldKeys    = array_keys($fieldMap);
        $statusLabels = [
            'applied'   => 'Lamaran Masuk',
            'screening' => 'Screening',
            'interview' => 'Wawancara',
            'passed'    => 'Diterima',
            'rejected'  => 'Tidak Dilanjutkan',
        ];

        $applications = JobApplication::with('answers')
            ->where('job_opening_id', $this->jobOpeningId)
            ->latest()
            ->get();

        return $applications->map(function ($app, $i) use ($fieldKeys, $statusLabels) {
            $answers = $app->answersMap();
            $row = [
                $i + 1,
                $statusLabels[$app->status] ?? $app->status,
                $app->created_at->format('d/m/Y H:i'),
            ];
            foreach ($fieldKeys as $key) {
                $row[] = $answers[$key] ?? '';
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

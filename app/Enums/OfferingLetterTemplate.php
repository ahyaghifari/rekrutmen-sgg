<?php

namespace App\Enums;

/**
 * Gaya visual offering letter yang dipakai perusahaan. Tambah case baru di sini
 * kalau ada perusahaan dengan gaya surat yang beda lagi dari SGG/RS.
 */
enum OfferingLetterTemplate: string
{
    case Sgg = 'sgg';
    case Rs  = 'rs';

    public function label(): string
    {
        return match ($this) {
            self::Sgg => 'Syifa Global Group (tabel kompensasi)',
            self::Rs  => 'RSU Syifa Medika (list kompensasi)',
        };
    }

    public function view(): string
    {
        return match ($this) {
            self::Sgg => 'pdf.offering-letter-sgg',
            self::Rs  => 'pdf.offering-letter-rs',
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_map(fn ($case) => $case->value, self::cases()),
            array_map(fn ($case) => $case->label(), self::cases()),
        );
    }
}

<?php

namespace App\Support;

use App\Models\Company;

/**
 * Daftar isian biodata untuk satu perusahaan, dari katalog config/biodata.php. Satu-satunya tempat aturan tampil/wajib/tab/urutan
 * (dipakai form, checklist, dan cetak). Aturan per perusahaan hanya dua jenis, tanpa master di database:
 *  - `hospital_only`     : hanya rumah sakit (companies.is_hospital), mis. STR.
 *  - `non_hospital_only` : hanya perusahaan selain rumah sakit, mis. akun media sosial.
 */
class BiodataFields
{
    /**
     * @return array<string, list<array>> tab (section_key) => isian yang tampil, berurutan.
     *         Tiap isian: key, label, type, section, required, block, def (definisi katalog).
     */
    public static function sections(?Company $company): array
    {
        $hospital = (bool) $company?->is_hospital;
        $order = array_fill_keys(array_keys(config('biodata.sections')), []);

        foreach (config('biodata.fields') as $key => $def) {
            if (($def['hospital_only'] ?? false) && ! $hospital) continue;
            if (($def['non_hospital_only'] ?? false) && $hospital) continue;

            $order[$def['section']][] = [
                'key' => $key,
                'label' => $def['label'],
                'type' => $def['type'],
                'section' => $def['section'],
                'enabled' => true,
                'required' => $def['required'],
                'block' => false,
                'after' => null,
                'def' => $def,
            ];
        }

        return $order;
    }

    /** @return array<string, array> key => isian yang tampil, tanpa pengelompokan tab */
    public static function flat(?Company $company): array
    {
        return collect(self::sections($company))->flatten(1)->keyBy('key')->all();
    }
}

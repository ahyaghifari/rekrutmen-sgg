<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Proxy daftar wilayah dari wilayah.id (tanpa header CORS, jadi tidak bisa dipanggil langsung
 * dari browser). Kode kosong = provinsi, "32" = kota/kab, "32.01" = kecamatan, "32.01.01" =
 * kelurahan/desa. API gagal -> 503, form di dashboard otomatis pindah ke input teks biasa.
 */
class WilayahController extends Controller
{
    private const PATHS = [0 => 'provinces', 2 => 'regencies', 5 => 'districts', 8 => 'villages'];

    public function show(?string $code = null): JsonResponse
    {
        $code = (string) $code;
        $path = self::PATHS[strlen($code)] ?? abort(404);
        $file = $code === '' ? "{$path}.json" : "{$path}/{$code}.json";

        $data = Cache::get("wilayah:{$file}");

        if ($data === null) {
            try {
                $res = Http::timeout(5)->get("https://wilayah.id/api/{$file}");
                $data = $res->successful() ? $res->json('data') : null;
            } catch (\Throwable) {
                $data = null;
            }

            if (! is_array($data)) {
                return response()->json(['message' => 'Wilayah.id tidak dapat dihubungi.'], 503);
            }

            Cache::put("wilayah:{$file}", $data, now()->addDays(30));
        }

        return response()->json(['data' => $data]);
    }
}

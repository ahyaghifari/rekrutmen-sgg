<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\JobOpening;

class PublicController extends Controller
{
    public function welcome()
    {
        $openings = JobOpening::with('company')
            ->active()
            ->whereHas('company', fn ($q) => $q->where('is_active', true))
            ->orderBy('created_at', 'desc')
            ->get();

        $slides = Company::all()->map(function ($company) {
            [$r, $g, $b] = sscanf($company->brand_color ?? '#042c6c', '#%02x%02x%02x');
            $rBright = min(255, intval($r + (255 - $r) * 0.28));
            $gBright = min(255, intval($g + (255 - $g) * 0.28));
            $bBright = min(255, intval($b + (255 - $b) * 0.28));
            return [
                'name'   => $company->name,
                'image'  => $company->hero_image_path ? asset('storage/'.$company->hero_image_path) : asset('people.png'),
                'logo'   => $company->logo_path ? asset('storage/'.$company->logo_path) : null,
                'color1' => sprintf('#%02x%02x%02x', $rBright, $gBright, $bBright),
                'color2' => sprintf('#%02x%02x%02x', intval($r * 0.65), intval($g * 0.65), intval($b * 0.65)),
            ];
        })->values()->all();

        $contacts = Company::active()
            ->where(fn ($q) => $q->whereNotNull('email')->orWhereNotNull('phone'))
            ->get(['name', 'email', 'phone']);

        return view('welcome', compact('openings', 'slides', 'contacts'));
    }

    public function lowongan(JobOpening $jobOpening)
    {
        abort_if(! $jobOpening->is_active, 404);

        $jobOpening->load('company');
        $co    = $jobOpening->company;
        $brand = $co?->brand_color ?? '#042c6c';
        [$r, $g, $b] = sscanf($brand, '#%02x%02x%02x');
        $brandGrad = sprintf('#%02x%02x%02x', min(255, intval($r + (255 - $r) * 0.28)), min(255, intval($g + (255 - $g) * 0.28)), min(255, intval($b + (255 - $b) * 0.28)));
        $brandDark = sprintf('#%02x%02x%02x', intval($r * 0.65), intval($g * 0.65), intval($b * 0.65));

        return view('lowongan.show', compact('jobOpening', 'co', 'brand', 'brandGrad', 'brandDark', 'r', 'g', 'b'));
    }
}

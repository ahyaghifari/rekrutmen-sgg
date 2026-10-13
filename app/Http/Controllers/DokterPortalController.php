<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\DoctorApplication;
use App\Support\DokterForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DokterPortalController extends Controller
{
    private const FILE = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

    public function index()
    {
        $banners = collect(glob(public_path('banner-dokter/banner-*.{jpg,jpeg,png,webp}'), GLOB_BRACE))
            ->map(fn ($p) => asset('banner-dokter/'.basename($p)))->values();
        $hospitals = $this->hospitals();

        return view('dokter.index', [
            'banners'     => $banners,
            'umumOpen'    => $hospitals->where('dokter_umum_open', true)->count(),
            'hospitals'   => $hospitals->count(),
            'application' => $this->application(),
        ]);
    }

    // Satu akun satu lamaran: kalau sudah ada, form diganti halaman riwayat.
    public function lamar(Request $request)
    {
        if ($this->application()) {
            return redirect()->route('dokter.riwayat');
        }

        $hospitals = $this->hospitals();

        return view('dokter.lamar', [
            'jenis'     => $request->query('jenis') === 'spesialis' ? 'spesialis' : 'umum',
            'umumOpen'  => $hospitals->where('dokter_umum_open', true)->values(),
            'hospitals' => $hospitals,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->application()) {
            return redirect()->route('dokter.riwayat');
        }

        $jenis    = $request->input('jenis') === 'spesialis' ? 'spesialis' : 'umum';
        $spes     = $jenis === 'spesialis';
        $hospital = $this->hospitals()->when(! $spes, fn ($c) => $c->where('dokter_umum_open', true))->pluck('id');

        $rules = [
            'company_id' => ['required', Rule::in($hospital)],
            'unit_minat' => ['nullable', 'string', 'max:255'],
            'pas_foto'   => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
        $scalar = [];
        foreach (DokterForm::sections() as [, , $fields]) {
            foreach ($fields as $fl) {
                if ($fl['extra'] === 'spesialis' && ! $spes) {
                    continue;
                }
                if ($fl['type'] === 'file') {
                    $rules[$fl['name']] = [$fl['req'] ? 'required' : 'nullable', ...self::FILE];
                    continue;
                }
                $scalar[] = $fl['name'];
                $rules[$fl['name']] = [$fl['req'] ? 'required' : 'nullable', ...match ($fl['type']) {
                    'date'     => ['date'],
                    'email'    => ['email'],
                    'select'   => [Rule::in($fl['opts'])],
                    'textarea' => ['string', 'max:2000'],
                    default    => ['string', 'max:255'],
                }];
            }
        }
        $rules['nik'] = ['required', 'digits:16'];
        $rules['dokter_province'] = ['required', 'string', 'max:100'];
        $rules['dokter_city']     = ['required', 'string', 'max:100'];
        $rules['pendidikan']      = ['required', 'array', 'min:1'];

        foreach (DokterForm::repeaters() as [$key, , , $cols, $wajib]) {
            $rules[$key] = $key === 'pendidikan' ? $rules['pendidikan'] : ['nullable', 'array'];
            foreach ($cols as $c) {
                $rules["$key.*.{$c[0]}"] = [in_array($c[0], $wajib) ? 'required' : 'nullable', ...match ($c[2] ?? 'text') {
                    'date'   => ['date'],
                    'number' => ['numeric'],
                    default  => ['string', 'max:255'],
                }];
            }
            $rules["$key.*.berkas"] = ['nullable', ...self::FILE];
        }

        $data = $request->validate($rules);

        $app = DoctorApplication::create([
            'user_id'    => $request->user()->id,
            'jenis'      => $jenis,
            'company_id' => $data['company_id'],
            'unit_minat' => $data['unit_minat'] ?? null,
            'data'       => [],
        ]);

        $dir   = "doctor-applications/{$app->id}";
        $files = [];
        foreach (array_keys(DokterForm::documents()) as $doc) {
            if ($request->hasFile($doc)) {
                $files[$doc] = $this->storeFile($request->file($doc), $dir);
            }
        }

        $payload = collect($data)->only([...$scalar, 'dokter_province', 'dokter_city'])->all();
        foreach (DokterForm::repeaters() as [$key]) {
            foreach ($data[$key] ?? [] as $i => $row) {
                $berkas = $request->file("$key.$i.berkas");
                unset($row['berkas']);
                if (! array_filter($row)) {
                    continue; // baris kosong
                }
                if ($berkas) {
                    $row['berkas'] = $this->storeFile($berkas, $dir);
                }
                $payload[$key][] = $row;
            }
        }

        $app->update(['data' => $payload, 'files' => $files]);

        return redirect()->route('dokter.riwayat')->with('status', 'Lamaran berhasil dikirim.');
    }

    public function riwayat()
    {
        $application = $this->application() ?? abort(redirect()->route('dokter.lamar'));
        $application->load('company');

        return view('dokter.riwayat', ['application' => $application, 'documents' => DokterForm::documents()]);
    }

    // Melengkapi / mengganti satu berkas dari halaman riwayat.
    public function uploadDokumen(Request $request, string $key): RedirectResponse
    {
        $application = $this->application() ?? abort(404);
        abort_unless(array_key_exists($key, DokterForm::documents()), 404);

        $request->validate(['berkas' => ['required', ...self::FILE]]);

        $files = $application->files ?? [];
        if (isset($files[$key])) {
            Storage::delete($files[$key]['path']);
        }
        $files[$key] = $this->storeFile($request->file('berkas'), "doctor-applications/{$application->id}");
        $application->update(['files' => $files]);

        return back()->with('status', DokterForm::documents()[$key].' berhasil diunggah.');
    }

    public function berkas(string $key)
    {
        $application = $this->application() ?? abort(404);
        $file = ($application->files ?? [])[$key] ?? abort(404);

        return Storage::download($file['path'], $file['name']);
    }

    private function storeFile($upload, string $dir): array
    {
        return [
            'path' => $upload->store($dir),
            'name' => $upload->getClientOriginalName(),
            'at'   => now()->toDateTimeString(),
        ];
    }

    private function application(): ?DoctorApplication
    {
        return auth()->check() ? DoctorApplication::where('user_id', auth()->id())->first() : null;
    }

    private function hospitals()
    {
        return Company::active()->where('is_hospital', true)->orderBy('name')->get(['id', 'name', 'logo_path', 'dokter_umum_open']);
    }
}

<x-filament-panels::page>

    {{-- Filter: Perusahaan + Lowongan --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Perusahaan</label>
                <select wire:model.live="companyId"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">— Semua perusahaan —</option>
                    @foreach($this->getCompanies() as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Lowongan</label>
                <select wire:model.live="jobOpeningId" @if(!$this->companyId) disabled @endif
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <option value="">— Pilih lowongan —</option>
                    @foreach($this->getJobOpenings() as $opening)
                        <option value="{{ $opening->id }}">{{ $opening->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if($this->jobOpeningId && $this->getSelectedJobOpening()?->requires_psychotest)
    <div class="fi-section rounded-xl bg-amber-50 dark:bg-amber-950/30 ring-1 ring-amber-200 dark:ring-amber-800 px-6 py-3 text-xs text-amber-800 dark:text-amber-300">
        <strong>Catatan:</strong> lowongan ini juga mewajibkan psikotes offline tambahan di luar sistem. Koordinasikan jadwalnya secara manual dengan kandidat.
    </div>
    @endif

    {{-- Banner: siap diaktifkan tesnya --}}
    @if($this->jobOpeningId && $this->getBelumDiaktifkanCount() > 0)
    <div class="fi-section rounded-xl bg-sky-50 dark:bg-sky-950/30 ring-1 ring-sky-200 dark:ring-sky-800 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-sky-100 dark:bg-sky-900 flex items-center justify-center shrink-0">
                <x-heroicon-m-play class="w-4 h-4 text-sky-700 dark:text-sky-400" />
            </div>
            <div>
                <div class="text-sm font-bold text-sky-900 dark:text-sky-100">Siap Diaktifkan Tesnya</div>
                <div class="text-xs text-sky-700 dark:text-sky-400 mt-0.5">
                    <strong>{{ $this->getBelumDiaktifkanCount() }}</strong> kandidat lolos seleksi berkas belum diaktifkan tesnya.
                </div>
            </div>
        </div>
        {{ ($this->aktifkanTesAction) }}
    </div>
    @endif

    {{-- Table --}}
    {{ $this->table }}

</x-filament-panels::page>

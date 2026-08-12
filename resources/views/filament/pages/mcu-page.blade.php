<x-filament-panels::page>

    {{-- Filter: Perusahaan + Lowongan --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Perusahaan</label>
                <select wire:model.live="companyId"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">— Semua perusahaan (yang mewajibkan MCU) —</option>
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

    {{-- Banner: draft jadwal MCU belum diterapkan --}}
    @if($this->jobOpeningId && $this->getMcuDraft())
    @php $mcuDraft = $this->getMcuDraft(); @endphp
    <div class="fi-section rounded-xl bg-blue-50 dark:bg-blue-950/30 ring-1 ring-blue-200 dark:ring-blue-800 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center shrink-0">
                <x-heroicon-m-calendar-days class="w-4 h-4 text-blue-700 dark:text-blue-400" />
            </div>
            <div>
                <div class="text-sm font-bold text-blue-900 dark:text-blue-100">Draft Jadwal MCU Tersimpan</div>
                <div class="text-xs text-blue-700 dark:text-blue-400 mt-0.5">
                    {{ $mcuDraft->mcu_date?->translatedFormat('d F Y') }}
                    @if($mcuDraft->mcu_time) · {{ substr($mcuDraft->mcu_time, 0, 5) }} @endif
                    @if($mcuDraft->location) · {{ $mcuDraft->location }} @endif
                    — {{ $mcuDraft->invitees()->count() }} kandidat diundang. Status belum berubah.
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            {{ ($this->jadwalMcuAction) }}
            {{ ($this->publishMcuAction) }}
        </div>
    </div>
    @endif

    {{-- Banner: siap jadwalkan MCU --}}
    @if($this->jobOpeningId && $this->getReadyCount() > 0 && !$this->getMcuDraft())
    <div class="fi-section rounded-xl bg-sky-50 dark:bg-sky-950/30 ring-1 ring-sky-200 dark:ring-sky-800 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-sky-100 dark:bg-sky-900 flex items-center justify-center shrink-0">
                <x-heroicon-m-calendar-days class="w-4 h-4 text-sky-700 dark:text-sky-400" />
            </div>
            <div>
                <div class="text-sm font-bold text-sky-900 dark:text-sky-100">Siap Jadwalkan MCU</div>
                <div class="text-xs text-sky-700 dark:text-sky-400 mt-0.5">
                    <strong>{{ $this->getReadyCount() }}</strong> kandidat lulus wawancara. Tentukan jadwal MCU mereka.
                </div>
            </div>
        </div>
        {{ ($this->jadwalMcuAction) }}
    </div>
    @endif

    {{-- Table --}}
    {{ $this->table }}

</x-filament-panels::page>

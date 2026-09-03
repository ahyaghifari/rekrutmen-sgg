<x-filament-panels::page>

    {{-- Filter: Perusahaan + Lowongan --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <div class="grid grid-cols-1 @if($this->isSuperAdmin()) md:grid-cols-2 @endif gap-4">
            @if($this->isSuperAdmin())
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
            @endif
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

    {{-- Kuis lowongan --}}
    @if($this->jobOpeningId)
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">Kuis Lowongan Ini</div>
            <div class="mt-1.5 flex flex-wrap gap-1.5">
                @forelse($this->getSelectedJobOpening()?->quizzes ?? [] as $quiz)
                    <x-filament::badge color="success">{{ $quiz->name }}</x-filament::badge>
                @empty
                    <span class="text-xs text-gray-400">Belum ada kuis dipasang — tes belum bisa diaktifkan.</span>
                @endforelse
            </div>
            @if($this->isQuizLocked())
            <div class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                <x-heroicon-m-lock-closed class="w-3.5 h-3.5 inline -mt-0.5" />
                Terkunci — tes sudah diaktifkan untuk lowongan ini.
            </div>
            @endif
        </div>
        {{ ($this->pilihKuisAction) }}
    </div>
    @endif

    {{-- Banner: siap diaktifkan tesnya --}}
    @if($this->jobOpeningId && $this->hasQuizAssigned() && $this->getBelumDiaktifkanCount() > 0)
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

    {{-- Banner: ada yang lulus tes tapi masih ada yang belum diputuskan --}}
    @if($this->jobOpeningId && $this->getLulusTesCount() > 0 && $this->getBelumDiputuskanCount() > 0)
    <div class="fi-section rounded-xl bg-orange-50 dark:bg-orange-950/30 ring-1 ring-orange-200 dark:ring-orange-800 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-orange-100 dark:bg-orange-900 flex items-center justify-center shrink-0">
                <x-heroicon-m-x-circle class="w-4 h-4 text-orange-700 dark:text-orange-400" />
            </div>
            <div>
                <div class="text-sm font-bold text-orange-900 dark:text-orange-100">Ada Kandidat Belum Diputuskan</div>
                <div class="text-xs text-orange-700 dark:text-orange-400 mt-0.5">
                    <strong>{{ $this->getBelumDiputuskanCount() }}</strong> kandidat sudah mengerjakan tes tapi belum ada keputusan. Tandai yang tidak lolos dan beritahu mereka.
                </div>
            </div>
        </div>
        {{ ($this->tandaiTidakLulusTesAction) }}
    </div>
    @endif

    {{-- Banner: ada draft notif penolakan tes yang belum dikirim --}}
    @if($this->jobOpeningId)
    @php $pendingTesDraft = \App\Models\ApplicationNotification::whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))->where('status', 'draft')->where('event', \App\Models\CompanyMessageTemplate::EVENT_QUIZ_REJECTED)->count(); @endphp
    @if($pendingTesDraft > 0)
    <div class="fi-section rounded-xl bg-red-50 dark:bg-red-950/30 ring-1 ring-red-200 dark:ring-red-800 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center shrink-0">
                <x-heroicon-m-envelope class="w-4 h-4 text-red-700 dark:text-red-400" />
            </div>
            <div>
                <div class="text-sm font-bold text-red-900 dark:text-red-100">Email Tidak Dilanjutkan Tes Ditahan</div>
                <div class="text-xs text-red-700 dark:text-red-400 mt-0.5">
                    <strong>{{ $pendingTesDraft }}</strong> kandidat belum menerima pemberitahuan tidak lolos tes. Kirim kapan saja.
                </div>
            </div>
        </div>
        {{ ($this->kirimNotifPenolakanTesAction) }}
    </div>
    @endif
    @endif

    {{-- Table --}}
    {{ $this->table }}

</x-filament-panels::page>

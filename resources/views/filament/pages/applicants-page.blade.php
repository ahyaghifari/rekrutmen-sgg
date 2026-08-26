<x-filament-panels::page>

    {{-- Filter: Perusahaan + Lowongan --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <div class="grid grid-cols-1 @if($this->isSuperAdmin()) md:grid-cols-2 @endif gap-4">
            @if($this->isSuperAdmin())
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Perusahaan</label>
                <select
                    wire:model.live="companyId"
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
                <select
                    wire:model.live="jobOpeningId"
                    @if(!$this->companyId) disabled @endif
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <option value="">— Pilih lowongan —</option>
                    @foreach($this->getJobOpenings() as $opening)
                        <option value="{{ $opening->id }}">{{ $opening->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    @if($this->jobOpeningId)
    @php $stats = $this->getStats(); @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach($stats as $stat)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 flex flex-col gap-1">
            <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</div>
            <div class="text-3xl font-extrabold" style="color:{{ $stat['color'] }}">{{ $stat['value'] }}</div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Bulk: Seleksi Berkas (screening → shortlisted/rejected) --}}
    @if($this->jobOpeningId && in_array($this->statusFilter, ['', 'screening']) && $this->getScreeningCount() > 0)
    @php $screeningCount = $this->getScreeningCount(); @endphp
    <div class="fi-section rounded-xl bg-yellow-50 dark:bg-yellow-950/30 ring-1 ring-yellow-200 dark:ring-yellow-800 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-yellow-100 dark:bg-yellow-900 flex items-center justify-center shrink-0">
                <x-heroicon-m-clipboard-document-check class="w-4 h-4 text-yellow-700 dark:text-yellow-400" />
            </div>
            <div>
                <div class="text-sm font-bold text-yellow-900 dark:text-yellow-100">Siap Seleksi Berkas</div>
                <div class="text-xs text-yellow-700 dark:text-yellow-400 mt-0.5">
                    Ada <strong>{{ $screeningCount }}</strong> pelamar dalam tahap peninjauan. Pilih siapa yang lolos.
                </div>
            </div>
        </div>
        {{ ($this->seleksiBerkasAction) }}
    </div>
    @endif

    {{-- Info: lolos berkas lanjut ke halaman Kandidat --}}
    @if($this->jobOpeningId && in_array($this->statusFilter, ['', 'shortlisted']))
    @php $shortlistedCount = \App\Models\JobApplication::where('job_opening_id', $this->jobOpeningId)->where('status', 'shortlisted')->count(); @endphp
    @if($shortlistedCount > 0)
    <div class="fi-section rounded-xl bg-gray-50 dark:bg-gray-800/50 ring-1 ring-gray-200 dark:ring-gray-700 px-6 py-3 text-xs text-gray-600 dark:text-gray-400">
        <strong>{{ $shortlistedCount }}</strong> pelamar sudah lolos seleksi berkas dan resmi jadi kandidat — lanjutkan prosesnya di grup sidebar <strong>Kandidat</strong> (Tes, Wawancara, MCU, TTD & Onboarding).
    </div>
    @endif
    @endif

    {{-- Banner: ada draft keputusan seleksi belum diterapkan --}}
    @if($this->jobOpeningId && $this->getDraftDecisionCount() > 0)
    @php $draftCount = $this->getDraftDecisionCount(); @endphp
    <div class="fi-section rounded-xl bg-emerald-50 dark:bg-emerald-950/30 ring-1 ring-emerald-200 dark:ring-emerald-800 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-emerald-100 dark:bg-emerald-900 flex items-center justify-center shrink-0">
                <x-heroicon-m-check-circle class="w-4 h-4 text-emerald-700 dark:text-emerald-400" />
            </div>
            <div>
                <div class="text-sm font-bold text-emerald-900 dark:text-emerald-100">Draft Keputusan Seleksi Berkas Tersimpan</div>
                <div class="text-xs text-emerald-700 dark:text-emerald-400 mt-0.5">
                    <strong>{{ $draftCount }}</strong> keputusan belum diterapkan — pelamar belum melihat perubahan.
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            {{ ($this->seleksiBerkasAction) }}
            {{ ($this->publishScreeningAction) }}
        </div>
    </div>
    @endif

    {{-- Banner: ada shortlisted + masih ada yang screening (belum diputuskan tidak lolos) --}}
    @if($this->jobOpeningId)
    @php
        $shortlistedCount2 = \App\Models\JobApplication::where('job_opening_id', $this->jobOpeningId)->where('status', 'shortlisted')->count();
        $screeningRemaining = \App\Models\JobApplication::where('job_opening_id', $this->jobOpeningId)->where('status', 'screening')->count();
    @endphp
    @if($shortlistedCount2 > 0 && $screeningRemaining > 0)
    <div class="fi-section rounded-xl bg-orange-50 dark:bg-orange-950/30 ring-1 ring-orange-200 dark:ring-orange-800 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-orange-100 dark:bg-orange-900 flex items-center justify-center shrink-0">
                <x-heroicon-m-x-circle class="w-4 h-4 text-orange-700 dark:text-orange-400" />
            </div>
            <div>
                <div class="text-sm font-bold text-orange-900 dark:text-orange-100">Ada Pelamar yang Belum Diputuskan</div>
                <div class="text-xs text-orange-700 dark:text-orange-400 mt-0.5">
                    <strong>{{ $screeningRemaining }}</strong> pelamar masih di Peninjauan Berkas sementara yang lain sudah lolos. Pilih siapa yang tidak lolos dan beritahu mereka.
                </div>
            </div>
        </div>
        {{ ($this->tandaiTidakLolosBerkasAction) }}
    </div>
    @endif
    @endif

    {{-- Banner: ada draft notif penolakan berkas yang belum dikirim --}}
    @if($this->jobOpeningId)
    @php $pendingRejectionCount = \App\Models\ApplicationNotification::whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))->where('status', 'draft')->where('event', \App\Models\CompanyMessageTemplate::EVENT_SCREENING_REJECTED)->count(); @endphp
    @if($pendingRejectionCount > 0)
    <div class="fi-section rounded-xl bg-red-50 dark:bg-red-950/30 ring-1 ring-red-200 dark:ring-red-800 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center shrink-0">
                <x-heroicon-m-envelope class="w-4 h-4 text-red-700 dark:text-red-400" />
            </div>
            <div>
                <div class="text-sm font-bold text-red-900 dark:text-red-100">Email Penolakan Berkas Ditahan</div>
                <div class="text-xs text-red-700 dark:text-red-400 mt-0.5">
                    <strong>{{ $pendingRejectionCount }}</strong> pelamar belum menerima pemberitahuan penolakan. Kirim kapan saja kamu siap.
                </div>
            </div>
        </div>
        {{ ($this->kirimNotifPenolakanBerkasAction) }}
    </div>
    @endif
    @endif

    {{-- Bulk: pindahkan semua Lamaran Masuk ke Screening --}}
    @if($this->jobOpeningId && in_array($this->statusFilter, ['', 'applied']) && $this->getAppliedCount() > 0)
    @php $appliedCount = $this->getAppliedCount(); @endphp
    <div class="fi-section rounded-xl bg-blue-50 dark:bg-blue-950/30 ring-1 ring-blue-200 dark:ring-blue-800 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center shrink-0">
                <x-heroicon-m-inbox class="w-4 h-4 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <div class="text-sm font-bold text-blue-900 dark:text-blue-100">Lamaran Masuk Belum Ditinjau</div>
                <div class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">
                    Ada <strong>{{ $appliedCount }}</strong> lamaran yang belum masuk tahap peninjauan berkas.
                </div>
            </div>
        </div>
        {{ ($this->bulkMoveToScreeningAction) }}
    </div>
    @endif

    {{-- Filter Status + Export --}}
    @if($this->jobOpeningId)
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 px-6 py-4 flex items-center gap-4">
        <label class="text-sm font-semibold text-gray-700 dark:text-gray-200 shrink-0">Filter Status</label>
        <select
            wire:model.live="statusFilter"
            class="w-full max-w-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">— Semua status —</option>
            <option value="applied">Lamaran Masuk</option>
            <option value="screening">Peninjauan Berkas</option>
            <option value="shortlisted">Lolos Seleksi Berkas</option>
            <option value="rejected">Tidak Dilanjutkan</option>
        </select>
        <a href="{{ route('admin.export.applications', array_filter(['job_opening_id' => $this->jobOpeningId, 'status' => $this->statusFilter ?: null])) }}"
           target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 hover:bg-green-700 px-4 py-2 text-sm font-semibold text-white transition shrink-0">
            <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
            Export Excel
        </a>
    </div>
    @endif

    {{-- Table --}}
    {{ $this->table }}

</x-filament-panels::page>

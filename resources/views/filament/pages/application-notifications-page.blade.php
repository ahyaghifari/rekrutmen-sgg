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
                <select wire:model.live="jobOpeningId"
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

    @if($this->jobOpeningId)
    @php $drafts = $this->getDrafts(); $sentCount = $this->getSentCount(); @endphp

    {{-- Stats row --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 flex flex-col gap-1">
            <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Draft Belum Dikirim</div>
            <div class="text-3xl font-extrabold text-amber-500">{{ $drafts->count() }}</div>
        </div>
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 flex flex-col gap-1">
            <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Sudah Terkirim</div>
            <div class="text-3xl font-extrabold text-green-600">{{ $sentCount }}</div>
        </div>
    </div>

    {{-- Bulk send banner --}}
    @if($drafts->count() > 0)
    <div class="fi-section rounded-xl bg-amber-50 dark:bg-amber-950/30 ring-1 ring-amber-200 dark:ring-amber-800 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-amber-100 dark:bg-amber-900 flex items-center justify-center shrink-0">
                <x-heroicon-m-paper-airplane class="w-4 h-4 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
                <div class="text-sm font-bold text-amber-900 dark:text-amber-100">{{ $drafts->count() }} email belum dikirim</div>
                <div class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">Tinjau tiap draft di bawah atau kirim semua sekaligus.</div>
            </div>
        </div>
        {{ ($this->sendAllAction) }}
    </div>
    @endif

    {{-- Draft list --}}
    @if($drafts->isEmpty())
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-10 text-center text-gray-400 dark:text-gray-500">
        <x-heroicon-o-envelope-open class="w-10 h-10 mx-auto mb-2 opacity-40" />
        <div class="text-sm">Tidak ada draft email untuk lowongan ini.</div>
    </div>
    @else
    <div class="flex flex-col gap-3">
        @foreach($drafts as $notif)
        @php
            $name = $notif->jobApplication->answers->firstWhere('field_key', 'full_name')?->value ?? '—';
            $eventLabel = match($notif->event) {
                'screening_passed'   => 'Lolos Seleksi Berkas',
                'screening_rejected' => 'Tidak Dilanjutkan',
                default              => $notif->event,
            };
            $eventColor = $notif->event === 'screening_passed' ? 'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/40' : 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/40';
        @endphp
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $name }}</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $eventColor }}">{{ $eventLabel }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1"><strong>Subjek:</strong> {{ $notif->subject }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line bg-gray-50 dark:bg-gray-800 rounded-lg p-3 mt-2 max-h-32 overflow-y-auto text-xs">{{ $notif->body }}</div>
                </div>
                <button wire:click="send('{{ $notif->id }}')" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 disabled:opacity-60 px-4 py-2 text-sm font-semibold text-white transition shrink-0">
                    <x-heroicon-m-paper-airplane class="w-4 h-4" />
                    Kirim
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @else
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-10 text-center text-gray-400 dark:text-gray-500">
        <x-heroicon-o-envelope class="w-10 h-10 mx-auto mb-2 opacity-40" />
        <div class="text-sm">Pilih perusahaan dan lowongan untuk melihat draft notifikasi.</div>
    </div>
    @endif

</x-filament-panels::page>

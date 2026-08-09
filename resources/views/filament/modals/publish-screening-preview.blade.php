<div class="space-y-4 text-sm">
    @if($passed->count() > 0)
    <div>
        <div class="text-xs font-bold uppercase tracking-wide text-green-700 dark:text-green-400 mb-2">
            Lolos Seleksi Berkas ({{ $passed->count() }})
        </div>
        <ul class="divide-y divide-gray-100 dark:divide-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden max-h-48 overflow-y-auto">
            @foreach($passed as $d)
            <li class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-900">
                <x-heroicon-m-check-circle class="w-4 h-4 text-green-500 shrink-0" />
                <span class="text-gray-800 dark:text-gray-200">{{ $d->jobApplication->answers->firstWhere('field_key','full_name')?->value ?? '—' }}</span>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($rejectedCount > 0)
    <div class="flex items-center gap-2 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 px-4 py-3">
        <x-heroicon-m-x-circle class="w-4 h-4 text-red-500 shrink-0" />
        <span class="text-red-800 dark:text-red-300 font-medium">{{ $rejectedCount }} pelamar tidak dilanjutkan</span>
    </div>
    @endif

    <p class="text-xs text-gray-500 dark:text-gray-400">
        Setelah diterapkan, status lamaran langsung berubah dan draft email notifikasi akan dibuat. Tindakan ini tidak dapat dibatalkan.
    </p>
</div>

<x-filament-panels::page>

    {{-- Job Opening Selector --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
            Pilih Lowongan
        </label>
        <select
            wire:model.live="jobOpeningId"
            class="w-full max-w-lg rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">— Pilih lowongan —</option>
            @foreach($this->getJobOpenings() as $opening)
                <option value="{{ $opening->id }}">
                    {{ $opening->title }} — {{ $opening->company?->name ?? $opening->company_name }}
                </option>
            @endforeach
        </select>
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

    {{-- Table --}}
    {{ $this->table }}

</x-filament-panels::page>

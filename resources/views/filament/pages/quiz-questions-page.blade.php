<x-filament-panels::page>

    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Kuis</label>
        <select wire:model.live="quizId"
            class="w-full max-w-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">— Pilih kuis —</option>
            @foreach($this->getQuizzes() as $quiz)
                <option value="{{ $quiz->id }}">{{ $quiz->name }}</option>
            @endforeach
        </select>
    </div>

    {{ $this->table }}

</x-filament-panels::page>

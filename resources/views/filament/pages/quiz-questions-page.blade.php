<x-filament-panels::page>

    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex-1 min-w-[240px]"
                x-data="{
                    quizzes: @js($this->getQuizzes()->map(fn ($q) => ['id' => $q->id, 'name' => $q->name])),
                    query: @js(optional($this->getQuizzes()->firstWhere('id', $quizId))->name),
                    pick() {
                        const match = this.quizzes.find(q => q.name === this.query);
                        $wire.set('quizId', match ? match.id : '');
                    }
                }">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Kuis</label>
                <input type="text" list="quiz-options" x-model="query" @input="pick()" @change="pick()"
                    placeholder="Cari kuis..."
                    class="w-full max-w-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
                <datalist id="quiz-options">
                    <template x-for="q in quizzes" :key="q.id">
                        <option :value="q.name"></option>
                    </template>
                </datalist>
            </div>

            @if($quizId)
            <div class="flex rounded-lg border border-gray-300 dark:border-gray-700 overflow-hidden shrink-0">
                <button type="button" wire:click="switchMode('table')"
                    class="px-4 py-2 text-sm font-semibold transition {{ $mode === 'table' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">
                    <x-heroicon-m-bars-3 class="w-4 h-4 inline -mt-0.5 mr-1" /> Urutkan
                </button>
                <button type="button" wire:click="switchMode('edit')"
                    class="px-4 py-2 text-sm font-semibold transition border-l border-gray-300 dark:border-gray-700 {{ $mode === 'edit' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">
                    <x-heroicon-m-pencil-square class="w-4 h-4 inline -mt-0.5 mr-1" /> Kelola Soal
                </button>
            </div>
            @endif
        </div>
    </div>

    @if($quizId && $mode === 'edit')
        <form wire:submit="save">
            {{ $this->form }}

            <div class="flex justify-end mt-4">
                <x-filament::button type="submit">
                    Simpan Semua Soal
                </x-filament::button>
            </div>
        </form>
    @else
        {{ $this->table }}
    @endif

</x-filament-panels::page>

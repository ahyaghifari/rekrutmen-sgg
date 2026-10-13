@props(['hospitals', 'when'])
{{-- Pilih rumah sakit: kartu radio dengan logo (kalau ada). $when = ekspresi Alpine kapan grup ini aktif. --}}
<div x-data="{ pick: '' }" class="grid gap-4 sm:grid-cols-2">
  @foreach ($hospitals as $c)
    <label class="flex items-center gap-4 rounded-xl border-2 p-4 cursor-pointer transition-colors"
           :class="pick === '{{ $c->id }}' ? 'border-teal-600 bg-teal-50' : 'border-slate-300 hover:border-teal-400'">
      <input type="radio" name="company_id" value="{{ $c->id }}" x-model="pick" :disabled="!({{ $when }})" required class="sr-only">
      @if ($c->logo_url)
        <img src="{{ $c->logo_url }}" alt="" class="h-12 w-12 shrink-0 rounded-lg object-contain bg-white">
      @else
        <span class="h-12 w-12 shrink-0 rounded-lg bg-teal-100 text-teal-700 grid place-items-center text-xl" aria-hidden="true"><i class="fa-solid fa-hospital"></i></span>
      @endif
      <span class="flex-1 text-sm font-bold text-slate-800">{{ $c->name }}</span>
      <i class="fa-solid fa-circle-check text-teal-600" x-show="pick === '{{ $c->id }}'" x-cloak aria-hidden="true"></i>
    </label>
  @endforeach
</div>

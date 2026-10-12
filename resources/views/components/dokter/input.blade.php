@props(['name', 'label', 'type' => 'text'])
<div>
  <label for="{{ $name }}" class="block mb-2 text-sm font-semibold text-slate-700">{{ $label }} <span class="text-red-600">*</span></label>
  <input id="{{ $name }}" type="{{ $type }}" name="{{ $name }}" @if($type !== 'password') value="{{ old($name) }}" @endif required
         {{ $attributes->merge(['class' => 'w-full rounded-xl border-2 px-4 py-3 text-base outline-none focus:border-teal-600 focus:ring-0 '.($errors->has($name) ? 'border-red-600' : 'border-slate-300')]) }} />
  @error($name)<p class="mt-1.5 text-[12.5px] text-red-600">{{ $message }}</p>@enderror
</div>

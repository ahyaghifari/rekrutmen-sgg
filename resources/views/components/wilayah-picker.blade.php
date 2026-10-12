@props(['prefix', 'values' => [], 'count' => 4, 'accent' => 'navy', 'big' => false])
@php
    $all   = [['province', 'Provinsi'], ['city', 'Kota/Kabupaten'], ['district', 'Kecamatan'], ['village', 'Kelurahan/Desa']];
    $inp   = $big ? 'rounded-xl border-2 py-3.5 pl-4 pr-9 text-base' : 'rounded-[10px] border py-2 pl-3 pr-8 text-[13px]';
    $lab   = $big ? 'mb-2 text-sm font-semibold' : 'mb-1 text-[12.5px] font-bold';
    $focus = $accent === 'teal' ? 'focus:border-teal-600' : 'focus:border-navy';
    $hit   = $accent === 'teal' ? 'bg-teal-600 text-white' : 'bg-navy text-white';
    $init  = collect($all)->mapWithKeys(fn ($l) => [$l[0] => old($prefix.$l[0], $values[$l[0]] ?? '')])->all();
@endphp
{{-- Pilihan wilayah dari wilayah.id (lewat proxy route('wilayah')); gagal diraih -> input teks biasa. --}}
<div {{ $attributes->merge(['class' => 'grid grid-cols-1 sm:grid-cols-2 '.($big ? '' : 'gap-4')]) }} x-data="wilayah(@js($init), @js(url('/wilayah')), {{ $count }})">
  @foreach (array_slice($all, 0, $count) as $i => [$key, $label])
  <div class="relative" @click.outside="if (openKey === '{{ $key }}') { openKey = null; pick({{ $i }}) }">
    <label class="block {{ $lab }} text-slate-700">{{ $label }}</label>
    <div class="relative">
      <input type="text" name="{{ $prefix.$key }}" x-model="names['{{ $key }}']" required maxlength="100" autocomplete="off"
             :disabled="!fallback && !opts['{{ $key }}'].length"
             :placeholder="fallback ? '' : (opts['{{ $key }}'].length ? 'Ketik untuk mencari...' : '{{ $i === 0 ? 'Memuat...' : 'Pilih ' . $all[$i - 1][1] . ' dulu' }}')"
             @focus="show('{{ $key }}')"
             @input="show('{{ $key }}')"
             @keydown.arrow-down.prevent="move('{{ $key }}', 1)"
             @keydown.arrow-up.prevent="move('{{ $key }}', -1)"
             @keydown.enter="if (openKey === '{{ $key }}') { $event.preventDefault(); choose({{ $i }}, filtered('{{ $key }}')[hi]) }"
             @keydown.escape="openKey = null; pick({{ $i }})"
             @keydown.tab="openKey = null; pick({{ $i }})"
             class="w-full {{ $inp }} border-slate-300 {{ $focus }} focus:outline-none disabled:bg-slate-50 disabled:text-slate-400">
      <i x-show="!fallback" class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-[11px] text-slate-400 pointer-events-none"></i>
    </div>
    {{-- Inline (di alur halaman, mendorong field di bawahnya): tidak terpotong overflow kartu dan tidak bergantung koordinat/keyboard mobile. --}}
    <ul x-show="openKey === '{{ $key }}'" x-cloak style="display:none;"
        class="mt-1 max-h-48 overflow-y-auto rounded-[10px] border border-slate-200 bg-white py-1 shadow-lg">
      <template x-for="(o, idx) in filtered('{{ $key }}')" :key="o.code">
        <li @mousedown.prevent="choose({{ $i }}, o)" @mouseenter="hi = idx"
            :class="idx === hi ? '{{ $hit }}' : 'text-slate-700'"
            class="cursor-pointer px-3 py-1.5 text-[12.5px]" x-text="o.name"></li>
      </template>
      <li x-show="!filtered('{{ $key }}').length" class="px-3 py-2 text-[12px] text-slate-400">Tidak ditemukan</li>
    </ul>
  </div>
  @endforeach
</div>
@once
<script>
  document.addEventListener('alpine:init', () => {
    Alpine.data('wilayah', (names, base, n = 4) => ({
      fallback: false,
      names,
      codes: { province: '', city: '', district: '', village: '' },
      opts: { province: [], city: [], district: [], village: [] },
      levels: ['province', 'city', 'district', 'village'],
      async load(level, parent) {
        try {
          const r = await fetch(`${base}/${parent}`, { headers: { Accept: 'application/json' } });
          if (!r.ok) throw new Error();
          this.opts[level] = (await r.json()).data;
        } catch (e) { this.fallback = true; }
      },
      async init() {
        await this.load('province', '');
        for (let i = 0; i < n && !this.fallback; i++) {
          const l = this.levels[i];
          const hit = this.opts[l].find(o => o.name === this.names[l]);
          if (!hit) break;
          this.codes[l] = hit.code;
          if (i < n - 1) await this.load(this.levels[i + 1], hit.code);
        }
      },
      openKey: null,
      hi: 0,
      show(l) {
        if (this.fallback) return;
        this.openKey = l; this.hi = 0;
      },
      filtered(l) {
        const q = (this.names[l] || '').trim().toLowerCase();
        return this.opts[l].filter(o => o.name.toLowerCase().includes(q)).slice(0, 200);
      },
      move(l, d) {
        const k = this.filtered(l).length;
        this.openKey = l;
        if (k) this.hi = (this.hi + d + k) % k;
      },
      choose(i, o) {
        this.openKey = null;
        if (o) this.settle(i, o);
      },
      // Ketikan harus cocok persis dengan salah satu nama wilayah; kalau tidak, dikosongkan.
      pick(i) {
        if (this.fallback) return;
        const l = this.levels[i];
        const q = (this.names[l] || '').trim().toLowerCase();
        this.settle(i, this.opts[l].find(o => o.name.toLowerCase() === q));
      },
      async settle(i, hit) {
        const l = this.levels[i];
        if (hit && hit.code === this.codes[l]) { this.names[l] = hit.name; return; }
        this.codes[l] = hit?.code ?? '';
        this.names[l] = hit?.name ?? '';
        for (let j = i + 1; j < n; j++) {
          const k = this.levels[j];
          this.codes[k] = ''; this.names[k] = ''; this.opts[k] = [];
        }
        if (hit && i < n - 1) await this.load(this.levels[i + 1], hit.code);
      },
    }));
  });
</script>
@endonce

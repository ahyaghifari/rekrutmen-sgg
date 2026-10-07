<x-public-layout>
<x-slot name="title">Formulir Lamaran — {{ $jobOpening->title }}</x-slot>

<div class="sg-rise min-h-screen">
  {{-- Top gradient bar --}}
  <div class="h-1" style="background:linear-gradient(90deg,{{ $brand }} 0%,{{ $brandDark }} 100%);" aria-hidden="true"></div>

  {{-- Navbar --}}
  <header class="sg-navbar-header border-b">
    <div class="max-w-[1000px] mx-auto px-6 py-[14px] flex items-center gap-4">
      <div class="flex items-center gap-3">
        <img src="/logo-sgg.png" alt="Syifa Global Group" class="h-9 w-auto" width="36" height="36" />
        <div class="text-[11px] font-semibold tracking-[0.06em] uppercase text-slate-400 sg-desk">Portal Rekrutmen</div>
      </div>
      <nav class="sg-desk-flex gap-1.5 ml-auto items-center">
        <a href="{{ route('welcome') }}" class="px-[14px] py-2 rounded-[10px] text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors duration-150">Beranda</a>
        <a href="{{ route('welcome') }}#lowongan" class="px-[14px] py-2 rounded-[10px] text-sm font-semibold" style="background:rgba({{ $r }},{{ $g }},{{ $b }},0.09);color:{{ $brand }};">Lowongan</a>
        @auth
          <a href="{{ route('dashboard') }}" class="ml-2 px-5 py-[10px] rounded-[11px] text-white text-sm font-bold no-underline transition-opacity duration-150 hover:opacity-90" style="background:{{ $brand }};">Dashboard</a>
        @endauth
      </nav>
      <div class="sg-mob ml-auto" style="display:flex;align-items:center;gap:8px;">
        @auth
          <a href="{{ route('dashboard') }}" class="px-4 py-[9px] rounded-[11px] text-white text-[13.5px] font-bold no-underline" style="background:{{ $brand }};">Dashboard</a>
        @endauth
      </div>
    </div>
  </header>

  {{-- Main --}}
  <main class="lg:w-11/12 mx-auto px-5 pt-7 pb-24">

    {{-- Position card --}}
    <div class="relative overflow-hidden rounded-[20px] text-white px-[30px] py-7" style="background:linear-gradient(135deg,{{ $brand }} 0%,{{ $brandDark }} 100%);">
      <div class="absolute w-[230px] h-[230px] rounded-full -right-[70px] -top-[110px] pointer-events-none bg-white/[0.07]"></div>
      <div class="absolute w-[130px] h-[130px] rounded-[30px] rotate-[22deg] right-[170px] -bottom-[80px] pointer-events-none bg-white/[0.08]"></div>
      <div class="relative z-[1] flex flex-wrap gap-[18px] items-center justify-between">
        <div class="flex items-center gap-4">
          @if($co?->logo_path)
          <div class="w-[52px] h-[52px] shrink-0 rounded-[14px] grid place-items-center overflow-hidden bg-white/[0.18]">
            <img src="{{ asset('storage/'.$co->logo_path) }}" alt="{{ $co->name }}" class="w-full h-full object-contain p-[8px]">
          </div>
          @endif
          <div>
            <div class="text-[11.5px] font-bold tracking-[0.08em] uppercase opacity-[0.82]">Melamar untuk posisi</div>
            <h1 class="mt-1 mb-1 text-2xl font-extrabold tracking-[-0.5px]">{{ $jobOpening->title }}</h1>
            <div class="text-[13.5px] text-white/[0.82]">{{ $co?->name ?? $jobOpening->company_name }} · Kuota: {{ $jobOpening->quota }} orang</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Flash errors --}}
    @if($errors->any())
    <div class="mt-4 rounded-[14px] px-5 py-4 border border-red-200 bg-red-600/[0.07]">
      <div class="text-[13.5px] font-bold text-red-600 mb-2"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i>Perbaiki isian berikut:</div>
      <ul class="m-0 pl-[18px] text-[13px] text-red-600 flex flex-col gap-1">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
      </ul>
    </div>
    @endif

    {{-- Ringkasan error validasi client-side, diisi JS saat submit --}}
    <div id="client-error-banner" style="display:none;" class="mt-4 rounded-[14px] px-5 py-4 border border-red-200 bg-red-600/[0.07]">
      <div class="text-[13.5px] font-bold text-red-600 mb-2"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i>Lengkapi dulu isian berikut sebelum mengirim:</div>
      <ul id="client-error-list" class="m-0 pl-[18px] text-[13px] text-red-600 flex flex-col gap-1"></ul>
    </div>

    {{-- Banner: gunakan data lamaran sebelumnya --}}
    @if(isset($previousApp) && $previousApp)
    <div class="mt-4 rounded-[14px] px-5 py-4 border flex flex-wrap items-center gap-3" style="background:rgba(4,44,108,0.04);border-color:rgba(4,44,108,0.18);">
      <div class="flex items-center gap-3 flex-1 min-w-0">
        <div class="w-9 h-9 shrink-0 rounded-[10px] grid place-items-center" style="background:rgba(4,44,108,0.1);">
          <i class="fa-solid fa-rotate-left text-navy" style="font-size:14px;"></i>
        </div>
        <div>
          <div class="text-[13.5px] font-bold text-slate-800">Pernah melamar sebelumnya?</div>
          <div class="text-[12.5px] text-slate-500 mt-0.5">Data dari lamaran <strong>{{ $previousApp->jobOpening->title ?? '' }}</strong> dapat digunakan kembali.</div>
        </div>
      </div>
      <button type="button" onclick="fillFromPrevious()" class="shrink-0 px-4 py-2 rounded-[9px] text-[13px] font-bold text-white transition-colors duration-150" style="background:#042c6c;">
        <i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i>Gunakan Data Sebelumnya
      </button>
    </div>
    @endif

    <script>
    function fillFromPrevious() {
      const d = @json($previousApp?->answersMap() ?? null);
      if (!d) return;
      Object.entries(d).forEach(([key, val]) => {
        if (val === null || val === undefined || val === '') return;
        const name = 'fields[' + key + ']';
        const el = document.querySelector('[name="' + name + '"]:not([type="radio"]):not([type="checkbox"])');
        if (el) { el.value = val; return; }
        const radio = document.querySelector('[name="' + name + '"][value="' + val + '"]');
        if (radio) radio.checked = true;
      });
    }
    </script>

    @php
    $sectionColors = ['#042c6c','#0284c7','#b45309','#047C54','#B9308C','#7c3aed','#0f6e3d'];
    @endphp

    <form id="lamaran-form" method="POST" action="{{ route('lamaran.store', $jobOpening) }}" enctype="multipart/form-data">
      @csrf

      {{-- Section 1 tetap (Data Utama), selalu paling atas; section master mulai dari nomor 2. --}}
      @php $sec0 = $sectionColors[0]; @endphp
      <section class="sg-card mt-4 rounded-[20px] py-[26px] px-7" style="border-top: 3px solid {{ $sec0 }};">
        <div class="flex gap-3 items-center mb-5">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold"
               style="color:{{ $sec0 }};background:{{ $sec0 }}1a;">1</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800 tracking-[-0.3px]">Data Utama</h2>
        </div>
        <div class="sg-fields">
          @foreach([
            ['full_name', 'Nama Lengkap', 'text'],
            ['birth_place', 'Tempat Lahir', 'text'],
            ['birth_date', 'Tanggal Lahir', 'date'],
            ['gender', 'Jenis Kelamin', 'select'],
            ['education_level', 'Jenjang Pendidikan', 'select'],
            ['domicile', 'Domisili', 'text'],
          ] as [$n, $lbl, $t])
          <div @if(in_array($n, ['full_name', 'domicile'])) class="col-span-full" @endif>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">{{ $lbl }} <span class="text-red-700">*</span></label>
            @if($t === 'select')
              <select name="fields[{{ $n }}]" required class="form-field {{ $errors->has("fields.$n") ? 'border-red-600' : '' }}">
                <option value="">Pilih...</option>
                @foreach($n === 'gender' ? ['Laki-laki', 'Perempuan'] : ['SMA/SMK', 'D-III', 'D-IV', 'S-I', 'S-II', 'Profesi'] as $c)
                  <option value="{{ $c }}" {{ old("fields.$n") === $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
              </select>
            @else
              <input type="{{ $t }}" name="fields[{{ $n }}]" value="{{ old("fields.$n") }}" required
                class="form-field {{ $errors->has("fields.$n") ? 'border-red-600' : '' }}" />
            @endif
            @error("fields.$n")<p class="mt-1.5 text-[12.5px] text-red-600">{{ $message }}</p>@enderror
          </div>
          @endforeach
        </div>
      </section>

      @foreach($co->formSections as $sectionIndex => $formSection)
      @php
        $secColor = $sectionColors[($sectionIndex + 1) % count($sectionColors)];
        $secNum   = $sectionIndex + 2;
      @endphp
      <section class="sg-card mt-4 rounded-[20px] py-[26px] px-7" style="border-top: 3px solid {{ $secColor }};">
        <div class="flex gap-3 items-center mb-5">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold"
               style="color:{{ $secColor }};background:{{ $secColor }}1a;">{{ $secNum }}</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800 tracking-[-0.3px]">{{ $formSection->title }}</h2>
        </div>

        <div class="sg-fields">
          @foreach($formSection->fields as $field)
          @continue(in_array($field->name, ['full_name', 'birth_place', 'birth_date', 'gender', 'domicile', 'education_level']))
          @php
            $isWide   = in_array($field->type, ['textarea']);
            $opts      = $field->options ?? [];
            $isFile    = $field->type === 'file';
            $inputName = $isFile ? "files[{$field->name}]" : "fields[{$field->name}]";
            $errKey    = $isFile ? "files.{$field->name}" : "fields.{$field->name}";
            $hasErr    = $errors->has($errKey);
            $oldVal    = old("fields.{$field->name}");
          @endphp

          <div @if($isWide || $field->type === 'radio') class="col-span-full" @endif>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">
              {{ $field->label }}@if($field->is_required) <span class="text-red-700">*</span>@endif
            </label>

            @if($field->type === 'textarea')
              <textarea name="{{ $inputName }}" rows="3"
                {{ $field->is_required ? 'required' : '' }}
                class="form-field resize-y {{ $hasErr ? 'border-red-600' : '' }}">{{ $oldVal }}</textarea>

            @elseif($field->type === 'select')
              <select name="{{ $inputName }}" {{ $field->is_required ? 'required' : '' }}
                class="form-field {{ $hasErr ? 'border-red-600' : '' }}">
                <option value="">Pilih...</option>
                @foreach($opts['choices'] ?? [] as $choice)
                  <option value="{{ $choice }}" {{ $oldVal === $choice ? 'selected' : '' }}>{{ $choice }}</option>
                @endforeach
              </select>

            @elseif($field->type === 'radio')
              <div class="flex gap-[10px] flex-wrap" x-data="{ val: '{{ $oldVal }}' }">
                @foreach($opts['choices'] ?? [] as $choice)
                <label class="sg-radio-opt flex items-center gap-[10px] border rounded-xl py-3 px-4 text-[13.5px] font-semibold cursor-pointer transition-all duration-150 select-none min-w-[120px]"
                  :class="val === @js($choice) ? 'sg-radio-checked' : ''"
                  :style="val === @js($choice) ? 'border-color:{{ $brand }};background:{{ $brand }}14;color:{{ $brand }};' : ''">
                  <span class="sg-radio-ring w-[18px] h-[18px] rounded-full border-2 flex items-center justify-center shrink-0 transition-colors duration-150"
                    :style="val === @js($choice) ? 'border-color:{{ $brand }}' : ''">
                    <span class="w-[8px] h-[8px] rounded-full transition-all duration-150"
                      :style="val === @js($choice) ? 'background:{{ $brand }};transform:scale(1)' : 'background:transparent;transform:scale(0)'"></span>
                  </span>
                  <input type="radio" name="{{ $inputName }}" value="{{ $choice }}"
                    x-model="val"
                    {{ $field->is_required ? 'required' : '' }}
                    class="sr-only" />
                  {{ $choice }}
                </label>
                @endforeach
              </div>

            @elseif($field->type === 'file')
              @php
                $mimes  = $opts['mimes'] ?? [];
                $maxKb  = $opts['max_kb'] ?? null;
                $accept = collect($mimes)->map(fn($m) => '.'.$m)->implode(',');
                $maxTxt = $maxKb ? (($maxKb >= 1024) ? round($maxKb / 1024, 1).'MB' : $maxKb.'KB') : null;
                $lblId  = 'f-lbl-'.$field->name;
              @endphp
              <label class="sg-upload-area block border-2 border-dashed {{ $hasErr ? 'border-red-600' : 'border-slate-300' }} rounded-2xl py-6 px-5 text-center cursor-pointer hover:border-navy transition-colors duration-150">
                <i class="fa-solid fa-file-arrow-up text-[22px] text-slate-400"></i>
                <div class="mt-2.5 text-sm font-bold text-slate-800" id="{{ $lblId }}">Pilih file...</div>
                @if($mimes || $maxTxt)
                <div class="mt-1 text-[12.5px] text-slate-500">
                  {{ $mimes ? 'Format: '.implode(', ', array_map('strtoupper', $mimes)) : '' }}{{ ($mimes && $maxTxt) ? ' · ' : '' }}{{ $maxTxt ? 'Maks. '.$maxTxt : '' }}
                </div>
                @endif
                <input type="file" name="{{ $inputName }}" accept="{{ $accept }}"
                  {{ $field->is_required ? 'required' : '' }} class="sr-only"
                  onchange="document.getElementById('{{ $lblId }}').textContent = this.files[0]?.name || 'Pilih file...'" />
              </label>

            @elseif($field->type === 'date')
              <input type="date" name="{{ $inputName }}" value="{{ $oldVal }}"
                {{ $field->is_required ? 'required' : '' }}
                class="form-field {{ $hasErr ? 'border-red-600' : '' }}" />

            @elseif($field->type === 'number')
              <input type="number" name="{{ $inputName }}" value="{{ $oldVal }}"
                {{ $field->is_required ? 'required' : '' }}
                class="form-field {{ $hasErr ? 'border-red-600' : '' }}" />

            @else
              <input type="text" name="{{ $inputName }}" value="{{ $oldVal }}"
                {{ $field->is_required ? 'required' : '' }}
                class="form-field {{ $hasErr ? 'border-red-600' : '' }}" />
            @endif

            @error($errKey)
              <p class="mt-1.5 text-[12.5px] text-red-600">{{ $message }}</p>
            @enderror
          </div>
          @endforeach
        </div>
      </section>
      @endforeach

      {{-- Agreement + Submit --}}
      <section class="sg-card mt-4 rounded-[20px] py-[26px] px-7">
        <label class="sg-agreement flex gap-[11px] items-start p-4 rounded-[14px] cursor-pointer">
          <div class="sg-chk relative w-[18px] h-[18px] shrink-0 mt-[2px]">
            <input type="checkbox" name="agreement" id="agreement" required
                   class="sg-chk-input absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10 m-0 p-0" />
            <span class="sg-chk-face flex items-center justify-center w-full h-full rounded-[5px] border-2 border-slate-300 transition-colors duration-150">
              <i class="fa-solid fa-check text-[9px] text-white opacity-0 transition-opacity duration-150 pointer-events-none"></i>
            </span>
          </div>
          <span class="text-[13px] leading-[1.6] text-slate-600">Saya menyatakan seluruh data dan dokumen yang saya kirimkan adalah benar, dan bersedia menerima konsekuensi apabila di kemudian hari ditemukan ketidaksesuaian.</span>
        </label>

        <div class="mt-[22px] pt-5 sg-form-footer flex flex-wrap gap-3 justify-between items-center">
          <span class="text-[12.5px] text-slate-500">Proses rekrutmen Syifa Global Group tidak dipungut biaya.</span>
          <div class="flex gap-[10px]">
            <a href="{{ route('welcome') }}" class="sg-auth-alt-btn px-5 py-3 rounded-xl border border-slate-300 bg-white text-slate-700 text-[13.5px] font-bold no-underline hover:border-slate-400 transition-colors duration-150">Batal</a>
            <button type="button" onclick="openConfirm()" class="px-[26px] py-3 rounded-xl border-0 bg-sgg-green hover:bg-sgg-green-hover text-white text-[13.5px] font-bold cursor-pointer transition-colors duration-150">
              <i class="fa-solid fa-paper-plane mr-2 text-xs"></i>Kirim Lamaran
            </button>
          </div>
        </div>
      </section>

    </form>

    <script>
    function openConfirm() {
      var form = document.getElementById('lamaran-form');
      var banner = document.getElementById('client-error-banner');
      var list = document.getElementById('client-error-list');

      if (!form.checkValidity()) {
        var invalids = form.querySelectorAll(':invalid');
        var seen = {};
        var items = [];
        invalids.forEach(function (el) {
          var wrap = el.closest('.sg-fields > div, .sg-agreement');
          var text;
          if (wrap && wrap.classList.contains('sg-agreement')) {
            text = 'Persetujuan data';
          } else {
            var label = wrap ? wrap.querySelector('label') : null;
            text = label ? label.textContent.replace('*', '').trim() : (el.name || 'Isian');
          }
          if (seen[text]) return;
          seen[text] = true;
          items.push(text);
        });

        list.innerHTML = '';
        items.forEach(function (text) {
          var li = document.createElement('li');
          li.textContent = text + ' belum diisi/dipilih.';
          list.appendChild(li);
        });
        banner.style.display = 'block';
        banner.scrollIntoView({ behavior: 'smooth', block: 'center' });

        form.reportValidity();
        return;
      }

      banner.style.display = 'none';
      var modal = document.getElementById('confirm-modal');
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
      requestAnimationFrame(function () {
        requestAnimationFrame(function () { modal.classList.add('is-open'); });
      });
    }
    function closeConfirm() {
      var modal = document.getElementById('confirm-modal');
      modal.classList.remove('is-open');
      document.body.style.overflow = '';
      setTimeout(function () { modal.style.display = 'none'; }, 180);
    }
    function submitLamaran() {
      var btn = document.getElementById('submit-lamaran-btn');
      if (btn) {
        if (btn.disabled) return; // cegah klik ganda / spam submit
        btn.disabled = true;
        var label = document.getElementById('submit-lamaran-label');
        if (label) label.textContent = 'Mengirim...';
      }
      clearDraft();
      document.getElementById('lamaran-form').submit();
    }
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeConfirm();
    });
    </script>

    {{-- Draft otomatis: isian tersimpan di browser (localStorage) supaya tidak hilang kalau di-refresh.
         File upload tidak bisa dipulihkan (batasan keamanan browser) — pelamar perlu pilih ulang filenya. --}}
    <script>
    (function () {
      var DRAFT_KEY = 'lamaran-draft-{{ $jobOpening->id }}';
      var form = document.getElementById('lamaran-form');

      function collectDraft() {
        var data = {};
        form.querySelectorAll('[name^="fields["]').forEach(function (el) {
          if (el.type === 'file') return;
          if (el.type === 'radio' || el.type === 'checkbox') {
            if (el.checked) data[el.name] = el.value;
          } else {
            data[el.name] = el.value;
          }
        });
        var agreement = document.getElementById('agreement');
        if (agreement) data['__agreement'] = agreement.checked;
        return data;
      }

      function saveDraft() {
        try { localStorage.setItem(DRAFT_KEY, JSON.stringify(collectDraft())); } catch (e) {}
      }

      window.clearDraft = function () {
        try { localStorage.removeItem(DRAFT_KEY); } catch (e) {}
      };

      function restoreDraft() {
        var raw;
        try { raw = localStorage.getItem(DRAFT_KEY); } catch (e) { return; }
        if (!raw) return;
        var data;
        try { data = JSON.parse(raw); } catch (e) { return; }

        Object.keys(data).forEach(function (name) {
          if (name === '__agreement') {
            var agreement = document.getElementById('agreement');
            if (agreement && data[name]) { agreement.checked = true; agreement.dispatchEvent(new Event('change')); }
            return;
          }
          var val = data[name];
          if (!val) return;
          var radio = form.querySelector('[name="' + name + '"][value="' + CSS.escape(val) + '"]');
          if (radio && (radio.type === 'radio' || radio.type === 'checkbox')) {
            radio.checked = true;
            radio.dispatchEvent(new Event('change'));
            return;
          }
          var el = form.querySelector('[name="' + name + '"]:not([type="radio"]):not([type="checkbox"]):not([type="file"])');
          if (el) el.value = val;
        });
      }

      @if($errors->isEmpty())
      window.addEventListener('load', restoreDraft);
      @endif

      form.addEventListener('input', saveDraft);
      form.addEventListener('change', saveDraft);
    })();
    </script>
  </main>
</div>

{{-- Confirmation Modal — sengaja di luar .sg-rise: elemen ber-animasi transform
     jadi containing block untuk position:fixed, bikin modal ini nge-center ke
     tinggi seluruh halaman alih-alih ke viewport kalau ditaruh di dalamnya. --}}
<style>
  #confirm-modal { opacity: 0; transition: opacity .18s ease; }
  #confirm-modal.is-open { opacity: 1; }
  #confirm-modal .sg-card { transform: scale(.95) translateY(6px); transition: transform .18s ease; }
  #confirm-modal.is-open .sg-card { transform: scale(1) translateY(0); }
</style>
<div id="confirm-modal" style="display:none;position:fixed;inset:0;z-index:9999;padding:16px;" class="flex items-center justify-center">
  <div onclick="closeConfirm()" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);backdrop-filter:blur(3px);"></div>
  <div class="relative sg-card rounded-[22px] shadow-2xl w-full max-w-sm mx-auto px-7 py-7" style="z-index:1;">
    <div class="flex flex-col items-center text-center gap-3">
      <div class="w-14 h-14 rounded-full flex items-center justify-center mb-1" style="background:rgba(4,44,108,0.1);">
        <i class="fa-solid fa-paper-plane text-xl" style="color:{{ $brand }};"></i>
      </div>
      <h3 class="m-0 text-[18px] font-extrabold text-slate-800 tracking-[-0.3px]">Kirim Lamaran?</h3>
      <p class="m-0 text-[13.5px] text-slate-500 leading-relaxed">
        Pastikan semua data yang kamu isi sudah benar.<br>
        Lamaran yang sudah dikirim <strong>tidak dapat diubah</strong>.
      </p>
    </div>
    <div class="mt-6 flex gap-[10px]">
      <button type="button" onclick="closeConfirm()"
        class="sg-auth-alt-btn flex-1 px-4 py-3 rounded-xl border border-slate-300 bg-white text-slate-700 text-[13.5px] font-bold cursor-pointer transition-colors duration-150 hover:border-slate-400">
        Periksa Kembali
      </button>
      <button type="button" id="submit-lamaran-btn" onclick="submitLamaran()"
        class="flex-1 px-4 py-3 rounded-xl border-0 text-white text-[13.5px] font-bold cursor-pointer transition-colors duration-150 disabled:opacity-60 disabled:cursor-not-allowed"
        style="background:{{ $brand }};">
        <i class="fa-solid fa-paper-plane mr-1.5 text-xs"></i>
        <span id="submit-lamaran-label">Ya, Kirim!</span>
      </button>
    </div>
  </div>
</div>

</x-public-layout>

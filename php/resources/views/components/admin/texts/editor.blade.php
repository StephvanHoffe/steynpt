@props(['page', 'initial', 'shared', 'changedAt' => [], 'state' => []])
{{-- Bewerkscherm voor de teksten van één pagina (TextEditor in de Next.js-versie); de logica staat in resources/js/components/admin-texts.js. --}}
@php
    use App\Content\Vars;

    $config = [
        'page' => $page,
        'initial' => $initial,
        'shared' => $shared,
        'vars' => Vars::VARS,
        'state' => [
            'error' => $state['error'] ?? null,
            'success' => $state['success'] ?? null,
            'fieldErrors' => (object) ($state['fieldErrors'] ?? []),
            'submitted' => $state['submitted'] ?? null,
        ],
    ];
    $initiallyDirty = isset($state['submitted']);
@endphp
<form method="post" action="/admin/teksten/{{ $page['slug'] }}" x-data="adminTexts(@js($config))" @submit="onSubmit()" @keydown.window="onKey($event)"
  class="grid grid-cols-1 gap-6 lg:grid-cols-[13rem_minmax(0,1fr)] xl:grid-cols-[15rem_minmax(0,48rem)]">
  @csrf
  <input type="hidden" name="page" value="{{ $page['slug'] }}">
  <input type="hidden" name="values" x-ref="values" :value="json" value="{{ json_encode($initial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">

  <aside class="min-w-0 lg:sticky lg:top-20 lg:self-start">
    <nav aria-label="Onderdelen van deze pagina">
      <p class="mb-2 hidden text-xs font-semibold uppercase tracking-[0.12em] text-muted lg:block">Onderdelen</p>
      <ul class="flex gap-1.5 overflow-x-auto pb-1 lg:flex-col lg:gap-0.5 lg:overflow-visible lg:pb-0">
        @foreach ($page['sections'] as $s => $section)
          <li class="shrink-0">
            <a href="#sectie-{{ $s }}"
              class="flex items-center justify-between gap-2 whitespace-nowrap rounded-full border border-line bg-white px-3 py-1.5 text-sm text-ink/80 hover:border-ink/40 hover:text-ink lg:rounded-lg lg:border-transparent lg:bg-transparent lg:hover:bg-white">
              {{ $section['title'] }}
              <template x-if="sectionState(@js($s)).error">
                <x-icon name="CircleAlert" class="size-3.5 text-danger" aria-label="bevat een fout" />
              </template>
              <template x-if="!sectionState(@js($s)).error && sectionState(@js($s)).dirty">
                <span class="size-2 rounded-full bg-accent" title="Niet opgeslagen wijzigingen"><span class="sr-only">niet opgeslagen</span></span>
              </template>
            </a>
          </li>
        @endforeach
      </ul>
    </nav>
    <template x-if="usedVars.length > 0">
      <div class="mt-5 hidden rounded-xl border border-line bg-white p-4 text-xs lg:block">
        <p class="font-semibold text-ink">Automatische waarden</p>
        <p class="mt-1 text-muted">Deze codes worden op de site vervangen door:</p>
        <dl class="mt-3 grid gap-2">
          <template x-for="v in usedVars" :key="v.key">
            <div>
              <dt class="font-mono text-[11px] text-ink" x-text="'{' + v.key + '}'"></dt>
              <dd class="text-muted">
                <span x-text="vars[v.key] || '(leeg)'"></span><template x-if="v.page !== @js($page['slug'])"><span> · <a :href="'/admin/teksten/' + v.page + '#sectie-' + v.section" class="underline underline-offset-2 hover:text-ink">aanpassen</a></span></template>
              </dd>
            </div>
          </template>
        </dl>
      </div>
    </template>
  </aside>

  <div class="grid min-w-0 gap-5">
    <fieldset :disabled="pending" class="grid min-w-0 gap-5">
      <legend class="sr-only">Teksten van {{ $page['title'] }}</legend>
      @foreach ($page['sections'] as $s => $section)
        <section id="sectie-{{ $s }}" aria-labelledby="kop-{{ $s }}" class="card scroll-mt-20 p-5 sm:p-6">
          <header class="mb-5">
            <h2 id="kop-{{ $s }}" class="text-lg font-semibold">{{ $section['title'] }}</h2>
            @if ($section['hint'])<p class="mt-0.5 text-sm text-muted">{{ $section['hint'] }}</p>@endif
          </header>
          <div class="grid gap-6">
            @foreach ($section['fields'] as $f => $field)
              <x-admin.texts.field :s="$s" :f="$f" :field="$field" :changed-at="$changedAt[$s.'.'.$f] ?? null" />
            @endforeach
          </div>
        </section>
      @endforeach
    </fieldset>

    <template x-if="anyCustom">
      <p class="text-right">
        <button type="button" @click="resetAll()" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted underline-offset-2 hover:text-ink hover:underline">
          <x-icon name="RotateCcw" class="size-3.5" /> Alle teksten op deze pagina terugzetten naar de standaardtekst
        </button>
      </p>
    </template>

    <div class="sticky bottom-3 z-20">
      <div class="flex flex-col gap-2 rounded-xl border border-line bg-white/95 p-3 shadow-lg shadow-ink/10 backdrop-blur sm:flex-row sm:items-center sm:justify-between sm:gap-3 sm:pl-5">
        <p role="status" class="min-w-0 flex-1 text-sm">
          <template x-if="status === 'pending'"><span class="text-muted">Bezig met opslaan…</span></template>
          <template x-if="status === 'error'">
            <span class="flex items-start gap-2 text-danger"><x-icon name="CircleAlert" class="mt-0.5 size-4 shrink-0" /> <span x-text="error"></span></span>
          </template>
          <template x-if="status === 'dirty'"><span class="font-medium" x-text="dirtyText"></span></template>
          <template x-if="status === 'success'">
            <span class="flex items-start gap-2 text-success">
              <x-icon name="CircleCheck" class="mt-0.5 size-4 shrink-0" />
              <span><span x-text="success"></span>@if ($page['path']) <a href="{{ $page['path'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold underline underline-offset-2">Bekijk de pagina</a>@endif</span>
            </span>
          </template>
          <template x-if="status === 'idle'">
            <span class="text-muted">Pas een tekst aan en klik op Opslaan.<span class="hidden sm:inline"> Ctrl+S werkt ook.</span></span>
          </template>
        </p>
        <div class="flex shrink-0 justify-end gap-2">
          <template x-if="dirty.length > 0">
            <button type="button" @click="undo()" :disabled="pending" class="btn btn-sm btn-outline bg-white">
              <x-icon name="Undo2" class="size-4" /> Ongedaan maken
            </button>
          </template>
          <button type="submit" :disabled="!dirty.length || pending" @if (! $initiallyDirty) disabled @endif class="btn btn-sm btn-primary min-w-28">
            <span x-show="pending" x-cloak class="contents"><x-icon name="LoaderCircle" class="size-4 animate-spin" /> Opslaan…</span>
            <span x-show="!pending" class="contents">Opslaan</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</form>

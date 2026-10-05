@php
    use App\Services\AdminFormat;
    use App\Site\Site;

    $tab = fn (bool $active) => 'inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium '.($active ? 'border-ink bg-ink text-white' : 'border-line bg-white hover:border-ink');
@endphp
<x-layouts.admin title="Aanvragen">
  <x-admin.page>
    <div class="max-w-5xl">
      <x-admin.page-header title="Aanvragen" description="Aanvragen voor een gratis kennismaking via de contactpagina." />
      <nav aria-label="Filter" class="mb-4 flex gap-1.5">
        <a href="/admin/aanvragen" class="{{ $tab(! $done) }}" @if (! $done) aria-current="page" @endif>
          Open <span class="tabular-nums opacity-70">{{ $openCount }}</span>
        </a>
        <a href="/admin/aanvragen?toon=afgehandeld" class="{{ $tab($done) }}" @if ($done) aria-current="page" @endif>
          Afgehandeld <span class="tabular-nums opacity-70">{{ $doneCount }}</span>
        </a>
      </nav>

      @if ($rows->isEmpty())
        <x-admin.empty-state>{{ $done ? 'Nog niets afgehandeld.' : 'Geen open aanvragen. Mooi zo!' }}</x-admin.empty-state>
      @else
        <ul class="grid gap-3">
          @foreach ($rows as $c)
            <li class="card p-5">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <p class="font-semibold">{{ $c->name }}</p>
                  <p class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                    <a href="mailto:{{ $c->email }}" class="inline-flex items-center gap-1.5 hover:underline">
                      <x-icon name="Mail" class="size-3.5 text-muted" /> {{ $c->email }}
                    </a>
                    @if ($c->phone)
                      <a href="tel:{{ preg_replace('/\s/', '', $c->phone) }}" class="inline-flex items-center gap-1.5 hover:underline">
                        <x-icon name="Phone" class="size-3.5 text-muted" /> {{ $c->phone }}
                      </a>
                    @endif
                  </p>
                </div>
                <span class="rounded-full bg-accent-tint px-3 py-1 text-xs font-semibold text-accent">{{ Site::interestLabel($c->interest) ?? $c->interest }}</span>
              </div>
              @if ($c->message)
                <p class="mt-3 whitespace-pre-line rounded-lg bg-surface px-4 py-3 text-sm">{{ $c->message }}</p>
              @endif
              <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-muted">
                <span class="first-letter:uppercase">{{ $c->created_at ? AdminFormat::shortDayTime($c->created_at) : '' }}</span>
                <form method="post" action="/admin/aanvragen/afhandelen">
                  @csrf
                  <input type="hidden" name="id" value="{{ $c->id }}">
                  <button type="submit" class="btn btn-sm {{ $c->handled ? 'btn-outline' : 'btn-primary' }}">
                    {{ $c->handled ? 'Terugzetten naar open' : 'Markeer als afgehandeld' }}
                  </button>
                </form>
              </div>
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  </x-admin.page>
</x-layouts.admin>

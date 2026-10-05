@php $t = \App\Site\Texts::get('algemeen')['locatie']; @endphp
<div class="grid gap-4 md:grid-cols-3">
  <a href="{{ \App\Site\Site::mapsUrl($t['street'], $t['city']) }}" target="_blank" rel="noopener noreferrer" class="card group flex flex-col p-6 transition-colors hover:border-ink/40">
    <x-icon name="MapPin" class="size-6" />
    <h3 class="display mt-6 text-2xl">{{ $t['name'] }}</h3>
    <p class="mt-2 text-sm text-muted">{{ $t['street'] }}<br>{{ $t['city'] }}@if ($t['area'] !== '')<br>{{ $t['area'] }}@endif</p>
    @if ($t['directions'] !== '')<p class="mt-3 text-sm">{{ $t['directions'] }}</p>@endif
    <span class="mt-auto inline-flex items-center gap-1 pt-5 text-sm font-semibold">
      Route <x-icon name="ArrowUpRight" class="size-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
    </span>
  </a>
  <div class="card flex flex-col p-6">
    <x-icon name="Trees" class="size-6" />
    <h3 class="display mt-6 text-2xl">{{ $t['onLocationTitle'] }}</h3>
    <p class="mt-2 text-sm text-muted">{{ $t['onLocation'] }}</p>
  </div>
  <div class="flex flex-col rounded-xl bg-accent-tint p-6">
    <x-icon name="Smartphone" class="size-6 text-accent" />
    <h3 class="display mt-6 text-2xl">{{ $t['onlineTitle'] }}</h3>
    <p class="mt-2 text-sm text-muted">{{ $t['online'] }}</p>
  </div>
</div>

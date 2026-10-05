@php
    $t = \App\Site\Texts::get('algemeen');
    $footer = $t['footer'];
    $locatie = $t['locatie'];
    $columns = [
        ['title' => 'Aanbod', 'links' => [
            ['href' => '/online-coaching', 'label' => 'Online coaching'],
            ['href' => '/personal-training', 'label' => 'Personal training'],
            ['href' => '/personal-training#topsport', 'label' => 'Topsport & specifieke doelen'],
            ['href' => '/ademcoaching', 'label' => 'Ademcoaching'],
            ['href' => '/voedingscoaching', 'label' => 'Voedingscoaching'],
        ]],
        ['title' => 'SteynPT', 'links' => [
            ['href' => '/over-steyn', 'label' => 'Over Steyn'],
            ['href' => '/tarieven', 'label' => 'Tarieven'],
            ['href' => '/vriend-uitnodigen', 'label' => 'Vriend uitnodigen'],
            ['href' => '/contact', 'label' => 'Gratis kennismaking'],
            ['href' => '/contact', 'label' => 'Contact'],
        ]],
        ['title' => 'Account', 'links' => [
            ['href' => '/registreren', 'label' => 'Account aanmaken'],
            ['href' => '/inloggen', 'label' => 'Inloggen'],
            ['href' => '/account', 'label' => 'Mijn omgeving'],
            ['href' => '/account/agenda', 'label' => 'Afspraak maken'],
        ]],
    ];
@endphp
<footer class="bg-ink text-white print:hidden">
  <div class="container-site grid gap-12 py-16 lg:grid-cols-[1fr_2.3fr] lg:py-20">
    <div>
      <x-logo variant="light" class="h-20 w-auto" />
      <p class="mt-6 max-w-sm text-sm leading-relaxed text-white/65">{{ $footer['intro'] }}</p>
      <a href="{{ $locatie['instagramUrl'] }}" target="_blank" rel="noopener noreferrer"
        class="mt-6 inline-flex items-center gap-2 rounded-lg border border-white/20 px-4 py-2 text-sm font-medium transition-colors hover:border-white">
        <x-instagram-icon class="size-4" /> Volg {{ $locatie['instagramHandle'] }}
      </a>
    </div>

    <div class="grid gap-10 sm:grid-cols-2 md:grid-cols-4">
      @foreach ($columns as $col)
        <div>
          <h2 class="text-xs font-semibold uppercase tracking-[0.14em] text-white/50">{{ $col['title'] }}</h2>
          <ul class="mt-4 space-y-2.5 text-sm">
            @foreach ($col['links'] as $link)
              <li><a href="{{ $link['href'] }}" class="text-white/80 transition-colors hover:text-white">{{ $link['label'] }}</a></li>
            @endforeach
          </ul>
        </div>
      @endforeach
      <div>
        <h2 class="text-xs font-semibold uppercase tracking-[0.14em] text-white/50">Locaties</h2>
        <ul class="mt-4 space-y-4 text-sm">
          <li>
            <a href="{{ \App\Site\Site::mapsUrl($locatie['street'], $locatie['city']) }}" target="_blank" rel="noopener noreferrer" class="group block">
              <span class="flex items-center gap-1.5 font-semibold">
                <x-icon name="MapPin" class="size-3.5 text-accent-soft" />
                {{ $locatie['name'] }}
                <x-icon name="ArrowUpRight" class="size-3.5 opacity-0 transition-opacity group-hover:opacity-100" />
              </span>
              <span class="mt-1 block text-white/65">{{ $locatie['street'] }}<br>{{ $locatie['city'] }}</span>
            </a>
          </li>
          @if ($footer['extra'] !== '')
            <li class="text-white/65">{{ $footer['extra'] }}</li>
          @endif
        </ul>
      </div>
    </div>
  </div>
  <div class="border-t border-white/10">
    <div class="container-site flex flex-col gap-3 py-6 text-xs text-white/50 sm:flex-row sm:items-center sm:justify-between">
      <p>© {{ now('Europe/Amsterdam')->year }} {{ $footer['copyright'] }}</p>
      <div class="flex gap-5">
        <a href="/privacy" class="hover:text-white">Privacyverklaring</a>
        <a href="/vriend-uitnodigen#voorwaarden" class="hover:text-white">Voorwaarden vriendenactie</a>
      </div>
    </div>
  </div>
</footer>

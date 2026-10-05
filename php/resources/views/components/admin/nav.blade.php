@props(['counts'])
@php
    $items = [
        ['href' => '/admin', 'label' => 'Overzicht', 'icon' => 'LayoutDashboard', 'exact' => true],
        ['href' => '/admin/agenda', 'label' => 'Agenda', 'icon' => 'CalendarDays', 'exclude' => '/admin/agenda/instellingen'],
        ['href' => '/admin/leden', 'label' => 'Leden', 'icon' => 'Users', 'badge' => 'applied'],
        ['href' => '/admin/trainingsschemas', 'label' => "Trainingsschema's", 'icon' => 'Dumbbell', 'badge' => 'training'],
        ['href' => '/admin/voedingsschemas', 'label' => "Voedingsschema's", 'icon' => 'Salad', 'badge' => 'voeding'],
        ['href' => '/admin/aanvragen', 'label' => 'Aanvragen', 'icon' => 'Inbox', 'badge' => 'requests'],
        ['href' => '/admin/teksten', 'label' => 'Website-teksten', 'icon' => 'FileText'],
        ['href' => '/admin/agenda/instellingen', 'label' => 'Instellingen', 'icon' => 'Settings2'],
    ];
    $badgeTitle = ['applied' => 'coaching aangevraagd', 'training' => 'te maken of te controleren', 'voeding' => 'te maken of te controleren', 'requests' => 'open'];
    $path = '/'.ltrim(request()->path(), '/');
    $isActive = fn (array $item) => ($item['exact'] ?? false)
        ? $path === $item['href']
        : str_starts_with($path, $item['href']) && ! (isset($item['exclude']) && str_starts_with($path, $item['exclude']));
@endphp
<div class="lg:w-60 lg:shrink-0 lg:border-r lg:border-line lg:bg-white print:hidden">
  <nav aria-label="Beheer" class="lg:sticky lg:top-14">
    {{-- Op de telefoon scrolt de balk horizontaal: houd het actieve onderdeel in beeld. --}}
    <ul class="relative flex gap-1 overflow-x-auto border-b border-line bg-white px-3 py-2 lg:flex-col lg:overflow-visible lg:border-0 lg:px-3 lg:py-4"
      x-data x-init="$el.querySelector('[aria-current=page]')?.scrollIntoView({ block: 'nearest', inline: 'nearest' })">
      @foreach ($items as $item)
        @php
            $active = $isActive($item);
            $n = isset($item['badge']) ? $counts[$item['badge']] : 0;
        @endphp
        <li class="shrink-0">
          <a href="{{ $item['href'] }}" @if ($active) aria-current="page" @endif
            class="relative flex items-center gap-2.5 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $active ? 'bg-ink text-white' : 'text-ink/80 hover:bg-surface hover:text-ink' }}">
            <x-icon :name="$item['icon']" class="size-4 shrink-0" />
            <span class="flex-1">{{ $item['label'] }}</span>
            @if ($n > 0)
              <span class="min-w-5 rounded-full px-1.5 text-center text-xs font-semibold tabular-nums {{ $active ? 'bg-white text-ink' : 'bg-accent text-white' }}" title="{{ $n }} {{ $badgeTitle[$item['badge']] }}">{{ $n }}<span class="sr-only"> {{ $badgeTitle[$item['badge']] }}</span></span>
            @endif
          </a>
        </li>
      @endforeach
    </ul>
  </nav>
</div>

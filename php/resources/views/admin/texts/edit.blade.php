<x-layouts.admin :title="$page['title'].' · Website-teksten'">
  <x-admin.page>
    <x-admin.page-header :title="$page['title']" :back="['href' => '/admin/teksten', 'label' => 'Website-teksten']">
      <x-slot:description>
        {{ $page['description'] }}
        {{ $last ? "Laatst opgeslagen op {$last}." : 'Nog niets aangepast: alles is de standaardtekst.' }}
      </x-slot:description>
      <x-slot:actions>
        {{-- Taal van de teksten: Nederlands of Engels (de Engelse site staat onder /en). --}}
        <nav aria-label="Taal van de teksten" class="inline-flex rounded-full border border-line bg-white p-0.5 text-sm font-semibold">
          <a href="/admin/teksten/{{ $slug }}" @if ($locale === 'nl') aria-current="page" @endif class="rounded-full px-3 py-1 {{ $locale === 'nl' ? 'bg-ink text-white' : 'text-muted hover:text-ink' }}">Nederlands</a>
          <a href="/admin/teksten/{{ \App\Content\English::PREFIX.$slug }}" @if ($locale === 'en') aria-current="page" @endif class="rounded-full px-3 py-1 {{ $locale === 'en' ? 'bg-ink text-white' : 'text-muted hover:text-ink' }}">English</a>
        </nav>
        @if ($page['path'])
          <a href="{{ $page['path'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline bg-white">
            Bekijk op de site <x-icon name="ExternalLink" class="size-3.5" />
          </a>
        @endif
      </x-slot:actions>
    </x-admin.page-header>
    <x-admin.texts.editor :page="$page" :initial="$values" :shared="$shared" :changed-at="$changedAt" :state="$state" />
  </x-admin.page>
</x-layouts.admin>

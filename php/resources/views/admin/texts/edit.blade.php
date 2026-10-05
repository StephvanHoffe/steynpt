<x-layouts.admin :title="$page['title'].' · Website-teksten'">
  <x-admin.page>
    <x-admin.page-header :title="$page['title']" :back="['href' => '/admin/teksten', 'label' => 'Website-teksten']">
      <x-slot:description>
        {{ $page['description'] }}
        {{ $last ? "Laatst opgeslagen op {$last}." : 'Nog niets aangepast: alles is de standaardtekst.' }}
      </x-slot:description>
      @if ($page['path'])
        <x-slot:actions>
          <a href="{{ $page['path'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline bg-white">
            Bekijk op de site <x-icon name="ExternalLink" class="size-3.5" />
          </a>
        </x-slot:actions>
      @endif
    </x-admin.page-header>
    <x-admin.texts.editor :page="$page" :initial="$values" :shared="$shared" :changed-at="$changedAt" :state="$state" />
  </x-admin.page>
</x-layouts.admin>

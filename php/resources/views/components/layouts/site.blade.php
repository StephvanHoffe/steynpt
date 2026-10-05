@props(['title' => null, 'absoluteTitle' => false, 'description' => null, 'noindex' => false, 'faq' => null])
{{-- Website en Mijn omgeving: met de gewone kop en footer. Het beheer heeft een eigen opmaak. --}}
<x-layouts.base :title="$title" :absolute-title="$absoluteTitle" :description="$description" :noindex="$noindex" :faq="$faq">
  <x-site.header />
  <main id="inhoud" class="flex-1 overflow-x-clip">
    {{ $slot }}
  </main>
  <x-site.footer />
</x-layouts.base>

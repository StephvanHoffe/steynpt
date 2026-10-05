@props(['eyebrow', 'title', 'size' => 'xl', 'eyebrowClass' => 'text-accent', 'gap' => 'mt-5'])
{{-- De kleine kop hoort bij de H1: zo staat de zoekterm ("Personal training in Amsterdam Oud-West") in de belangrijkste kop, zonder dat de opmaak verandert. --}}
<h1 {{ $attributes }}>
  <span class="eyebrow {{ $eyebrowClass }}">{{ $eyebrow }}</span><span class="sr-only">: </span>
  <span class="display display-{{ $size }} {{ $gap }} block">{{ \App\View\Rich::html($title) }}</span>
</h1>

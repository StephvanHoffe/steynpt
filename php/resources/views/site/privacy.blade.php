<x-layouts.site :title="$t['seo']['title']" :description="$t['seo']['description']">
  <div class="container-site max-w-3xl py-16 lg:py-24">
    <x-hero-heading :eyebrow="$t['intro']['eyebrow']" :title="$t['intro']['title']" size="lg" eyebrow-class="text-muted" gap="mt-4" />
    <p class="lead mt-6 text-muted">{{ $t['intro']['intro'] }}</p>
    <div class="mt-12 space-y-10">
      @foreach ($t['onderdelen']['sections'] as $s)
        <section>
          <h2 class="text-xl font-semibold">{{ $s['title'] }}</h2>
          <div class="prose-site mt-3 text-muted">{{ \App\View\Rich::paragraphs($s['body']) }}</div>
        </section>
      @endforeach
    </div>
    <p class="mt-12 text-sm text-muted">Vragen? <a href="/contact" class="font-semibold text-ink underline">Neem contact op</a>.</p>
  </div>
</x-layouts.site>

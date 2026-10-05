@props(['step', 'title', 'intro' => null])
<section class="card p-6 sm:p-8">
  <p class="text-xs font-bold uppercase tracking-[0.14em] text-accent">Stap {{ $step }}</p>
  <h2 class="display mt-1 text-2xl">{{ $title }}</h2>
  @if ($intro)<p class="mt-1 text-sm text-muted">{{ $intro }}</p>@endif
  <div class="mt-6 grid gap-6">{{ $slot }}</div>
</section>

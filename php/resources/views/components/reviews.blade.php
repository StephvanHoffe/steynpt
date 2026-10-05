@php $reviews = \App\Site\Texts::get('algemeen')['reviews']['reviews']; @endphp
<div class="grid gap-5 md:grid-cols-2">
  @foreach ($reviews as $r)
    @php
        $initials = collect(explode(' ', $r['name']))->filter(fn ($p) => preg_match('/^[A-Z]/', $p))->map(fn ($p) => $p[0])->take(2)->implode('');
    @endphp
    <figure class="card flex flex-col p-8">
      <x-icon name="Quote" class="size-8 text-accent" />
      <blockquote class="mt-5 flex-1 text-xl leading-snug font-medium text-pretty">“{{ $r['quote'] }}”</blockquote>
      <figcaption class="mt-6 flex items-center gap-3 border-t border-line pt-5">
        <span class="grid size-10 place-items-center rounded-full bg-accent-tint text-sm font-bold text-ink">{{ $initials }}</span>
        <span>
          <span class="block font-semibold">{{ $r['name'] }}</span>
          <span class="block text-sm text-muted">{{ $r['role'] }}</span>
        </span>
      </figcaption>
    </figure>
  @endforeach
</div>

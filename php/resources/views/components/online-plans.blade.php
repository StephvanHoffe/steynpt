@props(['referral' => null])
@php
    $plans = \App\Site\Texts::onlinePlans();
    $labels = \App\Site\Texts::get('pakketten')['labels'];
@endphp
<div class="grid gap-5 lg:grid-cols-3">
  @foreach ($plans as $plan)
    @php
        $featured = $plan['featured'];
        $href = '/registreren?plan='.$plan['id'].($referral ? '&ref='.rawurlencode($referral) : '');
    @endphp
    <article class="relative flex flex-col rounded-xl border p-7 {{ $featured ? 'border-ink bg-white ring-1 ring-ink shadow-lg shadow-ink/5' : 'border-line bg-white' }}">
      @if ($featured)
        <span class="absolute -top-3 left-7 rounded bg-ink px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-white">{{ $labels['featured'] }}</span>
      @endif
      <h3 class="display text-4xl">{{ $plan['name'] }}</h3>
      <p class="mt-2 text-sm {{ $featured ? 'text-ink/80' : 'text-muted' }}">{{ $plan['tagline'] }}</p>
      <p class="mt-6 flex items-baseline gap-1">
        <span class="text-lg font-semibold">€</span>
        <span class="display text-6xl">{{ \App\Site\Locale::price($plan['price']) }}</span>
        <span class="ml-1 text-sm {{ $featured ? 'text-ink/80' : 'text-muted' }}">{{ __('per maand') }}</span>
      </p>
      <ul class="mt-6 flex-1 space-y-2.5 text-sm">
        @foreach ($plan['features'] as $f)
          <li class="flex gap-2.5">
            <x-icon name="Check" class="mt-0.5 size-4 shrink-0 text-accent" stroke-width="2.5" />
            <span>{{ $f }}</span>
          </li>
        @endforeach
      </ul>
      <a href="{{ $href }}" class="btn mt-7 w-full {{ $featured ? 'btn-primary' : 'btn-outline bg-white' }}">{{ __('Kies :plan', ['plan' => $plan['name']]) }}</a>
    </article>
  @endforeach
</div>

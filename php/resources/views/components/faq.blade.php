@props(['items'])
<div class="divide-y divide-line rounded-xl border border-line bg-white">
  @foreach ($items as $item)
    <details class="group px-6 py-5 [&_summary::-webkit-details-marker]:hidden">
      <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-semibold">
        {{ $item['q'] }}
        <x-icon name="ChevronDown" class="size-5 shrink-0 transition-transform group-open:rotate-180" />
      </summary>
      <p class="mt-3 leading-relaxed text-muted">{{ $item['a'] }}</p>
    </details>
  @endforeach
</div>

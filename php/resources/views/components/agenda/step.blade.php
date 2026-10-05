@props(['n', 'title'])
<section class="card p-6">
  <h2 class="flex items-center gap-3 font-semibold">
    <span class="grid size-7 place-items-center rounded-md bg-ink text-xs text-white">{{ $n }}</span>
    {{ $title }}
  </h2>
  <div class="mt-4">{{ $slot }}</div>
</section>

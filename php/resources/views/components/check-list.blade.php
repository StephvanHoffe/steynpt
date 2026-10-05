@props(['items'])
<ul class="space-y-3">
  @foreach ($items as $item)
    <li class="flex gap-3">
      <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-accent-tint text-accent">
        <x-icon name="Check" class="size-3" stroke-width="3" />
      </span>
      <span class="text-ink/85">{{ $item }}</span>
    </li>
  @endforeach
</ul>

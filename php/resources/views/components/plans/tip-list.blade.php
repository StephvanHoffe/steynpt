@props(['tips'])
{{-- Tips onder een trainings- of voedingsschema. --}}
@if (count($tips) > 0)
  <section class="card break-inside-avoid p-5">
    <h3 class="flex items-center gap-2 font-semibold">
      <x-icon name="Lightbulb" class="size-5 text-accent" /> {{ __('Tips') }}
    </h3>
    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
      @foreach ($tips as $tip)
        <li>{{ $tip }}</li>
      @endforeach
    </ul>
  </section>
@endif

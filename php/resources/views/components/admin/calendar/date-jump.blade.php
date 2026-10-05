@props(['day', 'view', 'cancelled'])
{{-- Springen naar een datum; verstuurt direct na het kiezen. --}}
<form action="/admin/agenda" method="get" class="flex items-center gap-1.5">
  @if ($view !== 'week')<input type="hidden" name="weergave" value="{{ $view }}">@endif
  @if ($cancelled)<input type="hidden" name="geannuleerd" value="1">@endif
  <label class="sr-only" for="agenda-datum">Ga naar datum</label>
  <input id="agenda-datum" type="date" name="datum" value="{{ $day }}" required
    x-data @change="$el.value && $el.form.requestSubmit()"
    class="h-9 rounded-lg border border-line bg-white px-2 text-sm">
  <noscript><button type="submit" class="btn btn-sm btn-outline">Ga</button></noscript>
</form>

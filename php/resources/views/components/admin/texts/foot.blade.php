@props(['field', 'path'])
{{-- Uitleg, tekenteller en voorvertoning onder een veld (FieldFoot). $path is de Alpine-expressie van het pad. --}}
@php
    $kind = $field['kind'];
    $rich = $kind === 'line' && ! empty($field['rich']);
    $max = in_array($kind, ['line', 'text'], true) ? ($field['max'] ?? null) : null;
    $hint = $field['hint'] ?? ($rich
        ? 'Tussen *sterretjes* krijgt de tekst de accentkleur; Enter begint een nieuwe regel.'
        : ($kind === 'text' && ! empty($field['paragraphs']) ? 'Een lege regel begint een nieuwe alinea.' : null));
@endphp
@if ($kind === 'items' || $kind === 'check')
  @if (! empty($field['hint']))<p class="text-xs text-muted">{{ $field['hint'] }}</p>@endif
@else
  @if ($hint || $max || $kind === 'list')
    <p class="flex justify-between gap-3 text-xs text-muted">
      <span>{{ $hint ?? ($kind === 'list' ? 'Eén punt per regel.' : '') }}</span>
      @if ($kind === 'list')
        <span class="shrink-0 tabular-nums" x-text="listCount({{ $path }})"></span>
      @elseif ($max)
        <span class="shrink-0 tabular-nums" :class="{ 'font-semibold text-danger': lengthOf({{ $path }}) > {{ $max }} }" x-text="lengthOf({{ $path }}) + '/{{ $max }}'"></span>
      @endif
    </p>
  @endif
  <template x-if="unknownCodes({{ $path }}).length > 0">
    <p class="text-xs font-medium text-danger" x-text="unknownText({{ $path }})"></p>
  </template>
  <template x-if="previewHtml({{ $path }}, {{ $rich ? 'true' : 'false' }})">
    <div class="rounded-lg bg-surface px-3 py-2 text-xs text-muted">
      <span class="font-semibold text-ink">Op de site: </span><span class="contents" x-html="previewHtml({{ $path }}, {{ $rich ? 'true' : 'false' }})"></span>
    </div>
  </template>
@endif

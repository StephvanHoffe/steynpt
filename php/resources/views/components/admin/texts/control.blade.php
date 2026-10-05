@props(['field', 'model', 'path', 'id' => null, 'idExpr' => null])
{{-- Invoerveld per soort tekst. $model is de Alpine-expressie van de waarde, $path die van het pad (voor de foutmelding).
     Een vast id via $id, of een id dat afhangt van het item via $idExpr. --}}
@php
    $kind = $field['kind'];
    // Groeit mee met de tekst (field-sizing), met een redelijke minimale hoogte.
    $area = 'input min-h-0 py-2 text-sm leading-relaxed [field-sizing:content]';
    $common = ($id !== null ? 'id="'.e($id).'"' : ':id="'.e($idExpr).'"').' :aria-invalid="errorAt('.e($path).') ? \'true\' : null"';
@endphp
@if ($kind === 'line' && ! empty($field['rich']))
  <textarea {!! $common !!} class="{{ $area }}" x-model="{{ $model }}" :rows="Math.max(1, String({{ $model }} ?? '').split('\n').length)" rows="1"></textarea>
@elseif ($kind === 'line')
  <input {!! $common !!} class="input min-h-10 py-2 text-sm" x-model="{{ $model }}">
@elseif ($kind === 'text')
  <textarea {!! $common !!} class="{{ $area }} min-h-20" rows="3" x-model="{{ $model }}"></textarea>
@elseif ($kind === 'list')
  <textarea {!! $common !!} class="{{ $area }} min-h-20" :rows="Math.max(3, ({{ $model }} || []).length)" rows="3"
    :value="({{ $model }} || []).join('\n')" @input="{{ $model }} = $event.target.value.split('\n')"></textarea>
@elseif ($kind === 'price')
  <div class="flex items-center gap-2">
    <span class="text-sm font-semibold" aria-hidden="true">€</span>
    <input {!! $common !!} class="input min-h-10 w-36 py-2 text-sm tabular-nums" inputmode="decimal" x-model="{{ $model }}">
  </div>
@elseif ($kind === 'link')
  <select {!! $common !!} class="input min-h-10 py-2 text-sm sm:max-w-sm" x-model="{{ $model }}">
    @foreach (\App\Content\Values::SITE_LINKS as $l)
      <option value="{{ $l['href'] }}">{{ $l['label'] }} ({{ $l['href'] }})</option>
    @endforeach
  </select>
@elseif ($kind === 'url')
  <input {!! $common !!} type="url" class="input min-h-10 py-2 text-sm" x-model="{{ $model }}">
@endif

@props(['s', 'f', 'field', 'changedAt' => null])
{{-- Eén veld in het bewerkscherm (FieldEditor): label, invoer, uitleg, voorvertoning en foutmelding. --}}
@php
    $path = "{$s}.{$f}";
    $p = json_encode($path);
    $model = 'values['.json_encode($s).']['.json_encode($f).']';
    $id = 'veld-'.str_replace('.', '-', $path);
@endphp
<div class="grid gap-1.5 border-l-2 border-transparent pl-3 transition-colors" :class="{ 'border-accent': isDirty({{ $p }}), 'border-transparent': !isDirty({{ $p }}) }">
  @if ($field['kind'] === 'check')
    <div class="flex flex-wrap items-center justify-between gap-2">
      <label class="inline-flex items-center gap-2.5 text-sm font-semibold">
        <input type="checkbox" class="size-4 accent-ink" x-model="{{ $model }}">
        {{ $field['label'] }}
      </label>
      @include('components.admin.texts.reset', ['path' => $path, 'label' => $field['label'], 'changedAt' => $changedAt])
    </div>
  @else
    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
      <label for="{{ $id }}" class="text-sm font-semibold">{{ $field['label'] }}</label>
      @include('components.admin.texts.reset', ['path' => $path, 'label' => $field['label'], 'changedAt' => $changedAt])
    </div>
  @endif
  @if ($field['kind'] === 'items')
    <x-admin.texts.items :s="$s" :f="$f" :field="$field" />
  @elseif ($field['kind'] !== 'check')
    <x-admin.texts.control :field="$field" :model="$model" :path="$p" :id="$id" />
  @endif
  <x-admin.texts.foot :field="$field" :path="$p" />
  <template x-if="errorAt({{ $p }})"><p class="field-error mt-0" x-text="errorAt({{ $p }})"></p></template>
</div>

@props(['label', 'name', 'type' => 'text', 'value' => null, 'hint' => null, 'id' => null, 'old' => true, 'error' => null, 'bag' => 'default'])
@php
    $fieldId = $id ?? "f-{$name}";
    $error ??= $errors->getBag($bag)->first($name) ?: null;
    $current = $old ? old($name, $value) : $value;
@endphp
<div>
  <label for="{{ $fieldId }}" class="label">{{ __($label) }}</label>
  <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" class="{{ $attributes->get('class', 'input') }}"
    @if ($current !== null && $current !== '') value="{{ $current }}" @endif
    @if ($error) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @elseif ($hint) aria-describedby="{{ $fieldId }}-hint" @endif
    {{ $attributes->except('class') }}>
  @if ($hint && ! $error)<p id="{{ $fieldId }}-hint" class="mt-1.5 text-xs text-muted">{{ __($hint) }}</p>@endif
  @if ($error)<p id="{{ $fieldId }}-error" class="field-error">{{ __($error) }}</p>@endif
</div>

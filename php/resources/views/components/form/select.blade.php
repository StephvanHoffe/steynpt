@props(['label', 'name', 'options', 'value' => null, 'placeholder' => null, 'id' => null, 'error' => null, 'bag' => 'default'])
@php
    $fieldId = $id ?? "f-{$name}";
    $error ??= $errors->getBag($bag)->first($name) ?: null;
    $current = (string) old($name, $value);
@endphp
<div>
  <label for="{{ $fieldId }}" class="label">{{ $label }}</label>
  <select id="{{ $fieldId }}" name="{{ $name }}" class="input" @if ($error) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif {{ $attributes }}>
    @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
    @foreach ($options as $o)
      <option value="{{ $o['id'] }}" @selected($current === (string) $o['id'])>{{ $o['label'] }}</option>
    @endforeach
  </select>
  @if ($error)<p id="{{ $fieldId }}-error" class="field-error">{{ $error }}</p>@endif
</div>

@props(['name', 'label', 'hint' => null, 'value' => null, 'error' => null, 'placeholder' => null])
<div>
  <label for="f-{{ $name }}" class="label">{{ $label }} <span class="font-normal text-muted">{{ __('(optioneel)') }}</span></label>
  <textarea id="f-{{ $name }}" name="{{ $name }}" @if ($placeholder) placeholder="{{ $placeholder }}" @endif class="input min-h-20" @if ($error) aria-invalid="true" @endif>{{ $value }}</textarea>
  @if ($hint && ! $error)<p class="mt-1.5 text-xs text-muted">{{ $hint }}</p>@endif
  @if ($error)<p class="field-error">{{ $error }}</p>@endif
</div>

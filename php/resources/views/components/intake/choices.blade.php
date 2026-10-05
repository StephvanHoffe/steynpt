@props(['name', 'legend', 'options', 'multiple' => false, 'selected' => [], 'error' => null, 'columns' => 'sm:grid-cols-3'])
<fieldset @if ($error) aria-invalid="true" @endif>
  <legend class="label">{{ $legend }}</legend>
  <div class="grid grid-cols-2 gap-2 {{ $columns }}">
    @foreach ($options as $o)
      <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border-[1.5px] border-line bg-white px-3.5 py-3 text-sm transition-colors hover:border-ink/40 has-[:checked]:border-ink has-[:checked]:bg-surface has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-accent">
        <input type="{{ $multiple ? 'checkbox' : 'radio' }}" name="{{ $multiple ? $name.'[]' : $name }}" value="{{ $o['id'] }}" @checked(in_array((string) $o['id'], array_map('strval', $selected), true)) class="mt-0.5 size-4 shrink-0 accent-ink">
        <span>
          <span class="block font-medium">{{ $o['label'] }}</span>
          @if (! empty($o['hint']))<span class="mt-0.5 block text-xs text-muted">{{ $o['hint'] }}</span>@endif
        </span>
      </label>
    @endforeach
  </div>
  @if ($error)<p class="field-error">{{ $error }}</p>@endif
</fieldset>

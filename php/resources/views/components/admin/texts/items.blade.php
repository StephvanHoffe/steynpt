@props(['s', 'f', 'field'])
{{-- Lijst met items, zoals vragen of reviews (ItemsEditor): verplaatsen, verwijderen en toevoegen. --}}
@php
    $list = 'values['.json_encode($s).']['.json_encode($f).']';
    $fixed = ! empty($field['fixed']);
    $max = $field['max'] ?? 20;
    $min = $field['min'] ?? 1;
    $firstKey = array_key_first($field['fields']);
    $itemLabel = $field['itemLabel'];
    $itemName = mb_strtolower($itemLabel);
    $wide = fn (array $sub) => $sub['kind'] === 'text' || $sub['kind'] === 'list' || ($sub['kind'] === 'line' && ($sub['max'] ?? 160) >= 80);
    $args = json_encode($s).', '.json_encode($f);
    $label = json_encode($itemLabel.' ');
    $iconButton = 'grid size-8 place-items-center rounded-md border border-line bg-white text-ink/80 hover:border-ink/40 hover:text-ink disabled:opacity-35';
@endphp
<div class="grid gap-3">
  <template x-for="(item, i) in {{ $list }}" :key="i">
    <fieldset class="min-w-0 rounded-lg border border-line bg-surface/60 p-4">
      <legend class="sr-only" x-text="{{ $label }} + (i + 1)"></legend>
      <div class="mb-3 flex items-center justify-between gap-3">
        <p class="min-w-0 truncate text-sm font-semibold">
          <span x-text="{{ $label }} + (i + 1)"></span><template x-if="itemTitle(item, {{ json_encode($firstKey) }})"><span class="font-normal text-muted" x-text="' · ' + itemTitle(item, {{ json_encode($firstKey) }})"></span></template>
        </p>
        @unless ($fixed)
          <div class="flex shrink-0 gap-1">
            <button type="button" class="{{ $iconButton }}" @click="moveItem({{ $args }}, i, -1)" :disabled="i === 0"
              :aria-label="{{ $label }} + (i + 1) + ' omhoog'" :title="{{ $label }} + (i + 1) + ' omhoog'"><x-icon name="ArrowUp" class="size-4" /></button>
            <button type="button" class="{{ $iconButton }}" @click="moveItem({{ $args }}, i, 1)" :disabled="i === {{ $list }}.length - 1"
              :aria-label="{{ $label }} + (i + 1) + ' omlaag'" :title="{{ $label }} + (i + 1) + ' omlaag'"><x-icon name="ArrowDown" class="size-4" /></button>
            <button type="button" class="{{ $iconButton }}" @click="removeItem({{ $args }}, i)" :disabled="{{ $list }}.length <= {{ $min }}"
              :aria-label="{{ $label }} + (i + 1) + ' verwijderen'" :title="{{ $label }} + (i + 1) + ' verwijderen'"><x-icon name="Trash2" class="size-4" /></button>
          </div>
        @endunless
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($field['fields'] as $k => $sub)
          @php
              $model = 'item['.json_encode($k).']';
              $path = json_encode("{$s}.{$f}.").' + i + '.json_encode(".{$k}");
              $idExpr = json_encode('veld-'.$s.'-'.$f.'-').' + i + '.json_encode('-'.$k);
          @endphp
          <div class="grid min-w-0 content-start gap-1.5 {{ $wide($sub) ? 'sm:col-span-2' : '' }}">
            @if ($sub['kind'] === 'check')
              <label class="inline-flex items-center gap-2.5 text-sm font-medium">
                <input type="checkbox" class="size-4 accent-ink" x-model="{{ $model }}">
                {{ $sub['label'] }}
              </label>
            @else
              <label :for="{{ $idExpr }}" class="text-sm font-medium">{{ $sub['label'] }}@if (! empty($sub['optional']))<span class="font-normal text-muted"> (mag leeg)</span>@endif</label>
              <x-admin.texts.control :field="$sub" :model="$model" :path="$path" :id-expr="$idExpr" />
            @endif
            <x-admin.texts.foot :field="$sub" :path="$path" />
            <template x-if="errorAt({{ $path }})"><p class="field-error mt-0" x-text="errorAt({{ $path }})"></p></template>
          </div>
        @endforeach
      </div>
    </fieldset>
  </template>
  @unless ($fixed)
    <div>
      <button type="button" @click="addItem({{ $args }})" :disabled="{{ $list }}.length >= {{ $max }}" class="btn btn-sm btn-outline bg-white">
        <x-icon name="Plus" class="size-4" /> {{ $itemLabel }} toevoegen
      </button>
      <template x-if="{{ $list }}.length >= {{ $max }}">
        <span class="ml-3 text-xs text-muted">Maximaal {{ $max }} {{ $itemName === 'review' ? 'reviews' : 'items' }}.</span>
      </template>
    </div>
  @endunless
</div>

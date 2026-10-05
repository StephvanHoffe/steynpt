@props(['action', 'groups', 'selected' => null])
{{-- Klant kiezen voor een nieuw schema; laadt direct de gegevens van die klant.
     groups: [['label' => …, 'members' => [['id' => …, 'name' => …, 'note' => ?…], …]], …] --}}
<form action="{{ $action }}" method="get" class="flex flex-wrap items-end gap-2">
  <label class="block min-w-0 flex-1 basis-64">
    <span class="label">Klant</span>
    <select name="lid" required class="input" x-data @change="$el.value && $el.form.requestSubmit()">
      <option value="" disabled @selected(! $selected)>Kies een klant…</option>
      @foreach ($groups as $group)
        @continue(count($group['members']) === 0)
        <optgroup label="{{ $group['label'] }}">
          @foreach ($group['members'] as $m)
            <option value="{{ $m['id'] }}" @selected($selected === $m['id'])>{{ $m['name'] }}{{ ($m['note'] ?? null) ? ' · '.$m['note'] : '' }}</option>
          @endforeach
        </optgroup>
      @endforeach
    </select>
  </label>
  <noscript>
    <button type="submit" class="btn btn-outline">Kies</button>
  </noscript>
</form>

@props(['userId', 'type', 'aiEnabled', 'hasIntake', 'regenerate' => false, 'startsOn' => null])
{{-- Knoppen om een AI-concept te laten maken (met optionele instructie) of zelf te beginnen. De startdatum gaat mee naar het nieuwe concept. --}}
@php
    $action = \App\Services\AdminLabels::PLAN_SECTION[$type]['href'].'/nieuw';
    $hidden = function (string $method) use ($userId, $type, $startsOn) {
        $html = csrf_field()
            .'<input type="hidden" name="userId" value="'.e($userId).'">'
            .'<input type="hidden" name="type" value="'.e($type).'">'
            .'<input type="hidden" name="method" value="'.e($method).'">';
        if ($startsOn) {
            $html .= '<input type="hidden" name="startsOn" value="'.e($startsOn).'">';
        }

        return new \Illuminate\Support\HtmlString($html);
    };
@endphp
<div class="grid gap-3">
  @if ($aiEnabled && $hasIntake)
    <form action="{{ $action }}" method="post" class="grid gap-2">
      {{ $hidden('ai') }}
      <label class="block">
        <span class="label">{{ $regenerate ? 'Opnieuw laten maken met een instructie (optioneel)' : 'Instructie voor de AI (optioneel)' }}</span>
        <textarea name="instruction" rows="2" maxlength="1500" class="input min-h-0 py-2 text-sm"
          placeholder="{{ $type === 'training' ? "Bijv. 'geen squats vanwege de knie, meer focus op core'" : "Bijv. 'meer warme lunches, minder zuivel'" }}"></textarea>
      </label>
      <button type="submit" class="btn btn-sm btn-primary justify-self-start">
        <x-icon name="Sparkles" class="size-4" /> {{ $regenerate ? 'Nieuw AI-concept' : \App\View\PlanLabels::TYPE_LABEL[$type].' laten maken' }}
      </button>
    </form>
  @endif
  <form action="{{ $action }}" method="post">
    {{ $hidden('leeg') }}
    <button type="submit" class="btn btn-sm btn-outline bg-white">
      <x-icon name="FilePlus2" class="size-4" /> Zelf een leeg schema starten
    </button>
  </form>
  @if ($regenerate)
    <p class="text-xs text-muted">Een nieuw concept vervangt het huidige concept. Een gepubliceerd schema blijft zichtbaar voor de klant tot je het nieuwe publiceert.</p>
  @endif
</div>

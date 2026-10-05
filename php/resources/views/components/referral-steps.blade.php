{{-- De drie stappen van de vriendenactie, gedeeld door meerdere pagina's. --}}
@php $steps = \App\Site\Texts::get('algemeen')['vriendenactie']['steps']; @endphp
<ol class="grid gap-3">
  @foreach ($steps as $i => $step)
    <li class="card flex gap-5 p-6">
      <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-ink text-sm font-semibold text-white">{{ $i + 1 }}</span>
      <span>
        <span class="block font-semibold">{{ $step['title'] }}</span>
        <span class="mt-1 block text-muted">{{ $step['text'] }}</span>
      </span>
    </li>
  @endforeach
</ol>

@php
    use App\Support\Agenda;
@endphp
<x-layouts.admin title="Nieuwe afspraak">
  <x-admin.page>
    <div class="max-w-3xl">
      <x-admin.page-header :title="$moving ? 'Afspraak verplaatsen' : 'Nieuwe afspraak'" :back="['href' => '/admin/agenda?datum='.$defaults['day'], 'label' => 'Agenda']">
        <x-slot:description>
          @if ($moving)
            Nu: {{ Agenda::getAppointmentType($moving->type)['label'] ?? '' }} op <span class="first-letter:uppercase">{{ Agenda::formatDayLong($moving->starts_at) }}</span> om {{ Agenda::formatTime($moving->starts_at) }}. Kies
            een nieuw moment; de oude afspraak wordt dan geannuleerd.
          @else
            Plan een afspraak in voor een klant, bijvoorbeeld na een telefoontje.
          @endif
        </x-slot:description>
      </x-admin.page-header>
      <div class="card p-5 sm:p-7">
        <x-admin.appointment-form :members="$members" :locked-member="$locked" :min-day="$today" :defaults="$defaults" />
      </div>
    </div>
  </x-admin.page>
</x-layouts.admin>

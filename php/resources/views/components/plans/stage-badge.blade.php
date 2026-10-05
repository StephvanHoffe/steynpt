@props(['stage', 'coachingStatus' => null])
@php
    $info = \App\Support\Plans\Pipeline::STAGES[$stage];
    $tone = $stage === 'mislukt' ? \App\View\PlanLabels::GROUP_TONE['wacht']['badge'] : \App\View\PlanLabels::GROUP_TONE[$info['group']]['badge'];
@endphp
<span class="inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $tone }}">{{ $stage === 'pauze' && $coachingStatus === 'gestopt' ? 'Gestopt' : $info['label'] }}</span>

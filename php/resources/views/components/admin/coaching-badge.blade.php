@props(['status'])
<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ \App\Services\AdminLabels::COACHING_TONE[$status] ?? '' }}">{{ \App\Services\AdminLabels::coaching($status) }}</span>

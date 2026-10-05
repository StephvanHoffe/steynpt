@props(['name', 'strokeWidth' => null])
{{-- Lucide-icoon, bijvoorbeeld <x-icon name="ArrowRight" class="size-4" /> --}}
{!! \App\View\Icon::svg($name, $attributes->get('class', ''), array_filter(['stroke-width' => $strokeWidth] + $attributes->except('class')->getAttributes(), fn ($v) => $v !== null)) !!}

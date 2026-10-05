@props(['seconds' => 4])
{{-- Ververst de pagina periodiek zolang er een concept gegenereerd wordt. --}}
<div hidden x-data x-init="setTimeout(() => window.location.reload(), {{ (int) $seconds * 1000 }})"></div>

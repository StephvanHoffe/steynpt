@props(['english' => false, 'clientEnglish' => false])
{{-- Taal van het AI-concept. Uitgevinkt gaat het verborgen veld 'nl' mee; aangevinkt overschrijft 'en' dat. --}}
<div {{ $attributes }}>
  <input type="hidden" name="language" value="nl">
  <label class="flex cursor-pointer items-start gap-2 text-sm">
    <input type="checkbox" name="language" value="en" @checked($english) class="mt-0.5 size-4 shrink-0 accent-ink">
    <span>
      <span class="font-medium">Schema in het Engels maken</span>
      <span class="block text-xs text-muted">{{ $clientEnglish ? 'Deze klant gebruikt de site in het Engels.' : 'De AI schrijft het hele schema in het Engels; je instructie mag in het Nederlands.' }}</span>
    </span>
  </label>
</div>

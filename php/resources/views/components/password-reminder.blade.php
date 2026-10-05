@props(['user', 'next'])
{{-- Een week van tevoren melden dat het wachtwoord verloopt (daarna is een nieuw wachtwoord verplicht). --}}
@php $days = \App\Support\Totp::passwordDaysLeft(\App\Auth\Accounts::passwordChangedAt($user)); @endphp
@if ($days <= 7 && $days > 0)
  <div class="border-b border-[#f0dcb4] bg-[#fdf3e1] text-sm text-[#5c3305] print:hidden">
    <div class="mx-auto flex max-w-[1400px] flex-wrap items-center gap-x-3 gap-y-1 px-4 py-2 sm:px-6 lg:px-8">
      <x-icon name="KeyRound" class="size-4 shrink-0" />
      <span>{{ $days === 1 ? __('Je wachtwoord verloopt morgen.') : __('Je wachtwoord verloopt over :days dagen.', ['days' => $days]) }} {{ __('Om de 8 weken kies je een nieuw wachtwoord.') }}</span>
      <a href="/wachtwoord-vernieuwen?next={{ rawurlencode($next) }}" class="font-semibold underline underline-offset-4">{{ __('Nu vernieuwen') }}</a>
    </div>
  </div>
@endif

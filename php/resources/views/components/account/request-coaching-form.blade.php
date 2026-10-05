@props(['currentPlan' => null, 'plans'])
@if (session('coaching_success'))
  <x-form.alert :success="session('coaching_success')" />
@else
  <form method="post" action="/account/coaching" class="grid gap-3">
    @csrf
    <x-form.alert :error="session('coaching_error')" />
    <div class="grid gap-2 sm:grid-cols-3">
      @foreach ($plans as $plan)
        <label class="flex cursor-pointer flex-col rounded-xl border-[1.5px] border-line bg-white p-4 transition-colors has-[:checked]:border-ink has-[:checked]:bg-surface">
          <input type="radio" name="plan" value="{{ $plan['id'] }}" @checked(($currentPlan ?? 'online-pro') === $plan['id']) class="sr-only">
          <span class="font-semibold">{{ $plan['name'] }}</span>
          <span class="text-sm text-muted">{{ __('€ :price per maand', ['price' => \App\Site\Locale::price($plan['price'])]) }}</span>
        </label>
      @endforeach
    </div>
    <x-form.submit class="btn btn-primary w-full sm:w-auto" :pending-text="__('Aanvragen…')">{{ __('Start online coaching') }}</x-form.submit>
  </form>
@endif

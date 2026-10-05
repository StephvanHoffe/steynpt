@props(['error' => null, 'success' => null])
@if ($error || $success)
  <div role="{{ $error ? 'alert' : 'status' }}" {{ $attributes->class(['flex gap-3 rounded-xl border p-4 text-sm', $error ? 'border-danger/30 bg-danger/5 text-danger' : 'border-success/30 bg-success/5 text-success']) }}>
    <x-icon :name="$error ? 'CircleAlert' : 'CircleCheck'" class="size-5 shrink-0" />
    <p>{{ $error ?? $success }}</p>
  </div>
@endif

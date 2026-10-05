@props(['url', 'code', 'firstName', 'friendReward'])
@php
    $message = "Ik train bij SteynPT en dat bevalt top! Via mijn link krijg je {$friendReward} bij Steyn: {$url}";
    $enc = fn (string $s) => \App\Support\Js::encodeURIComponent($s);
@endphp
<div x-data="{
    copied: false,
    url: @js($url),
    copy() {
      navigator.clipboard.writeText(this.url)
        .then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000) })
        .catch(() => window.prompt('Kopieer je link:', this.url));
    },
    share() {
      if (navigator.share) navigator.share({ title: 'SteynPT online coaching', text: @js($message), url: this.url }).catch(() => {});
      else this.copy();
    },
  }">
  <div class="flex items-center gap-2 rounded-lg border border-line bg-white p-1.5 pl-3">
    <span class="w-0 min-w-0 flex-1 truncate font-mono text-sm" title="{{ $url }}">{{ preg_replace('#^https?://#', '', $url) }}</span>
    <button type="button" @click="copy()" class="btn btn-sm btn-ink shrink-0" aria-live="polite">
      <span x-show="!copied" class="contents"><x-icon name="Copy" class="size-4" />Kopieer</span>
      <span x-show="copied" x-cloak class="contents"><x-icon name="Check" class="size-4" />Gekopieerd</span>
    </button>
  </div>
  <p class="mt-2 text-xs text-muted">Of deel je code: <strong class="font-mono">{{ $code }}</strong></p>
  <div class="mt-4 flex flex-wrap gap-2">
    <a href="https://wa.me/?text={{ $enc($message) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline flex-1"><x-icon name="MessageCircle" class="size-4" /> WhatsApp</a>
    <a href="mailto:?subject={{ $enc("{$firstName} nodigt je uit voor SteynPT") }}&amp;body={{ $enc($message) }}" class="btn btn-sm btn-outline flex-1"><x-icon name="Mail" class="size-4" /> E-mail</a>
    <button type="button" @click="share()" class="btn btn-sm btn-outline flex-1"><x-icon name="Share2" class="size-4" /> Delen</button>
  </div>
</div>

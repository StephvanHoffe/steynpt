"use client";

import { Check, Copy, Mail, MessageCircle, Share2 } from "lucide-react";
import { useState } from "react";

export function ReferralShare({ url, code, firstName, friendReward }: { url: string; code: string; firstName: string; friendReward: string }) {
  const [copied, setCopied] = useState(false);
  const message = `Ik train met SteynPT en dat bevalt top! Via mijn link krijg je ${friendReward} bij Steyn: ${url}`;

  async function copy() {
    try {
      await navigator.clipboard.writeText(url);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      window.prompt("Kopieer je link:", url);
    }
  }

  async function share() {
    if (navigator.share) {
      try {
        await navigator.share({ title: "SteynPT online coaching", text: message, url });
      } catch {
        // Delen geannuleerd.
      }
    } else {
      copy();
    }
  }

  return (
    <div>
      <div className="flex items-center gap-2 rounded-lg border border-line bg-white p-1.5 pl-3">
        <span className="w-0 min-w-0 flex-1 truncate font-mono text-sm" title={url}>
          {url.replace(/^https?:\/\//, "")}
        </span>
        <button type="button" onClick={copy} className="btn btn-sm btn-ink shrink-0" aria-live="polite">
          {copied ? <Check className="size-4" aria-hidden="true" /> : <Copy className="size-4" aria-hidden="true" />}
          {copied ? "Gekopieerd" : "Kopieer"}
        </button>
      </div>
      <p className="mt-2 text-xs text-muted">
        Of deel je code: <strong className="font-mono">{code}</strong>
      </p>
      <div className="mt-4 flex flex-wrap gap-2">
        <a
          href={`https://wa.me/?text=${encodeURIComponent(message)}`}
          target="_blank"
          rel="noopener noreferrer"
          className="btn btn-sm btn-outline flex-1"
        >
          <MessageCircle className="size-4" aria-hidden="true" /> WhatsApp
        </a>
        <a
          href={`mailto:?subject=${encodeURIComponent(`${firstName} nodigt je uit voor SteynPT`)}&body=${encodeURIComponent(message)}`}
          className="btn btn-sm btn-outline flex-1"
        >
          <Mail className="size-4" aria-hidden="true" /> E-mail
        </a>
        <button type="button" onClick={share} className="btn btn-sm btn-outline flex-1">
          <Share2 className="size-4" aria-hidden="true" /> Delen
        </button>
      </div>
    </div>
  );
}

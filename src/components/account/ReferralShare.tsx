"use client";

import { Check, Copy, Mail, MessageCircle, Share2 } from "lucide-react";
import { useState } from "react";

export function ReferralShare({ url, code, firstName }: { url: string; code: string; firstName: string }) {
  const [copied, setCopied] = useState(false);
  const message = `Ik train met SteynPT en dat bevalt top! Via mijn link krijg je extra welkomstpunten bij online coaching van Steyn: ${url}`;

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
      <div className="flex items-center gap-2 rounded-xl border-[1.5px] border-ink/15 bg-white p-1.5 pl-4">
        <span className="min-w-0 flex-1 truncate font-mono text-sm" title={url}>
          {url.replace(/^https?:\/\//, "")}
        </span>
        <button type="button" onClick={copy} className="btn btn-sm btn-ink shrink-0" aria-live="polite">
          {copied ? <Check className="size-4" aria-hidden="true" /> : <Copy className="size-4" aria-hidden="true" />}
          {copied ? "Gekopieerd" : "Kopieer"}
        </button>
      </div>
      <p className="mt-2 text-xs text-ink/70">
        Of deel je code: <strong className="font-mono">{code}</strong>
      </p>
      <div className="mt-4 grid grid-cols-3 gap-2">
        <a
          href={`https://wa.me/?text=${encodeURIComponent(message)}`}
          target="_blank"
          rel="noopener noreferrer"
          className="btn btn-sm border-[1.5px] border-ink/20 bg-white"
        >
          <MessageCircle className="size-4" aria-hidden="true" /> WhatsApp
        </a>
        <a
          href={`mailto:?subject=${encodeURIComponent(`${firstName} nodigt je uit voor SteynPT`)}&body=${encodeURIComponent(message)}`}
          className="btn btn-sm border-[1.5px] border-ink/20 bg-white"
        >
          <Mail className="size-4" aria-hidden="true" /> E-mail
        </a>
        <button type="button" onClick={share} className="btn btn-sm border-[1.5px] border-ink/20 bg-white">
          <Share2 className="size-4" aria-hidden="true" /> Delen
        </button>
      </div>
    </div>
  );
}

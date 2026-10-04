import { KeyRound } from "lucide-react";
import Link from "next/link";
import type { User } from "@/lib/db";
import { isDemoAccount } from "@/lib/demo";
import { passwordDaysLeft } from "@/lib/totp";

/** Een week van tevoren melden dat het wachtwoord verloopt (daarna is een nieuw wachtwoord verplicht). */
export function PasswordReminder({ user, next }: { user: Pick<User, "email" | "passwordChangedAt" | "createdAt">; next: string }) {
  if (isDemoAccount(user.email)) return null;
  const days = passwordDaysLeft(user.passwordChangedAt ?? user.createdAt);
  if (days > 7 || days <= 0) return null;
  return (
    <div className="border-b border-[#f0dcb4] bg-[#fdf3e1] text-sm text-[#5c3305] print:hidden">
      <div className="mx-auto flex max-w-[1400px] flex-wrap items-center gap-x-3 gap-y-1 px-4 py-2 sm:px-6 lg:px-8">
        <KeyRound className="size-4 shrink-0" aria-hidden="true" />
        <span>
          Je wachtwoord verloopt {days === 1 ? "morgen" : `over ${days} dagen`}. Om de 8 weken kies je een nieuw wachtwoord.
        </span>
        <Link href={`/wachtwoord-vernieuwen?next=${encodeURIComponent(next)}`} className="font-semibold underline underline-offset-4">
          Nu vernieuwen
        </Link>
      </div>
    </div>
  );
}

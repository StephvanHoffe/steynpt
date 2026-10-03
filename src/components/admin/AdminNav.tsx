"use client";

import { CalendarDays, Dumbbell, Inbox, LayoutDashboard, Salad, Settings2, Users } from "lucide-react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { useEffect, useRef } from "react";
import type { AdminCounts } from "@/lib/admin";

const ITEMS = [
  { href: "/admin", label: "Overzicht", icon: LayoutDashboard, exact: true },
  { href: "/admin/agenda", label: "Agenda", icon: CalendarDays, exclude: "/admin/agenda/instellingen" },
  { href: "/admin/leden", label: "Leden", icon: Users, badge: "applied" as const },
  { href: "/admin/trainingsschemas", label: "Trainingsschema's", icon: Dumbbell, badge: "training" as const },
  { href: "/admin/voedingsschemas", label: "Voedingsschema's", icon: Salad, badge: "voeding" as const },
  { href: "/admin/aanvragen", label: "Aanvragen", icon: Inbox, badge: "requests" as const },
  { href: "/admin/agenda/instellingen", label: "Instellingen", icon: Settings2 },
];

const BADGE_TITLE = { applied: "coaching aangevraagd", training: "te maken of te controleren", voeding: "te maken of te controleren", requests: "open" };

export function AdminNav({ counts }: { counts: AdminCounts }) {
  const pathname = usePathname();
  const activeRef = useRef<HTMLAnchorElement>(null);
  // Op de telefoon scrolt de balk horizontaal: houd het actieve onderdeel in beeld.
  useEffect(() => activeRef.current?.scrollIntoView({ block: "nearest", inline: "nearest" }), [pathname]);
  const isActive = (item: (typeof ITEMS)[number]) =>
    item.exact ? pathname === item.href : pathname.startsWith(item.href) && !("exclude" in item && item.exclude && pathname.startsWith(item.exclude));

  return (
    <div className="lg:w-60 lg:shrink-0 lg:border-r lg:border-line lg:bg-white print:hidden">
      <nav aria-label="Beheer" className="lg:sticky lg:top-14">
        <ul className="relative flex gap-1 overflow-x-auto border-b border-line bg-white px-3 py-2 lg:flex-col lg:overflow-visible lg:border-0 lg:px-3 lg:py-4">
          {ITEMS.map((item) => {
            const active = isActive(item);
            const n = item.badge ? counts[item.badge] : 0;
            return (
              <li key={item.href} className="shrink-0">
                <Link
                  ref={active ? activeRef : undefined}
                  href={item.href}
                  aria-current={active ? "page" : undefined}
                  className={`relative flex items-center gap-2.5 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                    active ? "bg-ink text-white" : "text-ink/80 hover:bg-surface hover:text-ink"
                  }`}
                >
                  <item.icon className="size-4 shrink-0" aria-hidden="true" />
                  <span className="flex-1">{item.label}</span>
                  {n > 0 && (
                    <span
                      className={`min-w-5 rounded-full px-1.5 text-center text-xs font-semibold tabular-nums ${active ? "bg-white text-ink" : "bg-accent text-white"}`}
                      title={`${n} ${BADGE_TITLE[item.badge!]}`}
                    >
                      {n}
                      <span className="sr-only"> {BADGE_TITLE[item.badge!]}</span>
                    </span>
                  )}
                </Link>
              </li>
            );
          })}
        </ul>
      </nav>
    </div>
  );
}

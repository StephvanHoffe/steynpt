import { ArrowLeft } from "lucide-react";
import Link from "next/link";
import type { ReactNode } from "react";
import type { CoachingStatus } from "@/lib/db";
import { COACHING_LABEL } from "./labels";

export { COACHING_LABEL };

/** Breedte en marges van een beheerpagina. */
export const ADMIN_PAGE = "mx-auto w-full max-w-[1400px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8";

export function AdminPageHeader({
  title,
  description,
  back,
  actions,
}: {
  title: ReactNode;
  description?: ReactNode;
  back?: { href: string; label: string };
  actions?: ReactNode;
}) {
  return (
    <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div className="min-w-0">
        {back && (
          <Link href={back.href} className="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink">
            <ArrowLeft className="size-4" aria-hidden="true" /> {back.label}
          </Link>
        )}
        <h1 className="display text-3xl sm:text-4xl">{title}</h1>
        {description && <div className="mt-1.5 text-sm text-muted">{description}</div>}
      </div>
      {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
    </header>
  );
}



const COACHING_TONE: Record<CoachingStatus, string> = {
  geen: "bg-surface text-muted",
  aangevraagd: "bg-accent-tint text-accent",
  actief: "bg-ink text-white",
  gepauzeerd: "bg-surface text-ink",
  gestopt: "bg-surface text-muted line-through decoration-1",
};

export function CoachingBadge({ status }: { status: CoachingStatus }) {
  return <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${COACHING_TONE[status]}`}>{COACHING_LABEL[status]}</span>;
}

/** Leeg-staat binnen een kaart of lijst. */
export function EmptyState({ children }: { children: ReactNode }) {
  return <p className="rounded-xl border border-dashed border-line bg-white px-5 py-8 text-center text-sm text-muted">{children}</p>;
}

import { ExternalLink, LogOut } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { AdminNav } from "@/components/admin/AdminNav";
import { LogoMark } from "@/components/Logo";
import { PasswordReminder } from "@/components/PasswordReminder";
import { adminCounts } from "@/lib/admin";
import { getCurrentUser } from "@/lib/auth";

export const metadata: Metadata = {
  title: { default: "Beheer", template: "%s · Beheer SteynPT" },
  robots: { index: false },
};

/** Eigen opmaak voor het beheer: bovenbalk en zijbalk in plaats van de websitekop. */
export default async function AdminLayout({ children }: LayoutProps<"/admin">) {
  // Elke pagina doet zelf requireAdmin(); zonder beheerder alleen de inhoud (die stuurt door).
  const user = await getCurrentUser();
  if (!user || user.role !== "admin") return <main className="flex-1">{children}</main>;
  const counts = await adminCounts();

  return (
    <div className="flex flex-1 flex-col bg-surface">
      <header className="sticky top-0 z-40 flex h-14 items-center justify-between gap-4 border-b border-line bg-white px-4 sm:px-6 print:hidden">
        <Link href="/admin" className="flex items-center gap-2.5">
          <LogoMark className="h-7 w-auto" />
          <span className="font-display text-[15px] font-semibold tracking-wide">
            SteynPT <span className="font-normal text-muted">Beheer</span>
          </span>
        </Link>
        <div className="flex items-center gap-1 text-sm">
          <Link href="/" className="hidden items-center gap-1.5 rounded-md px-3 py-2 font-medium text-muted hover:bg-surface hover:text-ink sm:inline-flex">
            Naar website <ExternalLink className="size-3.5" aria-hidden="true" />
          </Link>
          <form action="/uitloggen" method="post">
            <button type="submit" className="inline-flex items-center gap-1.5 rounded-md px-3 py-2 font-medium text-muted hover:bg-surface hover:text-ink">
              <LogOut className="size-4" aria-hidden="true" /> <span className="hidden sm:inline">Uitloggen</span>
            </button>
          </form>
        </div>
      </header>
      <PasswordReminder user={user} next="/admin" />
      <div className="flex flex-1 flex-col lg:flex-row">
        <AdminNav counts={counts} />
        <main id="inhoud" className="min-w-0 flex-1">
          {children}
        </main>
      </div>
    </div>
  );
}

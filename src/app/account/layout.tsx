import { LayoutDashboard, LogOut, Shield, UserRound } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { getCurrentUser } from "@/lib/auth";

export const metadata: Metadata = {
  title: "Mijn account",
  robots: { index: false },
};

export default async function AccountLayout({ children }: LayoutProps<"/account">) {
  // Elke pagina doet zelf requireUser() met het juiste terugkeerpad.
  const user = await getCurrentUser();
  if (!user) return children;
  return (
    <div className="bg-paper">
      <div className="border-b border-line bg-white print:hidden">
        <div className="container-site flex items-center justify-between gap-4 overflow-x-auto py-2">
          <nav aria-label="Account" className="flex gap-1">
            <Link href="/account" className="inline-flex items-center gap-2 whitespace-nowrap rounded-full px-3 py-2 text-sm font-medium hover:bg-sand">
              <LayoutDashboard className="size-4" aria-hidden="true" /> Dashboard
            </Link>
            <Link href="/account/profiel" className="inline-flex items-center gap-2 whitespace-nowrap rounded-full px-3 py-2 text-sm font-medium hover:bg-sand">
              <UserRound className="size-4" aria-hidden="true" /> Profiel
            </Link>
            {user.role === "admin" && (
              <Link href="/admin" className="inline-flex items-center gap-2 whitespace-nowrap rounded-full px-3 py-2 text-sm font-medium hover:bg-sand">
                <Shield className="size-4" aria-hidden="true" /> Beheer
              </Link>
            )}
          </nav>
          <form action="/uitloggen" method="post">
            <button type="submit" className="inline-flex items-center gap-2 whitespace-nowrap rounded-full px-3 py-2 text-sm font-medium text-muted hover:bg-sand hover:text-ink">
              <LogOut className="size-4" aria-hidden="true" /> Uitloggen
            </button>
          </form>
        </div>
      </div>
      {children}
    </div>
  );
}

import type { ReactNode } from "react";
import { CheckList } from "./ui";
import { LogoMark } from "./Logo";

export function AuthShell({ title, intro, aside, children }: { title: string; intro?: ReactNode; aside: { title: string; items: string[] }; children: ReactNode }) {
  return (
    <section className="bg-paper">
      <div className="container-site grid gap-10 py-12 lg:grid-cols-[1fr_1.15fr] lg:gap-16 lg:py-20">
        <aside className="grain relative order-2 overflow-hidden rounded-[1.5rem] bg-ink p-8 text-paper lg:order-1 lg:p-12">
          <LogoMark className="pointer-events-none absolute -bottom-16 -right-8 h-80 w-auto opacity-[0.06]" />
          <p className="eyebrow text-volt">SteynPT account</p>
          <h2 className="display display-md mt-4">{aside.title}</h2>
          <div className="mt-8">
            <CheckList tone="dark" items={aside.items} />
          </div>
        </aside>
        <div className="order-1 lg:order-2">
          <h1 className="display display-lg">{title}</h1>
          {intro && <div className="mt-4 text-muted">{intro}</div>}
          <div className="mt-8">{children}</div>
        </div>
      </div>
    </section>
  );
}

import { Plus, Search } from "lucide-react";
import Link from "next/link";
import { ADMIN_PAGE, AdminPageHeader, EmptyState } from "@/components/admin/ui";
import type { PlanType } from "@/lib/db";
import { siteOrigin } from "@/lib/origin";
import { SOON_DAYS, STAGE_GROUPS, STAGES, relativeDay, type StageGroup } from "@/lib/plans/pipeline";
import { loadPlanPipeline, type PipelineRow } from "@/lib/plans/pipeline-server";
import { newPlanHref, PLAN_SECTION, planHref } from "@/lib/plans/sections";
import { getOnlinePlan } from "@/lib/site";
import { formatPlanDay, GROUP_TONE, StageBadge } from "./stage";

const sinceFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short", timeZone: "Europe/Amsterdam" });
const isGroup = (v: unknown): v is StageGroup => STAGE_GROUPS.some((g) => g.id === v);

/** Wat is de logische volgende stap voor deze klant? */
function nextStep(row: PipelineRow, type: PlanType, origin: string): { label: string; href: string; primary?: boolean } | null {
  const section = PLAN_SECTION[type];
  switch (row.stage) {
    case "eerste":
      return { label: "Schema maken", href: newPlanHref(type, row.member.id), primary: true };
    case "verlopen":
      return { label: "Nieuw schema", href: newPlanHref(type, row.member.id), primary: true };
    case "binnenkort":
      return { label: "Nu voorbereiden", href: newPlanHref(type, row.member.id) };
    case "controleren":
      return { label: "Controleren", href: planHref(type, row.open!.id), primary: true };
    case "mislukt":
      return { label: "Opnieuw proberen", href: planHref(type, row.open!.id), primary: true };
    case "bezig":
      return { label: "Bekijken", href: planHref(type, row.open!.id) };
    case "gepland":
      return { label: "Ingepland bekijken", href: planHref(type, row.scheduled!.id) };
    case "intake": {
      const body = `Hoi ${row.member.firstName},\n\nWil je je intake invullen? Dan maak ik je ${section.one} op maat.\n\n${origin}/account/intake\n\nGroet,\nSteyn`;
      return { label: "Herinnering mailen", href: `mailto:${row.member.email}?subject=${encodeURIComponent("Je intake voor je schema")}&body=${encodeURIComponent(body)}` };
    }
    default:
      return row.current ? { label: "Bekijken", href: planHref(type, row.current.id) } : null;
  }
}

function Due({ row, today }: { row: PipelineRow; today: string }) {
  if (!row.dueOn) return <span className="text-muted">–</span>;
  // Bij gepauzeerde coaching is een verstreken datum geen actiepunt.
  const late = row.dueOn <= today && row.stage !== "pauze";
  return (
    <span className="whitespace-nowrap">
      <span className={`font-medium tabular-nums ${late ? "text-danger" : ""}`}>{formatPlanDay(row.dueOn)}</span>
      <span className={`block text-xs ${late ? "text-danger" : "text-muted"}`}>
        {row.stage === "gepland" ? `start ${relativeDay(today, row.dueOn)}` : relativeDay(today, row.dueOn)}
      </span>
    </span>
  );
}

function Current({ row, type }: { row: PipelineRow; type: PlanType }) {
  if (!row.current) return <span className="text-muted">{row.stage === "intake" ? "Intake nog niet ingevuld" : "Nog geen schema"}</span>;
  return (
    <Link href={planHref(type, row.current.id)} className="block min-w-0 hover:underline">
      <span className="block truncate">{row.current.title || PLAN_SECTION[type].one}</span>
      <span className="block text-xs text-muted">sinds {sinceFmt.format(row.current.publishedAt)}</span>
    </Link>
  );
}

export async function PlanOverview({ type, searchParams }: { type: PlanType; searchParams: Record<string, string | string[] | undefined> }) {
  const section = PLAN_SECTION[type];
  const q = typeof searchParams.q === "string" ? searchParams.q.trim() : "";
  const fase = isGroup(searchParams.fase) ? searchParams.fase : null;
  const [{ today, rows: all }, origin] = await Promise.all([loadPlanPipeline(), siteOrigin()]);

  const needle = q.toLowerCase();
  const searched = all[type].filter((r) => !needle || `${r.member.firstName} ${r.member.lastName} ${r.member.email}`.toLowerCase().includes(needle));
  const rows = fase ? searched.filter((r) => STAGES[r.stage].group === fase) : searched;
  const countFor = (g: StageGroup | null) => (g ? searched.filter((r) => STAGES[r.stage].group === g).length : searched.length);
  const href = (g: StageGroup | null) => {
    const p = new URLSearchParams();
    if (g) p.set("fase", g);
    if (q) p.set("q", q);
    const s = p.toString();
    return `${section.href}${s ? `?${s}` : ""}`;
  };

  return (
    <div className={ADMIN_PAGE}>
      <AdminPageHeader
        title={section.title}
        description="Alle klanten met coaching of een schema: in welke fase ze zitten en wie toe is aan een nieuw schema."
        actions={
          <Link href={newPlanHref(type)} className="btn btn-sm btn-primary">
            <Plus className="size-4" aria-hidden="true" /> Nieuw {section.one}
          </Link>
        }
      />

      {/* Fases als filter */}
      <nav aria-label="Filter op fase" className="mb-5 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
        {STAGE_GROUPS.map((g) => {
          const active = fase === g.id;
          const n = countFor(g.id);
          return (
            <Link
              key={g.id}
              href={active ? href(null) : href(g.id)}
              aria-current={active ? "page" : undefined}
              className={`rounded-xl border p-3.5 transition-colors ${active ? "border-ink bg-ink text-white" : "border-line bg-white hover:border-ink/40"}`}
            >
              <span className="flex items-baseline gap-2 text-sm font-medium leading-snug">
                <span className={`size-2 shrink-0 -translate-y-px rounded-full ${GROUP_TONE[g.id].dot}`} aria-hidden="true" />
                {g.label}
              </span>
              <span className="mt-1 block text-2xl font-semibold tabular-nums">{n}</span>
              <span className={`block text-xs leading-snug ${active ? "text-white/70" : "text-muted"}`}>{g.hint}</span>
            </Link>
          );
        })}
      </nav>

      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p className="text-sm text-muted">
          {fase ? (
            <>
              {STAGE_GROUPS.find((g) => g.id === fase)!.label}: {rows.length} {rows.length === 1 ? "klant" : "klanten"} ·{" "}
              <Link href={href(null)} className="font-semibold text-accent hover:underline">
                alle {countFor(null)} tonen
              </Link>
            </>
          ) : (
            <>
              {rows.length} {rows.length === 1 ? "klant" : "klanten"}, meest dringende bovenaan
            </>
          )}
        </p>
        <form action={section.href} method="get" className="relative w-full sm:w-72">
          {fase && <input type="hidden" name="fase" value={fase} />}
          <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" aria-hidden="true" />
          <label htmlFor="zoek" className="sr-only">
            Zoek op naam of e-mail
          </label>
          <input id="zoek" name="q" defaultValue={q} placeholder="Zoek op naam of e-mail" className="input h-10 pl-9" />
        </form>
      </div>

      {rows.length === 0 ? (
        <EmptyState>{q ? `Geen klanten gevonden voor “${q}”.` : fase ? "Niemand in deze fase." : "Nog geen klanten met coaching of een schema."}</EmptyState>
      ) : (
        <div className="overflow-hidden rounded-xl border border-line bg-white">
          {/* Tabel op grotere schermen */}
          <table className="hidden w-full table-fixed text-left text-sm lg:table">
            <caption className="sr-only">{section.title} per klant</caption>
            <colgroup>
              <col className="w-[26%]" />
              <col className="w-[17%]" />
              <col className="w-[25%]" />
              <col className="w-[14%]" />
              <col className="w-[18%]" />
            </colgroup>
            <thead className="border-b border-line bg-[#f6f7f8] text-xs uppercase tracking-wide text-muted">
              <tr>
                <th className="px-4 py-2.5 font-semibold">Klant</th>
                <th className="px-4 py-2.5 font-semibold">Fase</th>
                <th className="px-4 py-2.5 font-semibold">Huidig schema</th>
                <th className="px-4 py-2.5 font-semibold">Nieuw schema op</th>
                <th className="px-4 py-2.5 font-semibold">
                  <span className="sr-only">Volgende stap</span>
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {rows.map((r) => {
                const step = nextStep(r, type, origin);
                return (
                  <tr key={r.member.id} className="align-middle hover:bg-surface/60">
                    <td className="px-4 py-3">
                      <Link href={`/admin/leden/${r.member.id}`} className="block truncate font-semibold hover:underline">
                        {r.member.firstName} {r.member.lastName}
                      </Link>
                      <span className="block truncate text-xs text-muted">{getOnlinePlan(r.member.plan)?.name ? `Online ${getOnlinePlan(r.member.plan)!.name}` : r.member.email}</span>
                    </td>
                    <td className="px-4 py-3">
                      <StageBadge stage={r.stage} coachingStatus={r.member.coachingStatus} />
                      {r.open && r.current && <span className="mt-1 block text-xs text-muted">vervangt huidig schema</span>}
                    </td>
                    <td className="px-4 py-3">
                      <Current row={r} type={type} />
                    </td>
                    <td className="px-4 py-3">
                      <Due row={r} today={today} />
                    </td>
                    <td className="px-4 py-3 text-right">
                      {step && (
                        <Link href={step.href} className={`btn btn-sm ${step.primary ? "btn-primary" : "btn-outline"}`}>
                          {step.label}
                        </Link>
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>

          {/* Kaarten op tablet en telefoon */}
          <ul className="divide-y divide-line lg:hidden">
            {rows.map((r) => {
              const step = nextStep(r, type, origin);
              return (
                <li key={r.member.id} className="grid gap-2 px-4 py-3.5">
                  <div className="flex items-start justify-between gap-3">
                    <Link href={`/admin/leden/${r.member.id}`} className="min-w-0 font-semibold hover:underline">
                      {r.member.firstName} {r.member.lastName}
                    </Link>
                    <StageBadge stage={r.stage} coachingStatus={r.member.coachingStatus} />
                  </div>
                  <p className="text-sm text-muted">
                    {r.current ? `Schema sinds ${sinceFmt.format(r.current.publishedAt)}` : r.stage === "intake" ? "Intake nog niet ingevuld" : "Nog geen schema"}
                    {r.dueOn && (
                      <>
                        {r.stage === "gepland" ? " · nieuw schema start " : " · nieuw schema "}
                        <span className={r.dueOn <= today && r.stage !== "pauze" ? "font-semibold text-danger" : "text-ink"}>
                          {formatPlanDay(r.dueOn)} ({relativeDay(today, r.dueOn)})
                        </span>
                      </>
                    )}
                  </p>
                  {step && (
                    <Link href={step.href} className={`btn btn-sm justify-self-start ${step.primary ? "btn-primary" : "btn-outline"}`}>
                      {step.label}
                    </Link>
                  )}
                </li>
              );
            })}
          </ul>
        </div>
      )}
      <p className="mt-4 text-xs text-muted">
        Komende week: het nieuwe schema is binnen {SOON_DAYS} dagen nodig. De datum stel je in bij het publiceren (standaard de looptijd van het schema) en kun je later aanpassen.
      </p>
    </div>
  );
}

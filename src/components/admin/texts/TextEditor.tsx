"use client";

import { ArrowDown, ArrowUp, CircleAlert, CircleCheck, LoaderCircle, Plus, RotateCcw, Trash2, Undo2 } from "lucide-react";
import Link from "next/link";
import { useActionState, useEffect, useMemo, useRef, useState, type ReactNode } from "react";
import { Rich } from "@/components/content/Rich";
import { saveSiteTextsAction } from "@/lib/actions/site-texts";
import type { SiteTextsState } from "@/lib/actions/types";
import type { Field, ItemsField, PageDef, RawValues, SubField } from "@/lib/content/fields";
import { fillText, placeholdersIn } from "@/lib/content/markup";
import { sameValue } from "@/lib/content/values";
import { computeVars, type VarInfo } from "@/lib/content/vars";

// Bewerkscherm voor de teksten van één pagina. Alle teksten gaan als JSON naar saveSiteTextsAction en worden daar
// opnieuw gecontroleerd (src/lib/content/values.ts).

type Props = {
  page: PageDef;
  initial: RawValues;
  /** Huidige teksten van de gedeelde pagina's, voor de automatische waarden in de voorvertoning. */
  shared: { algemeen: RawValues; pakketten: RawValues };
  vars: VarInfo[];
  links: { href: string; label: string }[];
  /** Per aangepast veld ("onderdeel.veld"): wanneer en door wie het is opgeslagen. */
  changedAt: Record<string, string>;
};

type Ctx = { vars: Record<string, string>; links: Props["links"]; errorAt: (path: string) => string | undefined };

const fieldId = (path: string) => `veld-${path.replace(/\./g, "-")}`;
const emptyItem = (field: ItemsField) => Object.fromEntries(Object.entries(field.fields).map(([k, sub]) => [k, structuredClone(sub.default)]));
const getAt = (values: RawValues, path: string): unknown => path.split(".").reduce<unknown>((v, k) => (v && typeof v === "object" ? (v as Record<string, unknown>)[k] : undefined), values);
const count = (n: number, one: string, many: string) => `${n} ${n === 1 ? one : many}`;
const LEAVE_WARNING = "Je hebt wijzigingen die nog niet zijn opgeslagen. Weet je zeker dat je deze pagina wilt verlaten?";

export function TextEditor({ page, initial, shared, vars: varInfo, links, changedAt }: Props) {
  const [values, setValues] = useState(initial);
  const [saved, setSaved] = useState(initial);
  const [submitted, setSubmitted] = useState<RawValues | null>(null);
  const [state, action, pending] = useActionState<SiteTextsState, FormData>(saveSiteTextsAction, {});
  const formRef = useRef<HTMLFormElement>(null);

  // Na opslaan: de opgeschoonde teksten van de server worden de nieuwe beginstand.
  const [handled, setHandled] = useState<number | undefined>();
  if (state.savedAt && state.savedAt !== handled) {
    setHandled(state.savedAt);
    const next = { ...values };
    for (const [s, fields] of Object.entries(state.saved ?? {})) next[s] = { ...next[s], ...fields };
    setValues(next);
    setSaved(next);
  }

  // Automatische waarden, met wat je op deze pagina zelf aan het aanpassen bent.
  const vars = useMemo(
    () =>
      computeVars(
        (page.slug === "algemeen" ? values : shared.algemeen) as Parameters<typeof computeVars>[0],
        (page.slug === "pakketten" ? values : shared.pakketten) as Parameters<typeof computeVars>[1],
      ),
    [page.slug, values, shared],
  );

  const dirty = useMemo(() => {
    const out: string[] = [];
    for (const [s, section] of Object.entries(page.sections))
      for (const f of Object.keys(section.fields)) if (!sameValue(values[s]?.[f], saved[s]?.[f])) out.push(`${s}.${f}`);
    return out;
  }, [page, values, saved]);
  const isDirty = dirty.length > 0;

  // Een foutmelding verdwijnt zodra je het veld na het opslaan hebt aangepast.
  const fieldErrors = state.fieldErrors ?? {};
  const errorAt = (path: string) => (fieldErrors[path] && submitted && sameValue(getAt(values, path), getAt(submitted, path)) ? fieldErrors[path] : undefined);
  const openErrors = Object.keys(fieldErrors).filter((p) => errorAt(p));
  const ctx: Ctx = { vars, links, errorAt };

  const setField = (s: string, f: string, value: unknown) => setValues((prev) => ({ ...prev, [s]: { ...prev[s], [f]: value } }));

  // Niet zomaar weg met niet-opgeslagen wijzigingen.
  useEffect(() => {
    if (!isDirty) return;
    const onBeforeUnload = (e: BeforeUnloadEvent) => e.preventDefault();
    const onClick = (e: MouseEvent) => {
      const a = (e.target as HTMLElement | null)?.closest?.("a[href]");
      if (!a || a.getAttribute("target") === "_blank" || a.getAttribute("href")?.startsWith("#")) return;
      if (!window.confirm(LEAVE_WARNING)) {
        e.preventDefault();
        e.stopPropagation();
      }
    };
    window.addEventListener("beforeunload", onBeforeUnload);
    document.addEventListener("click", onClick, true);
    return () => {
      window.removeEventListener("beforeunload", onBeforeUnload);
      document.removeEventListener("click", onClick, true);
    };
  }, [isDirty]);

  // Ctrl/Cmd + S slaat op.
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === "s") {
        e.preventDefault();
        formRef.current?.requestSubmit();
      }
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, []);

  // Na een mislukte poging: naar het eerste veld met een fout.
  useEffect(() => {
    if (!state.fieldErrors) return;
    const el = document.querySelector<HTMLElement>('[aria-invalid="true"]');
    el?.scrollIntoView({ block: "center", behavior: "smooth" });
    el?.focus({ preventScroll: true });
  }, [state]);

  const resetAll = () => {
    if (!window.confirm("Alle teksten op deze pagina terugzetten naar de standaardtekst? Dit wordt pas definitief als je opslaat.")) return;
    setValues(Object.fromEntries(Object.entries(page.sections).map(([s, sec]) => [s, Object.fromEntries(Object.entries(sec.fields).map(([f, field]) => [f, structuredClone(field.default)]))])));
  };
  const anyCustom = Object.entries(page.sections).some(([s, sec]) => Object.entries(sec.fields).some(([f, field]) => !sameValue(values[s]?.[f], field.default)));

  const sectionState = (s: string) => ({
    dirty: dirty.some((d) => d.startsWith(`${s}.`)),
    error: openErrors.some((p) => p.startsWith(`${s}.`)),
  });

  const usedVars = varInfo.filter((v) => JSON.stringify(values).includes(`{${v.key}}`) || v.page === page.slug);

  return (
    <form
      ref={formRef}
      action={action}
      onSubmit={() => setSubmitted(values)}
      className="grid grid-cols-1 gap-6 lg:grid-cols-[13rem_minmax(0,1fr)] xl:grid-cols-[15rem_minmax(0,48rem)]"
    >
      <input type="hidden" name="page" value={page.slug} />
      <input type="hidden" name="values" value={JSON.stringify(values)} />

      <aside className="min-w-0 lg:sticky lg:top-20 lg:self-start">
        <nav aria-label="Onderdelen van deze pagina">
          <p className="mb-2 hidden text-xs font-semibold uppercase tracking-[0.12em] text-muted lg:block">Onderdelen</p>
          <ul className="flex gap-1.5 overflow-x-auto pb-1 lg:flex-col lg:gap-0.5 lg:overflow-visible lg:pb-0">
            {Object.entries(page.sections).map(([s, section]) => {
              const st = sectionState(s);
              return (
                <li key={s} className="shrink-0">
                  <a
                    href={`#sectie-${s}`}
                    className="flex items-center justify-between gap-2 whitespace-nowrap rounded-full border border-line bg-white px-3 py-1.5 text-sm text-ink/80 hover:border-ink/40 hover:text-ink lg:rounded-lg lg:border-transparent lg:bg-transparent lg:hover:bg-white"
                  >
                    {section.title}
                    {st.error ? (
                      <CircleAlert className="size-3.5 text-danger" aria-label="bevat een fout" />
                    ) : st.dirty ? (
                      <span className="size-2 rounded-full bg-accent" title="Niet opgeslagen wijzigingen">
                        <span className="sr-only">niet opgeslagen</span>
                      </span>
                    ) : null}
                  </a>
                </li>
              );
            })}
          </ul>
        </nav>
        {usedVars.length > 0 && (
          <div className="mt-5 hidden rounded-xl border border-line bg-white p-4 text-xs lg:block">
            <p className="font-semibold text-ink">Automatische waarden</p>
            <p className="mt-1 text-muted">Deze codes worden op de site vervangen door:</p>
            <dl className="mt-3 grid gap-2">
              {usedVars.map((v) => (
                <div key={v.key}>
                  <dt className="font-mono text-[11px] text-ink">{`{${v.key}}`}</dt>
                  <dd className="text-muted">
                    {vars[v.key] || "(leeg)"}
                    {v.page !== page.slug && (
                      <>
                        {" · "}
                        <Link href={`/admin/teksten/${v.page}#sectie-${v.section}`} className="underline underline-offset-2 hover:text-ink">
                          aanpassen
                        </Link>
                      </>
                    )}
                  </dd>
                </div>
              ))}
            </dl>
          </div>
        )}
      </aside>

      <div className="grid min-w-0 gap-5">
        <fieldset disabled={pending} className="grid min-w-0 gap-5">
          <legend className="sr-only">Teksten van {page.title}</legend>
          {Object.entries(page.sections).map(([s, section]) => (
            <section key={s} id={`sectie-${s}`} aria-labelledby={`kop-${s}`} className="card scroll-mt-20 p-5 sm:p-6">
              <header className="mb-5">
                <h2 id={`kop-${s}`} className="text-lg font-semibold">
                  {section.title}
                </h2>
                {section.hint && <p className="mt-0.5 text-sm text-muted">{section.hint}</p>}
              </header>
              <div className="grid gap-6">
                {Object.entries(section.fields).map(([f, field]) => (
                  <FieldEditor
                    key={f}
                    path={`${s}.${f}`}
                    field={field}
                    value={values[s]?.[f]}
                    dirty={dirty.includes(`${s}.${f}`)}
                    changedAt={changedAt[`${s}.${f}`]}
                    onChange={(v) => setField(s, f, v)}
                    ctx={ctx}
                  />
                ))}
              </div>
            </section>
          ))}
        </fieldset>

        {anyCustom && (
          <p className="text-right">
            <button type="button" onClick={resetAll} className="inline-flex items-center gap-1.5 text-sm font-medium text-muted underline-offset-2 hover:text-ink hover:underline">
              <RotateCcw className="size-3.5" aria-hidden="true" /> Alle teksten op deze pagina terugzetten naar de standaardtekst
            </button>
          </p>
        )}

        <div className="sticky bottom-3 z-20">
          <div className="flex flex-col gap-2 rounded-xl border border-line bg-white/95 p-3 shadow-lg shadow-ink/10 backdrop-blur sm:flex-row sm:items-center sm:justify-between sm:gap-3 sm:pl-5">
            <SaveStatus pending={pending} dirty={dirty.length} state={state} openErrors={openErrors.length} path={page.path} />
            <div className="flex shrink-0 justify-end gap-2">
              {isDirty && (
                <button type="button" onClick={() => setValues(saved)} disabled={pending} className="btn btn-sm btn-outline bg-white">
                  <Undo2 className="size-4" aria-hidden="true" /> Ongedaan maken
                </button>
              )}
              <button type="submit" disabled={!isDirty || pending} className="btn btn-sm btn-primary min-w-28">
                {pending ? (
                  <>
                    <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> Opslaan…
                  </>
                ) : (
                  "Opslaan"
                )}
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>
  );
}

function SaveStatus({ pending, dirty, state, openErrors, path }: { pending: boolean; dirty: number; state: SiteTextsState; openErrors: number; path: string | null }) {
  let content: ReactNode;
  if (pending) content = <span className="text-muted">Bezig met opslaan…</span>;
  else if (state.error && openErrors > 0)
    content = (
      <span className="flex items-start gap-2 text-danger">
        <CircleAlert className="mt-0.5 size-4 shrink-0" aria-hidden="true" /> {state.error}
      </span>
    );
  else if (dirty > 0) content = <span className="font-medium">{count(dirty, "wijziging", "wijzigingen")} nog niet opgeslagen</span>;
  else if (state.success)
    content = (
      <span className="flex items-start gap-2 text-success">
        <CircleCheck className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
        <span>
          {state.success}
          {path && (
            <>
              {" "}
              <a href={path} target="_blank" rel="noopener noreferrer" className="font-semibold underline underline-offset-2">
                Bekijk de pagina
              </a>
            </>
          )}
        </span>
      </span>
    );
  else
    content = (
      <span className="text-muted">
        Pas een tekst aan en klik op Opslaan.<span className="hidden sm:inline"> Ctrl+S werkt ook.</span>
      </span>
    );
  return (
    <p role="status" className="min-w-0 flex-1 text-sm">
      {content}
    </p>
  );
}

function FieldEditor({
  path,
  field,
  value,
  dirty,
  changedAt,
  onChange,
  ctx,
}: {
  path: string;
  field: Field;
  value: unknown;
  dirty: boolean;
  changedAt?: string;
  onChange: (value: unknown) => void;
  ctx: Ctx;
}) {
  const custom = !sameValue(value, field.default);
  const error = ctx.errorAt(path);
  const reset = (
    <span className="flex shrink-0 items-center gap-2">
      {custom && (
        <span className="rounded-full bg-accent-tint px-2 py-0.5 text-[11px] font-semibold text-accent" title={changedAt ? `Opgeslagen op ${changedAt}` : "Nog niet opgeslagen"}>
          Aangepast
        </span>
      )}
      {custom && (
        <button
          type="button"
          onClick={() => onChange(structuredClone(field.default))}
          className="inline-flex items-center gap-1 text-xs font-medium text-muted hover:text-ink"
          aria-label={`${field.label}: standaardtekst terugzetten`}
        >
          <RotateCcw className="size-3" aria-hidden="true" /> Standaardtekst
        </button>
      )}
    </span>
  );

  return (
    <div className={`grid gap-1.5 border-l-2 pl-3 transition-colors ${dirty ? "border-accent" : "border-transparent"}`}>
      {field.kind === "check" ? (
        <div className="flex flex-wrap items-center justify-between gap-2">
          <label className="inline-flex items-center gap-2.5 text-sm font-semibold">
            <input type="checkbox" className="size-4 accent-ink" checked={value === true} onChange={(e) => onChange(e.target.checked)} />
            {field.label}
          </label>
          {reset}
        </div>
      ) : (
        <div className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
          <label htmlFor={fieldId(path)} className="text-sm font-semibold">
            {field.label}
          </label>
          {reset}
        </div>
      )}
      {field.kind === "items" ? (
        <ItemsEditor path={path} field={field} value={Array.isArray(value) ? (value as Record<string, unknown>[]) : []} onChange={onChange} ctx={ctx} />
      ) : field.kind !== "check" ? (
        <Control id={fieldId(path)} field={field} value={value} onChange={onChange} ctx={ctx} invalid={!!error} />
      ) : null}
      <FieldFoot field={field} value={value} ctx={ctx} />
      {error && <p className="field-error mt-0">{error}</p>}
    </div>
  );
}

/** Uitleg, tekenteller en voorvertoning onder een veld. */
function FieldFoot({ field, value, ctx }: { field: Field | SubField; value: unknown; ctx: Ctx }) {
  if (field.kind === "items" || field.kind === "check") return field.hint ? <p className="text-xs text-muted">{field.hint}</p> : null;
  const texts = Array.isArray(value) ? (value as string[]).filter((v) => v.trim()) : typeof value === "string" ? [value] : [];
  const max = field.kind === "line" || field.kind === "text" ? field.max : undefined;
  const length = typeof value === "string" ? value.trim().length : 0;
  const rich = field.kind === "line" && field.rich;
  const hint =
    field.hint ??
    (rich
      ? "Tussen *sterretjes* krijgt de tekst de accentkleur; Enter begint een nieuwe regel."
      : field.kind === "text" && field.paragraphs
        ? "Een lege regel begint een nieuwe alinea."
        : undefined);
  const unknown = [...new Set(texts.flatMap(placeholdersIn))].filter((k) => !(k in ctx.vars));
  const preview = texts.filter((t) => placeholdersIn(t).length > 0 || (rich && /[*\n]/.test(t)));

  return (
    <>
      {(hint || max || field.kind === "list") && (
        <p className="flex justify-between gap-3 text-xs text-muted">
          <span>{hint ?? (field.kind === "list" ? "Eén punt per regel." : "")}</span>
          {field.kind === "list" ? (
            <span className="shrink-0 tabular-nums">{count(texts.length, "punt", "punten")}</span>
          ) : max ? (
            <span className={`shrink-0 tabular-nums ${length > max ? "font-semibold text-danger" : ""}`}>
              {length}/{max}
            </span>
          ) : null}
        </p>
      )}
      {unknown.length > 0 && (
        <p className="text-xs font-medium text-danger">
          Onbekende code {unknown.map((u) => `{${u}}`).join(", ")}. Gebruik alleen de automatische waarden uit het lijstje.
        </p>
      )}
      {preview.length > 0 && unknown.length === 0 && (
        <div className="rounded-lg bg-surface px-3 py-2 text-xs text-muted">
          <span className="font-semibold text-ink">Op de site: </span>
          {preview.map((t, i) => (
            <span key={i} className="text-ink">
              {i > 0 && " · "}
              {rich ? <Rich text={fillText(t, ctx.vars)} /> : fillText(t, ctx.vars)}
            </span>
          ))}
        </div>
      )}
    </>
  );
}

function Control({ id, field, value, onChange, ctx, invalid }: { id: string; field: Field | SubField; value: unknown; onChange: (v: unknown) => void; ctx: Ctx; invalid: boolean }) {
  const text = typeof value === "string" ? value : "";
  const common = { id, "aria-invalid": invalid || undefined } as const;
  // Groeit mee met de tekst (field-sizing), met een redelijke minimale hoogte.
  const area = "input min-h-0 py-2 text-sm leading-relaxed [field-sizing:content]";
  switch (field.kind) {
    case "line":
      return field.rich ? (
        <textarea {...common} className={area} rows={Math.max(1, text.split("\n").length)} value={text} onChange={(e) => onChange(e.target.value)} />
      ) : (
        <input {...common} className="input min-h-10 py-2 text-sm" value={text} onChange={(e) => onChange(e.target.value)} />
      );
    case "text":
      return <textarea {...common} className={`${area} min-h-20`} rows={3} value={text} onChange={(e) => onChange(e.target.value)} />;
    case "list": {
      const lines = Array.isArray(value) ? (value as string[]) : [];
      return <textarea {...common} className={`${area} min-h-20`} rows={Math.max(3, lines.length)} value={lines.join("\n")} onChange={(e) => onChange(e.target.value.split("\n"))} />;
    }
    case "price":
      return (
        <div className="flex items-center gap-2">
          <span className="text-sm font-semibold" aria-hidden="true">
            €
          </span>
          <input {...common} className="input min-h-10 w-36 py-2 text-sm tabular-nums" inputMode="decimal" value={text} onChange={(e) => onChange(e.target.value)} />
        </div>
      );
    case "link":
      return (
        <select {...common} className="input min-h-10 py-2 text-sm sm:max-w-sm" value={text} onChange={(e) => onChange(e.target.value)}>
          {ctx.links.map((l) => (
            <option key={l.href} value={l.href}>
              {l.label} ({l.href})
            </option>
          ))}
        </select>
      );
    case "url":
      return <input {...common} type="url" className="input min-h-10 py-2 text-sm" value={text} onChange={(e) => onChange(e.target.value)} />;
    case "check":
      return (
        <input {...common} type="checkbox" className="size-4 accent-ink" checked={value === true} onChange={(e) => onChange(e.target.checked)} />
      );
    default:
      return null;
  }
}

function ItemsEditor({ path, field, value, onChange, ctx }: { path: string; field: ItemsField; value: Record<string, unknown>[]; onChange: (v: unknown) => void; ctx: Ctx }) {
  const max = field.fixed ? value.length : (field.max ?? 20);
  const min = field.fixed ? value.length : (field.min ?? 1);
  const update = (i: number, k: string, v: unknown) => onChange(value.map((item, j) => (j === i ? { ...item, [k]: v } : item)));
  const move = (i: number, d: -1 | 1) => {
    const next = [...value];
    [next[i], next[i + d]] = [next[i + d], next[i]];
    onChange(next);
  };
  const firstKey = Object.keys(field.fields)[0];
  const itemName = field.itemLabel.toLowerCase();
  const wide = (sub: SubField) => sub.kind === "text" || sub.kind === "list" || (sub.kind === "line" && (sub.max ?? 160) >= 80);

  return (
    <div className="grid gap-3">
      {value.map((item, i) => {
        const title = typeof item[firstKey] === "string" ? (item[firstKey] as string) : "";
        return (
          <fieldset key={i} className="min-w-0 rounded-lg border border-line bg-surface/60 p-4">
            <legend className="sr-only">
              {field.itemLabel} {i + 1}
            </legend>
            <div className="mb-3 flex items-center justify-between gap-3">
              <p className="min-w-0 truncate text-sm font-semibold">
                {field.itemLabel} {i + 1}
                {title && <span className="font-normal text-muted"> · {fillText(title, ctx.vars)}</span>}
              </p>
              {!field.fixed && (
                <div className="flex shrink-0 gap-1">
                  <IconButton label={`${field.itemLabel} ${i + 1} omhoog`} disabled={i === 0} onClick={() => move(i, -1)}>
                    <ArrowUp className="size-4" />
                  </IconButton>
                  <IconButton label={`${field.itemLabel} ${i + 1} omlaag`} disabled={i === value.length - 1} onClick={() => move(i, 1)}>
                    <ArrowDown className="size-4" />
                  </IconButton>
                  <IconButton label={`${field.itemLabel} ${i + 1} verwijderen`} disabled={value.length <= min} onClick={() => onChange(value.filter((_, j) => j !== i))}>
                    <Trash2 className="size-4" />
                  </IconButton>
                </div>
              )}
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              {Object.entries(field.fields).map(([k, sub]) => {
                const subPath = `${path}.${i}.${k}`;
                const error = ctx.errorAt(subPath);
                return (
                  <div key={k} className={`grid min-w-0 content-start gap-1.5 ${wide(sub) ? "sm:col-span-2" : ""}`}>
                    {sub.kind === "check" ? (
                      <label className="inline-flex items-center gap-2.5 text-sm font-medium">
                        <input type="checkbox" className="size-4 accent-ink" checked={item[k] === true} onChange={(e) => update(i, k, e.target.checked)} />
                        {sub.label}
                      </label>
                    ) : (
                      <>
                        <label htmlFor={fieldId(subPath)} className="text-sm font-medium">
                          {sub.label}
                          {sub.optional && <span className="font-normal text-muted"> (mag leeg)</span>}
                        </label>
                        <Control id={fieldId(subPath)} field={sub} value={item[k]} onChange={(v) => update(i, k, v)} ctx={ctx} invalid={!!error} />
                      </>
                    )}
                    <FieldFoot field={sub} value={item[k]} ctx={ctx} />
                    {error && <p className="field-error mt-0">{error}</p>}
                  </div>
                );
              })}
            </div>
          </fieldset>
        );
      })}
      {!field.fixed && (
        <div>
          <button type="button" onClick={() => onChange([...value, emptyItem(field)])} disabled={value.length >= max} className="btn btn-sm btn-outline bg-white">
            <Plus className="size-4" aria-hidden="true" /> {field.itemLabel} toevoegen
          </button>
          {value.length >= max && <span className="ml-3 text-xs text-muted">Maximaal {max} {itemName === "review" ? "reviews" : "items"}.</span>}
        </div>
      )}
    </div>
  );
}

function IconButton({ label, onClick, disabled, children }: { label: string; onClick: () => void; disabled?: boolean; children: ReactNode }) {
  return (
    <button
      type="button"
      onClick={onClick}
      disabled={disabled}
      aria-label={label}
      title={label}
      className="grid size-8 place-items-center rounded-md border border-line bg-white text-ink/80 hover:border-ink/40 hover:text-ink disabled:opacity-35"
    >
      {children}
    </button>
  );
}

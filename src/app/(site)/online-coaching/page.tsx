import { ArrowRight, Briefcase, Dumbbell, Gift, Plane, Trophy } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { CtaBand } from "@/components/CtaBand";
import { DashboardPreview } from "@/components/DashboardPreview";
import { Faq } from "@/components/Faq";
import { OnlinePlans } from "@/components/OnlinePlans";
import { ReferralSteps } from "@/components/ReferralSteps";
import { Rich } from "@/components/content/Rich";
import { ButtonLink, SectionHeading } from "@/components/ui";
import { algemeen, onlineCoaching } from "@/lib/content/registry";
import { getTexts } from "@/lib/content/texts";
import { resolveInvitation } from "@/lib/referral";
import { HeroHeading } from "@/components/HeroHeading";

const AUDIENCE_ICONS = [Dumbbell, Briefcase, Plane, Trophy];

export async function generateMetadata(): Promise<Metadata> {
  const { seo } = await getTexts(onlineCoaching);
  return { title: seo.title, description: seo.description };
}

export default async function OnlineCoachingPage({ searchParams }: PageProps<"/online-coaching">) {
  const { uitnodiging } = await searchParams;
  const [invitation, t, shared] = await Promise.all([resolveInvitation(uitnodiging), getTexts(onlineCoaching), getTexts(algemeen)]);
  const ref = invitation?.code ?? null;
  const audiences = t.voorWie.cards.map((c, i) => ({ ...c, icon: AUDIENCE_ICONS[i] }));

  return (
    <>
      <section className="hero-soft relative overflow-hidden">
        <div className="container-site grid items-center gap-14 py-16 lg:grid-cols-[1.2fr_1fr] lg:py-24">
          <div className="animate-rise">
            {invitation && (
              <p className="mb-6 inline-flex items-center gap-2 rounded-full bg-accent-tint px-4 py-2 text-sm font-semibold text-ink">
                <Gift className="size-4" aria-hidden="true" />
                {invitation.firstName} nodigt je uit: je krijgt {shared.vriendenactie.friendReward}
              </p>
            )}
            <HeroHeading eyebrow={t.hero.eyebrow} title={<Rich text={t.hero.title} />} />
            <p className="lead mt-6 max-w-xl text-ink/75">{t.hero.intro}</p>
            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
              <ButtonLink href="#pakketten">
                {t.hero.primary} <ArrowRight className="size-4" aria-hidden="true" />
              </ButtonLink>
              <ButtonLink href={`/registreren${ref ? `?ref=${ref}` : ""}`} variant="outline">
                {t.hero.secondary}
              </ButtonLink>
            </div>
            {t.hero.note && <p className="mt-5 text-sm text-muted">{t.hero.note}</p>}
          </div>
          <DashboardPreview />
        </div>
      </section>

      <section className="container-site py-20 lg:py-28">
        <SectionHeading eyebrow={t.voorWie.eyebrow} title={<Rich text={t.voorWie.title} />} intro={t.voorWie.intro} />
        <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {audiences.map(({ icon: Icon, title, text }, i) => (
            <div key={i} className="card p-6">
              <span className="grid size-11 place-items-center rounded-xl bg-accent-tint text-accent">
                <Icon className="size-5" aria-hidden="true" />
              </span>
              <h3 className="mt-5 text-lg font-semibold">{title}</h3>
              <p className="mt-2 text-[15px] text-muted">{text}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="bg-surface py-20 lg:py-28">
        <div className="container-site">
          <SectionHeading eyebrow={t.stappen.eyebrow} title={<Rich text={t.stappen.title} />} />
          <ol className="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            {t.stappen.steps.map((step, i) => (
              <li key={i} className="relative rounded-xl bg-paper p-7">
                <span className="display grid size-12 place-items-center rounded-full bg-ink text-2xl text-white">{i + 1}</span>
                <h3 className="display mt-6 text-2xl">{step.title}</h3>
                <p className="mt-3 text-[15px] leading-relaxed text-muted">{step.text}</p>
              </li>
            ))}
          </ol>
        </div>
      </section>

      <section id="pakketten" className="scroll-mt-28 bg-surface py-20 lg:py-28">
        <div className="container-site">
          <SectionHeading align="center" eyebrow={t.pakketten.eyebrow} title={<Rich text={t.pakketten.title} />} intro={t.pakketten.intro} />
          <div className="mt-14">
            <OnlinePlans referral={ref} />
          </div>
          <p className="mt-8 text-center text-sm text-muted">
            {t.pakketten.note}{" "}
            <Link href="/contact" className="font-semibold text-ink underline decoration-accent underline-offset-4">
              {t.pakketten.noteLink}
            </Link>
          </p>
        </div>
      </section>

      <section className="container-site grid gap-14 py-20 lg:grid-cols-2 lg:items-center lg:py-28">
        <div>
          <SectionHeading eyebrow={t.vriendenactie.eyebrow} title={<Rich text={t.vriendenactie.title} />} intro={t.vriendenactie.intro} />
          <ButtonLink href="/vriend-uitnodigen" variant="outline" className="mt-8">
            {t.vriendenactie.button}
          </ButtonLink>
        </div>
        <ReferralSteps />
      </section>

      <section className="container-site pb-8">
        <SectionHeading eyebrow={t.faq.eyebrow} title={<Rich text={t.faq.title} />} />
        <div className="mt-10 max-w-4xl">
          <Faq items={t.faq.questions} />
        </div>
      </section>

      <CtaBand
        title={t.afsluiter.title}
        text={t.afsluiter.text}
        primary={{ href: `/registreren${ref ? `?ref=${ref}` : ""}`, label: t.afsluiter.primary }}
        secondary={{ href: "/contact", label: t.afsluiter.secondary }}
      />
    </>
  );
}

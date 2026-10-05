import { ArrowRight, Battery, Brain, Building2, Clock, Moon, Trophy, User, Users } from "lucide-react";
import type { Metadata } from "next";
import { CtaBand } from "@/components/CtaBand";
import { Faq } from "@/components/Faq";
import { PageHero } from "@/components/PageHero";
import { Rich } from "@/components/content/Rich";
import { ButtonLink, CheckList, SectionHeading } from "@/components/ui";
import { ademcoaching, pakketten } from "@/lib/content/registry";
import { getTexts } from "@/lib/content/texts";

const BENEFIT_ICONS = [Brain, Moon, Battery, Trophy];
const GROUP_ICONS = [Building2, Trophy, Users];

export async function generateMetadata(): Promise<Metadata> {
  const { seo } = await getTexts(ademcoaching);
  return { title: seo.title, description: seo.description };
}

export default async function AdemcoachingPage() {
  const [t, { adem }] = await Promise.all([getTexts(ademcoaching), getTexts(pakketten)]);
  const benefits = t.voordelen.cards.map((c, i) => ({ ...c, icon: BENEFIT_ICONS[i] }));
  const groups = t.vormen.groups.map((c, i) => ({ ...c, icon: GROUP_ICONS[i] }));
  return (
    <>
      <PageHero
        eyebrow={t.hero.eyebrow}
        title={<Rich text={t.hero.title} />}
        intro={t.hero.intro}
        image="/images/steyn-team-gym.jpg"
        imageAlt="Steyn met sporters bij Gymbase in Amsterdam"
      >
        <ButtonLink href="/contact?onderwerp=ademcoaching">
          {t.hero.primary} <ArrowRight className="size-4" aria-hidden="true" />
        </ButtonLink>
        <ButtonLink href="/contact?onderwerp=ademcoaching-groep" variant="outline">
          {t.hero.secondary}
        </ButtonLink>
      </PageHero>

      <section className="container-site py-20 lg:py-28">
        <SectionHeading eyebrow={t.voordelen.eyebrow} title={<Rich text={t.voordelen.title} />} intro={t.voordelen.intro} />
        <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {benefits.map(({ icon: Icon, title, text }, i) => (
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
          <SectionHeading eyebrow={t.vormen.eyebrow} title={<Rich text={t.vormen.title} />} />
          <div className="mt-12 grid gap-5 lg:grid-cols-2">
            <article className="flex flex-col rounded-xl border border-ink bg-white p-7 ring-1 ring-ink sm:p-9">
              <p className="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-accent">
                <User className="size-4" aria-hidden="true" /> {t.vormen.soloLabel}
              </p>
              <h3 className="display mt-2 text-3xl">{adem.name}</h3>
              <p className="mt-6 flex items-baseline gap-1">
                <span className="text-lg font-semibold">€</span>
                <span className="display text-6xl">{adem.price}</span>
                <span className="text-lg font-semibold text-muted">,-</span>
                <span className="ml-1 text-sm text-muted">{t.vormen.perSession}</span>
              </p>
              <p className="mt-2 flex items-center gap-2 text-sm font-medium">
                <Clock className="size-4 text-accent" aria-hidden="true" /> {adem.duration}
              </p>
              <div className="mt-6 flex-1">
                <CheckList items={adem.features} />
              </div>
              <ButtonLink href="/contact?onderwerp=ademcoaching" variant="ink" className="mt-8">
                {t.vormen.soloButton}
              </ButtonLink>
            </article>

            <article className="flex flex-col rounded-xl border border-line bg-white p-7 sm:p-9">
              <p className="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-muted">
                <Users className="size-4" aria-hidden="true" /> {t.vormen.groupLabel}
              </p>
              <h3 className="display mt-2 text-3xl">{t.vormen.groupTitle}</h3>
              <p className="display mt-6 text-4xl">{t.vormen.groupPrice}</p>
              <p className="mt-2 text-sm text-muted">{t.vormen.groupText}</p>
              <ul className="mt-6 grid flex-1 content-start gap-4">
                {groups.map(({ icon: Icon, title, text }, i) => (
                  <li key={i} className="flex gap-4">
                    <Icon className="size-6 shrink-0 text-accent" aria-hidden="true" />
                    <span>
                      <span className="block font-semibold">{title}</span>
                      <span className="mt-0.5 block text-sm text-muted">{text}</span>
                    </span>
                  </li>
                ))}
              </ul>
              <ButtonLink href="/contact?onderwerp=ademcoaching-groep" variant="outline" className="mt-8">
                {t.vormen.groupButton}
              </ButtonLink>
            </article>
          </div>

          <div className="mt-16 max-w-3xl">
            <h3 className="display text-2xl">
              <Rich text={t.sessie.title} />
            </h3>
            <ol className="mt-8 grid gap-6 sm:grid-cols-2">
              {t.sessie.steps.map(({ title, text }, i) => (
                <li key={i} className="flex gap-5">
                  <span className="display grid size-11 shrink-0 place-items-center rounded-full border border-accent/50 text-xl text-accent">
                    {i + 1}
                  </span>
                  <span>
                    <span className="block text-lg font-semibold">{title}</span>
                    <span className="mt-1 block text-muted">{text}</span>
                  </span>
                </li>
              ))}
            </ol>
          </div>
        </div>
      </section>

      <section className="container-site grid gap-14 py-20 lg:grid-cols-[1fr_1.4fr] lg:py-28">
        <div>
          <SectionHeading eyebrow={t.faq.eyebrow} title={<Rich text={t.faq.title} />} />
          <div className="mt-8">
            <CheckList items={t.faq.points} />
          </div>
        </div>
        <Faq items={t.faq.questions} />
      </section>

      <CtaBand
        title={t.afsluiter.title}
        text={t.afsluiter.text}
        primary={{ href: "/contact?onderwerp=ademcoaching", label: t.afsluiter.primary }}
        secondary={{ href: "/contact?onderwerp=ademcoaching-groep", label: t.afsluiter.secondary }}
      />
    </>
  );
}

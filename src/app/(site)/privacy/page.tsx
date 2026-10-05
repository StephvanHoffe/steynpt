import type { Metadata } from "next";
import Link from "next/link";
import { Paragraphs, Rich } from "@/components/content/Rich";
import { privacy } from "@/lib/content/registry";
import { getTexts } from "@/lib/content/texts";
import { HeroHeading } from "@/components/HeroHeading";

export async function generateMetadata(): Promise<Metadata> {
  const { seo } = await getTexts(privacy);
  return { title: seo.title, description: seo.description };
}

export default async function PrivacyPage() {
  const t = await getTexts(privacy);
  return (
    <div className="container-site max-w-3xl py-16 lg:py-24">
      <HeroHeading eyebrow={t.intro.eyebrow} title={<Rich text={t.intro.title} />} size="lg" eyebrowClass="text-muted" gap="mt-4" />
      <p className="lead mt-6 text-muted">{t.intro.intro}</p>
      <div className="mt-12 space-y-10">
        {t.onderdelen.sections.map((s, i) => (
          <section key={i}>
            <h2 className="text-xl font-semibold">{s.title}</h2>
            <div className="prose-site mt-3 text-muted">
              <Paragraphs text={s.body} />
            </div>
          </section>
        ))}
      </div>
      <p className="mt-12 text-sm text-muted">
        Vragen? <Link href="/contact" className="font-semibold text-ink underline">Neem contact op</Link>.
      </p>
    </div>
  );
}

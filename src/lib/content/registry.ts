// Alle pagina's met aanpasbare teksten, in de volgorde van het beheer.
import type { PageDef } from "./fields";
import { ademcoaching } from "./pages/ademcoaching";
import { algemeen } from "./pages/algemeen";
import { home } from "./pages/home";
import { onlineCoaching } from "./pages/online-coaching";
import { contact, overSteyn, privacy, tarieven, voedingscoaching, vriendUitnodigen } from "./pages/overige";
import { pakketten } from "./pages/pakketten";
import { personalTraining } from "./pages/personal-training";

export { ademcoaching, algemeen, contact, home, onlineCoaching, overSteyn, pakketten, personalTraining, privacy, tarieven, voedingscoaching, vriendUitnodigen };

/** Pagina's van de site, in de volgorde van het menu. */
export const SITE_PAGES: PageDef[] = [home, onlineCoaching, personalTraining, ademcoaching, voedingscoaching, tarieven, overSteyn, vriendUitnodigen, contact, privacy];
/** Teksten die op meerdere pagina's staan. */
export const SHARED_PAGES: PageDef[] = [algemeen, pakketten];
export const ALL_PAGES: PageDef[] = [...SITE_PAGES, ...SHARED_PAGES];

export const findPage = (slug: string) => ALL_PAGES.find((p) => p.slug === slug);

export { computeVars, VAR_KEYS, VARS } from "./vars";

import Link from "next/link";
import { site } from "@/data/site";
import { catalogNav, mainNav } from "@/data/navigation";
import { branches } from "@/data/branches";
import { content } from "@/lib/content";
import { Logo } from "@/components/ui/Logo";

const linkClass = "text-white/70 transition-colors hover:text-white";
const headClass = "text-xs font-semibold uppercase tracking-[0.18em] text-yellow";

/** Ijtimoiy tarmoq belgilari — oddiy SVG, tashqi kutubxonasiz. */
function SocialIcon({ name }: { name: "instagram" | "telegram" }) {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      {name === "instagram" ? (
        <>
          <rect x="3" y="3" width="18" height="18" rx="5" />
          <circle cx="12" cy="12" r="4" />
          <circle cx="17.5" cy="6.5" r="0.6" fill="currentColor" />
        </>
      ) : (
        <path d="M21 4 3 11l6 2m12-9-3 16-9-7m12-9L9 13m0 0v6l3-3.5" />
      )}
    </svg>
  );
}

/**
 * To'q binafsha footer: tepada "savolingiz bormi" qatori katta telefon bilan,
 * ostida katalog, kompaniya, filiallar (manzil va ish vaqti bilan) va aloqa.
 */
export function Footer() {
  const socials = [
    { name: "instagram" as const, label: `Instagram: @${site.instagram}`, href: site.instagramUrl },
    { name: "telegram" as const, label: `Telegram: @${site.telegram}`, href: site.telegramUrl },
  ];

  return (
    <footer className="site-footer relative overflow-hidden text-white">
      <Logo
        variant="mark"
        className="pointer-events-none absolute -right-16 top-10 h-96 w-auto text-white/[0.03]"
      />
      <div className="relative mx-auto max-w-7xl px-5 sm:px-8">
        {/* Savol qatori */}
        <div className="flex flex-col items-start justify-between gap-6 border-b border-white/10 py-12 md:flex-row md:items-center">
          <div>
            <p className="display text-3xl sm:text-4xl">Savolingiz bormi?</p>
            <p className="mt-2 text-white/70">Qo&apos;ng&apos;iroq qiling — mutaxassisimiz hammasini tushuntiradi.</p>
          </div>
          <a
            href={`tel:${site.phone}`}
            className="numeral inline-flex items-center gap-3 rounded-[16px] bg-yellow px-6 py-4 text-3xl tracking-[0.04em] text-ink transition-transform hover:-translate-y-0.5 sm:text-4xl"
          >
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" />
            </svg>
            {site.phoneDisplay}
          </a>
        </div>

        <div className="grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-[1.3fr_0.8fr_0.8fr_1.4fr]">
          <div>
            <Logo className="h-10 w-auto text-white" title="HAMKOR SAVDO" />
            <p className="mt-5 max-w-xs leading-relaxed text-white/70">
              {site.tagline} Texnika, tilla, mebel va skuterlar — {site.facts.installmentMonthsMax} oygacha muddatli
              to&apos;lovga.
            </p>
            <ul className="mt-6 flex gap-2">
              {socials.map((s) => (
                <li key={s.name}>
                  <a
                    href={s.href}
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label={s.label}
                    className="flex h-11 w-11 items-center justify-center rounded-[12px] border border-white/15 bg-white/5 text-white transition-colors hover:border-yellow hover:bg-yellow hover:text-ink"
                  >
                    <SocialIcon name={s.name} />
                  </a>
                </li>
              ))}
            </ul>
          </div>

          <nav aria-label="Katalog">
            <h2 className={headClass}>Katalog</h2>
            <ul className="mt-5 space-y-3 text-sm">
              {catalogNav.map((item) => (
                <li key={item.href}>
                  <Link href={item.href} className={linkClass}>
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </nav>

          <nav aria-label="Sahifalar">
            <h2 className={headClass}>Kompaniya</h2>
            <ul className="mt-5 space-y-3 text-sm">
              {mainNav.map((item) => (
                <li key={item.href}>
                  <Link href={item.href} className={linkClass}>
                    {item.label}
                  </Link>
                </li>
              ))}
              <li>
                <a href={site.telegramBotUrl} target="_blank" rel="noopener noreferrer" className={linkClass}>
                  Taklif va shikoyat
                </a>
              </li>
            </ul>
          </nav>

          <div>
            <h2 className={headClass}>Filiallar</h2>
            <ul className="mt-5 space-y-4 text-sm">
              {branches.map((b) => (
                <li key={b.id}>
                  <Link href={`/filiallar/${b.id}`} className="group block">
                    <span className="flex items-baseline justify-between gap-3">
                      <span className="font-semibold text-white group-hover:text-yellow">
                        {b.city} — {b.landmark}
                      </span>
                      <span className="shrink-0 text-xs text-white/50">{b.hours}</span>
                    </span>
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </div>

        <div className="flex flex-col gap-3 border-t border-white/10 py-6 text-xs text-white/50 sm:flex-row sm:items-center sm:justify-between">
          <p>
            © {new Date().getFullYear()} {site.name}. Barcha huquqlar himoyalangan.
          </p>
          <p className="flex flex-wrap items-center gap-x-5 gap-y-2">
            {/* Ruscha nusxa — oddiy <a>: boshqa til, to'liq yuklanish kerak. */}
            <a href="/ru/" hrefLang="ru" data-lang-link className="transition-colors hover:text-white">
              Русская версия
            </a>
            {/* Katalog bo'sh bo'lsa, sahifa qidiruvdan ham yopilgan
                (`robots: index: false`) — unga havola ham bermaymiz. */}
            {content.products.length > 0 ? (
              <Link href="/katalog" className="transition-colors hover:text-white">
                Mahsulotlar katalogi
              </Link>
            ) : null}
            <Link href="/maxfiylik" className="transition-colors hover:text-white">
              Maxfiylik siyosati
            </Link>
          </p>
        </div>
      </div>
    </footer>
  );
}

import Link from "next/link";
import { site } from "@/data/site";
import { catalogNav, mainNav } from "@/data/navigation";
import { content } from "@/lib/content";
import { Logo } from "@/components/ui/Logo";

const linkClass = "text-ink-2 transition-colors hover:text-purple";

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

export function Footer() {
  const socials = [
    { name: "instagram" as const, label: `Instagram: @${site.instagram}`, href: site.instagramUrl },
    { name: "telegram" as const, label: `Telegram: @${site.telegram}`, href: site.telegramUrl },
  ];

  return (
    <footer className="border-t border-line bg-ground-2">
      <div className="mx-auto grid max-w-7xl gap-10 px-5 py-14 sm:grid-cols-2 sm:px-8 lg:grid-cols-[1.4fr_1fr_1fr_1.2fr]">
        <div>
          <Logo className="h-9 w-auto text-purple" title="HAMKOR SAVDO" />
          <p className="mt-4 max-w-xs leading-relaxed text-ink-2">{site.tagline}</p>
          <p className="mt-2 text-sm text-ink-3">{site.serviceArea}</p>
          <ul className="mt-6 flex gap-2">
            {socials.map((s) => (
              <li key={s.name}>
                <a
                  href={s.href}
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label={s.label}
                  className="flex h-11 w-11 items-center justify-center rounded-[12px] border border-line bg-ground text-purple transition-colors hover:border-purple hover:bg-purple hover:text-white"
                >
                  <SocialIcon name={s.name} />
                </a>
              </li>
            ))}
          </ul>
        </div>

        <nav aria-label="Katalog">
          <h2 className="text-xs font-semibold uppercase tracking-wider text-ink-3">Katalog</h2>
          <ul className="mt-4 space-y-2.5 text-sm">
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
          <h2 className="text-xs font-semibold uppercase tracking-wider text-ink-3">Kompaniya</h2>
          <ul className="mt-4 space-y-2.5 text-sm">
            {mainNav.map((item) => (
              <li key={item.href}>
                <Link href={item.href} className={linkClass}>
                  {item.label}
                </Link>
              </li>
            ))}
          </ul>
        </nav>

        <div>
          <h2 className="text-xs font-semibold uppercase tracking-wider text-ink-3">Aloqa</h2>
          <a
            href={`tel:${site.phone}`}
            className="numeral mt-4 block text-2xl tracking-[0.04em] text-purple hover:underline"
          >
            {site.phoneDisplay}
          </a>
          <ul className="mt-3 space-y-2.5 text-sm">
            <li>
              <a href={site.telegramBotUrl} target="_blank" rel="noopener noreferrer" className={linkClass}>
                Taklif va shikoyat: @{site.telegramBot}
              </a>
            </li>
            <li>
              <Link href="/aloqa" className={linkClass}>
                Barcha filiallar va telefonlar &rarr;
              </Link>
            </li>
          </ul>
        </div>
      </div>

      <div className="border-t border-line">
        <div className="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-5 text-xs text-ink-3 sm:flex-row sm:items-center sm:justify-between sm:px-8">
          <p>
            © {new Date().getFullYear()} {site.name}. Barcha huquqlar himoyalangan.
          </p>
          <p className="flex flex-wrap items-center gap-x-5">
            {/* Ruscha nusxa — oddiy <a>: boshqa til, to'liq yuklanish kerak. */}
            {/* eslint-disable-next-line @next/next/no-html-link-for-pages */}
            <a href="/ru/" hrefLang="ru" data-lang-link className="transition-colors hover:text-purple">
              Русская версия
            </a>
            {/* Katalog bo'sh bo'lsa, sahifa qidiruvdan ham yopilgan
                (`robots: index: false`) — unga havola ham bermaymiz. */}
            {content.products.length > 0 ? (
              <Link href="/katalog" className="transition-colors hover:text-purple">
                Mahsulotlar katalogi
              </Link>
            ) : null}
            <Link href="/maxfiylik" className="transition-colors hover:text-purple">
              Maxfiylik siyosati
            </Link>
          </p>
        </div>
      </div>
    </footer>
  );
}

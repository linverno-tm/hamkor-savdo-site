import Link from "next/link";
import { catalogNav, mainNav } from "@/data/navigation";
import { site } from "@/data/site";
import { Logo } from "@/components/ui/Logo";

/**
 * Sticky navigation — a server component with no JavaScript of its own.
 *
 * "Katalog" ochiluvchi ro'yxati sof CSS: sichqoncha ustiga kelganda yoki
 * klaviatura bilan ichiga kirilganda (`:focus-within`) ochiladi. Ichidagi
 * havolalar HTML'da doim turadi — Google ularni menyu havolasi sifatida ko'radi.
 *
 * The mobile menu is a native <details>/<summary> disclosure, so it opens,
 * closes, announces its state and takes keyboard input before a single byte of
 * JS has arrived. `components/ui/Reveal.tsx` layers Escape-to-close on top when
 * JS is present, and sets `data-scrolled` here once the page moves, which
 * brings in the subtle shadow (globals.css > .site-header).
 */
export function Header() {
  return (
    <header data-site-header className="site-header fixed inset-x-0 top-0 z-50 border-b border-line bg-ground/95 backdrop-blur">
      {/* Sahifadagi birinchi fokuslanadigan element — uslubi globals.css da. */}
      <a href="#asosiy" className="skip-link">
        Asosiy qismga o&apos;tish
      </a>
      <div className="mx-auto flex h-[4.5rem] max-w-7xl items-center justify-between gap-6 px-5 sm:px-8">
        <Link href="/" className="tap shrink-0 text-purple" aria-label="HAMKOR SAVDO — bosh sahifa">
          <Logo className="h-8 w-auto sm:h-9" />
        </Link>

        {/* Kirill matn lotinchadan kengroq ("Муддатли тўлов") — chegara ikkala
            nusxada ham tekshirilgan. Har bir band `whitespace-nowrap`. */}
        <nav aria-label="Asosiy menyu" className="hidden flex-1 items-center justify-center gap-1 xl:flex">
          <div className="nav-drop group relative">
            <button
              type="button"
              aria-haspopup="true"
              className="nav-link inline-flex items-center gap-1.5"
            >
              Katalog
              <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" className="transition-transform duration-200 group-hover:rotate-180 group-focus-within:rotate-180">
                <path d="M2.5 4.5 6 8l3.5-3.5" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </button>
            <div className="nav-drop-panel absolute left-1/2 top-full pt-3">
              <ul className="w-60 rounded-2xl border border-line bg-ground p-2 shadow-[0_24px_48px_-24px_rgba(47,24,72,0.45)]">
                {catalogNav.map((item) => (
                  <li key={item.href}>
                    <Link
                      href={item.href}
                      className="flex items-center justify-between rounded-xl px-4 py-3 text-[0.95rem] font-medium text-ink-2 transition-colors hover:bg-ground-2 hover:text-purple focus-visible:bg-ground-2"
                    >
                      {item.label}
                      <span aria-hidden="true" className="text-purple">&rarr;</span>
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          </div>
          {mainNav.map((item) => (
            <Link key={item.href} href={item.href} className="nav-link">
              {item.label}
            </Link>
          ))}
        </nav>

        <div className="hidden shrink-0 items-center gap-3 xl:flex">
          <a
            href="/uz-kr/"
            data-lang-link
            className="lang-to-cyrl tap whitespace-nowrap rounded-xl border border-line px-3 py-2 text-sm font-medium text-ink-2 transition-colors hover:border-purple hover:text-purple"
          >
            Kirillcha
          </a>
          {/* Til almashtirish — ataylab oddiy <a>, `next/link` emas: kirillcha
              nusxa (out/uz-kr/) postbuild skripti yozgan alohida statik fayllar,
              bu yerda haqiqiy qayta yuklash kerak. */}
          {/* eslint-disable-next-line @next/next/no-html-link-for-pages */}
          <a
            href="/"
            data-lang-link
            className="lang-to-latin tap whitespace-nowrap rounded-xl border border-line px-3 py-2 text-sm font-medium text-ink-2 transition-colors hover:border-purple hover:text-purple"
          >
            Lotincha
          </a>
          <a href={`tel:${site.phone}`} className="btn btn-primary btn-lift !min-h-11 whitespace-nowrap !px-5">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" />
            </svg>
            {site.phoneDisplay}
          </a>
        </div>

        {/* Mobile: native disclosure, works without JS */}
        <details id="mobile-menu" className="mobile-menu xl:hidden">
          <summary
            className="flex h-12 w-12 cursor-pointer items-center justify-center rounded-xl border border-line text-purple"
            aria-label="Menyu"
          >
            <svg className="menu-open-icon" width="20" height="14" viewBox="0 0 20 14" fill="none" aria-hidden="true">
              <path d="M1 1h18M1 7h18M1 13h18" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" />
            </svg>
            <svg className="menu-close-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
              <path d="M1 1l14 14M15 1L1 15" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" />
            </svg>
          </summary>

          <div className="on-purple mobile-menu-panel bg-purple text-white">
            <nav aria-label="Mobil menyu" className="flex flex-1 flex-col gap-8 px-6 py-6">
              <div>
                <p className="kicker">Katalog</p>
                <ul className="mt-3 grid grid-cols-2 gap-2">
                  {catalogNav.map((item) => (
                    <li key={item.href}>
                      <Link
                        href={item.href}
                        className="flex min-h-12 items-center rounded-xl bg-white/10 px-4 font-semibold transition-colors hover:bg-white/20"
                      >
                        {item.label}
                      </Link>
                    </li>
                  ))}
                </ul>
              </div>
              <ul className="flex flex-col">
                {mainNav.map((item) => (
                  <li key={item.href}>
                    <Link
                      href={item.href}
                      className="flex items-center justify-between border-b border-white/15 py-4 transition-colors hover:text-yellow"
                    >
                      <span className="display text-2xl">{item.label}</span>
                      <span aria-hidden="true">&rarr;</span>
                    </Link>
                  </li>
                ))}
              </ul>
            </nav>
            <div className="grid gap-3 px-6 pb-10 sm:grid-cols-2">
              <a href={`tel:${site.phone}`} className="btn btn-yellow">
                {site.phoneDisplay}
              </a>
              <a href={site.telegramUrl} target="_blank" rel="noopener noreferrer" className="btn btn-outline">
                Telegram
              </a>
              <a href="/uz-kr/" data-lang-link className="lang-to-cyrl btn btn-outline">
                Kirillcha
              </a>
              {/* eslint-disable-next-line @next/next/no-html-link-for-pages */}
              <a href="/" data-lang-link className="lang-to-latin btn btn-outline">
                Lotincha
              </a>
            </div>
          </div>
        </details>
      </div>
    </header>
  );
}

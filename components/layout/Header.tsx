import { navigation } from "@/data/navigation";
import { site } from "@/data/site";
import { Logo } from "@/components/ui/Logo";

/**
 * Sticky navigation — a server component with no JavaScript of its own.
 *
 * The mobile menu is a native <details>/<summary> disclosure, so it opens,
 * closes, announces its state and takes keyboard input before a single byte of
 * JS has arrived. That matters here: on a slow connection the framework bundle
 * can land seconds after the HTML, and a hamburger that does nothing in the
 * meantime is the most frustrating thing a phone user can meet.
 * `components/ui/Reveal.tsx` layers Escape-to-close on top when JS is present.
 *
 * The header keeps a solid white ground at all times — the guidebook forbids
 * placing the lockup over busy or ambiguous backgrounds.
 */
export function Header() {
  return (
    <header className="fixed inset-x-0 top-0 z-50 border-b border-line bg-ground">
      <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8">
        <a href="#hero" className="shrink-0 text-purple" aria-label="HAMKOR SAVDO — bosh sahifa">
          <Logo className="h-8 w-auto sm:h-9" />
        </a>

        <nav aria-label="Asosiy menyu" className="hidden items-center gap-6 lg:flex">
          {navigation.map((item) => (
            <a
              key={item.href}
              href={item.href}
              className="text-[0.95rem] font-medium text-ink-2 transition-colors hover:text-purple"
            >
              {item.label}
            </a>
          ))}
          <a href={`tel:${site.phone}`} className="btn btn-primary !min-h-11 !px-5">
            {site.phoneDisplay}
          </a>
        </nav>

        {/* Mobile: native disclosure, works without JS */}
        <details id="mobile-menu" className="mobile-menu lg:hidden">
          <summary
            className="flex h-12 w-12 cursor-pointer items-center justify-center rounded-full border border-line text-purple"
            aria-label="Menyu"
          >
            <svg
              className="menu-open-icon"
              width="20"
              height="14"
              viewBox="0 0 20 14"
              fill="none"
              aria-hidden="true"
            >
              <path d="M1 1h18M1 7h18M1 13h18" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" />
            </svg>
            <svg
              className="menu-close-icon"
              width="16"
              height="16"
              viewBox="0 0 16 16"
              fill="none"
              aria-hidden="true"
            >
              <path d="M1 1l14 14M15 1L1 15" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" />
            </svg>
          </summary>

          <div className="on-purple mobile-menu-panel bg-purple text-white">
            <nav aria-label="Mobil menyu" className="flex flex-1 flex-col justify-center gap-1 px-6">
              {navigation.map((item, i) => (
                <a
                  key={item.href}
                  href={item.href}
                  className="flex items-baseline gap-4 rounded-xl py-3 transition-colors hover:text-yellow"
                >
                  <span className="numeral text-lg text-on-purple-2" aria-hidden="true">
                    0{i + 1}
                  </span>
                  <span className="display text-3xl">{item.label}</span>
                </a>
              ))}
            </nav>
            <div className="grid gap-3 px-6 pb-10 sm:grid-cols-2">
              <a href={`tel:${site.phone}`} className="btn btn-yellow">
                {site.phoneDisplay}
              </a>
              <a
                href={site.telegramUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="btn btn-outline"
              >
                Telegram
              </a>
            </div>
          </div>
        </details>
      </div>
    </header>
  );
}

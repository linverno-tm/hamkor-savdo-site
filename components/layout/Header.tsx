import Link from "next/link";
import { catalogNav, mainNav } from "@/data/navigation";
import { site } from "@/data/site";
import { Logo } from "@/components/ui/Logo";
import { ThemeToggle } from "@/components/ui/ThemeToggle";

const PhoneIcon = ({ size = 16 }: { size?: number }) => (
  <svg width={size} height={size} viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path
      d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinejoin="round"
    />
  </svg>
);

/**
 * Suzuvchi menyu — sahifa ustida turgan oq "orolcha" (globals.css >
 * .hdr). Har qanday fon ustida o'qiladi: bosh sahifaning binafsha
 * qismida ham, boshqa sahifalarning oq boshida ham. Sahifa siljigach
 * (`data-scrolled`, lib/motion.ts) biroz ixchamlashadi va soya oladi.
 *
 * JavaScript'siz ishlaydi:
 *  - "Katalog" ro'yxati — sof CSS (`:hover` / `:focus-within`), havolalar
 *    HTML'da doim turadi, Google ularni ko'radi.
 *  - Telefon menyusi — native <details>; Escape va qulflash
 *    components/ui/Reveal.tsx da qo'shimcha sifatida.
 */
export function Header() {
  return (
    <header data-site-header className="hdr fixed inset-x-0 top-0 z-50">
      {/* Sahifadagi birinchi fokuslanadigan element — uslubi globals.css da. */}
      <a href="#asosiy" className="skip-link">
        Asosiy qismga o&apos;tish
      </a>
      <div className="hdr-bar mx-auto flex max-w-7xl items-center justify-between gap-4">
        <Link href="/" className="tap shrink-0 text-purple" aria-label="HAMKOR SAVDO — bosh sahifa">
          <Logo className="h-8 w-auto sm:h-9" />
        </Link>

        {/* Kirill matn lotinchadan kengroq ("Муддатли тўлов") — chegara ikkala
            nusxada ham tekshirilgan. Har bir band `whitespace-nowrap`. */}
        <nav aria-label="Asosiy menyu" className="hidden flex-1 items-center justify-center gap-0.5 xl:flex">
          <div className="nav-drop group relative">
            <button type="button" aria-haspopup="true" className="nav-link inline-flex items-center gap-1.5">
              Katalog
              <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" className="transition-transform duration-300 group-hover:rotate-180 group-focus-within:rotate-180">
                <path d="M2.5 4.5 6 8l3.5-3.5" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </button>
            <div className="nav-drop-panel absolute left-1/2 top-full pt-4">
              <ul className="nav-drop-list w-64 p-2">
                {catalogNav.map((item) => (
                  <li key={item.href}>
                    <Link href={item.href} className="nav-drop-item">
                      {item.label}
                      <span aria-hidden="true">&rarr;</span>
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

        <div className="hidden shrink-0 items-center gap-2 xl:flex">
          <ThemeToggle />
          <a href="/uz-kr/" data-lang-link className="lang-to-cyrl hdr-chip tap">
            Kirillcha
          </a>
          {/* Til almashtirish — ataylab oddiy <a>, `next/link` emas: kirillcha
              nusxa (out/uz-kr/) postbuild skripti yozgan alohida statik fayllar,
              bu yerda haqiqiy qayta yuklash kerak. */}
          {/* eslint-disable-next-line @next/next/no-html-link-for-pages */}
          <a href="/" data-lang-link className="lang-to-latin hdr-chip tap">
            Lotincha
          </a>
          <a href={`tel:${site.phone}`} className="hdr-call">
            <span className="hdr-call-icon">
              <PhoneIcon />
            </span>
            {site.phoneDisplay}
          </a>
        </div>

        {/* Telefon: rejim tugmasi doim ko'rinadi; menyu — native disclosure, JS'siz ishlaydi */}
        <div className="flex items-center gap-2.5 xl:hidden">
          <ThemeToggle />
          <details id="mobile-menu" className="mobile-menu">
            <summary className="hdr-burger" aria-label="Menyu">
              <span className="hdr-burger-lines" aria-hidden="true">
                <span />
                <span />
              </span>
            </summary>
            <div className="on-purple mobile-menu-panel text-white">
              <nav aria-label="Mobil menyu" className="flex flex-1 flex-col gap-8 px-6 py-6">
                <div>
                  <p className="mm-label">Katalog</p>
                  <ul className="mm-cats mt-3 grid grid-cols-2 gap-2">
                    {catalogNav.map((item) => (
                      <li key={item.href}>
                        <Link href={item.href} className="mm-cat">
                          {item.label}
                        </Link>
                      </li>
                    ))}
                  </ul>
                </div>
                <ul className="mm-links flex flex-col">
                  {mainNav.map((item) => (
                    <li key={item.href}>
                      <Link href={item.href} className="mm-link">
                        <span>{item.label}</span>
                        <span aria-hidden="true">&rarr;</span>
                      </Link>
                    </li>
                  ))}
                </ul>
              </nav>
              <div className="mm-actions grid gap-3 px-6 pb-10 sm:grid-cols-2">
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
      </div>
    </header>
  );
}

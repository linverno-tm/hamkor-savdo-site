import Link from "next/link";
import { site } from "@/data/site";
import { branches } from "@/data/branches";
import { topics } from "@/data/topics";
import { Logo } from "@/components/ui/Logo";
import { ThemeToggle } from "@/components/ui/ThemeToggle";

/**
 * Ruscha sahifalar (/ru/...) uchun sarlavha va pastki qism.
 *
 * O'zbekcha `Header` bosh sahifadagi bo'limlarga (#biz-haqimizda ...) olib
 * boradi — ular ruscha emas, shuning uchun ruscha sahifada ular o'rniga
 * shu sahifalarning o'zaro havolalari turadi. Ataylab oddiy: logotip,
 * yo'nalishlar, telefon va "O'zbekcha" tugmasi. JavaScript'siz ishlaydi.
 */

export const CITY_RU: Record<string, string> = { Shahrixon: "Шахрихан", Asaka: "Асака", Andijon: "Андижан" };

const ruNav = [
  { href: "/ru/", label: "Главная" },
  ...topics.map((t) => ({ href: `${t.ruPath}/`, label: t.ru.kicker })),
];

export function RuHeader({ uzHref = "/" }: { uzHref?: string }) {
  return (
    <header className="fixed inset-x-0 top-0 z-50 border-b border-line bg-ground">
      <a href="#asosiy" className="skip-link">
        Перейти к содержанию
      </a>
      <div className="mx-auto flex h-20 max-w-7xl items-center justify-between gap-4 px-5 sm:px-8">
        {/* eslint-disable-next-line @next/next/no-html-link-for-pages */}
        <a href="/ru/" className="tap shrink-0 text-purple" aria-label="HAMKOR SAVDO — главная">
          <Logo className="h-8 w-auto sm:h-9" />
        </a>
        <nav aria-label="Разделы" className="hidden items-center gap-5 xl:flex">
          {ruNav.slice(1).map((item) => (
            <a
              key={item.href}
              href={item.href}
              className="whitespace-nowrap text-[0.95rem] font-medium text-ink-2 transition-colors hover:text-purple"
            >
              {item.label}
            </a>
          ))}
        </nav>
        <div className="flex items-center gap-2">
          <ThemeToggle lang="ru" />
          {/* Til almashtirish — oddiy <a>: o'zbekcha sahifa boshqa til nusxasi. */}
          <a
            href={uzHref}
            hrefLang="uz"
            className="tap whitespace-nowrap rounded-full border border-line px-3 py-1.5 text-sm font-medium text-ink-2 transition-colors hover:border-purple hover:text-purple"
          >
            O&apos;zbekcha
          </a>
          <a href={`tel:${site.phone}`} className="btn btn-primary !min-h-11 whitespace-nowrap !px-5">
            <span className="hidden sm:inline">{site.phoneDisplay}</span>
            <span className="sm:hidden">Позвонить</span>
          </a>
        </div>
      </div>
    </header>
  );
}

export function RuFooter() {
  return (
    <footer className="border-t border-line bg-ground-2">
      <div className="mx-auto grid max-w-7xl gap-10 px-5 py-14 sm:px-8 lg:grid-cols-4">
        <div>
          <Logo className="h-9 w-auto text-purple" title="HAMKOR SAVDO" />
          <p className="mt-4 max-w-xs leading-relaxed text-ink-2">
            Бытовая техника, мебель и золото в рассрочку. Андижанская область.
          </p>
        </div>
        <nav aria-label="Разделы сайта">
          <h2 className="text-xs font-semibold uppercase tracking-wider text-ink-3">Разделы</h2>
          <ul className="mt-4 space-y-2 text-sm">
            {ruNav.map((item) => (
              <li key={item.href}>
                <a href={item.href} className="text-ink-2 transition-colors hover:text-purple">
                  {item.label}
                </a>
              </li>
            ))}
          </ul>
        </nav>
        <div>
          <h2 className="text-xs font-semibold uppercase tracking-wider text-ink-3">Магазины</h2>
          <ul className="mt-4 space-y-3 text-sm">
            {branches.map((b) => (
              <li key={b.id}>
                <Link href={`/filiallar/${b.id}`} className="group block" hrefLang="uz">
                  <span className="font-semibold text-ink group-hover:text-purple">{CITY_RU[b.city] ?? b.city}</span>
                  <span className="block text-ink-3">{b.landmark}</span>
                </Link>
              </li>
            ))}
          </ul>
        </div>
        <div>
          <h2 className="text-xs font-semibold uppercase tracking-wider text-ink-3">Контакты</h2>
          <ul className="mt-4 space-y-2 text-sm">
            <li>
              <a href={`tel:${site.phone}`} className="font-semibold text-purple hover:underline">
                {site.phoneDisplay}
              </a>
            </li>
            <li>
              <a href={site.telegramUrl} target="_blank" rel="noopener noreferrer" className="text-ink-2 hover:text-purple">
                Telegram: @{site.telegram}
              </a>
            </li>
            <li>
              <a href={site.instagramUrl} target="_blank" rel="noopener noreferrer" className="text-ink-2 hover:text-purple">
                Instagram: @{site.instagram}
              </a>
            </li>
          </ul>
        </div>
      </div>
      <div className="border-t border-line">
        <div className="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-5 text-xs text-ink-3 sm:flex-row sm:items-center sm:justify-between sm:px-8">
          <p>
            © {new Date().getFullYear()} {site.name}. Андижанская область.
          </p>
          <Link href="/maxfiylik" className="hover:text-purple" hrefLang="uz">
            Политика конфиденциальности (на узбекском)
          </Link>
        </div>
      </div>
    </footer>
  );
}

import Link from "next/link";
import { site } from "@/data/site";
import { navigation } from "@/data/navigation";
import { branches } from "@/data/branches";
import { Logo } from "@/components/ui/Logo";

export function Footer() {
  return (
    <footer className="border-t border-line bg-ground-2">
      <div className="mx-auto grid max-w-7xl gap-10 px-5 py-14 sm:px-8 lg:grid-cols-4">
        <div className="lg:col-span-1">
          <Logo className="h-9 w-auto text-purple" title="HAMKOR SAVDO" />
          <p className="mt-4 max-w-xs leading-relaxed text-ink-2">{site.tagline}</p>
          <p className="mt-3 text-sm text-ink-3">{site.serviceArea}</p>
        </div>

        <nav aria-label="Sahifa bo'limlari">
          <h2 className="text-xs font-semibold uppercase tracking-wider text-ink-3">Bo&apos;limlar</h2>
          <ul className="mt-4 space-y-2 text-sm">
            {navigation.map((item) => (
              <li key={item.href}>
                <Link href={item.href} className="text-ink-2 transition-colors hover:text-purple">
                  {item.label}
                </Link>
              </li>
            ))}
          </ul>
        </nav>

        <div>
          <h2 className="text-xs font-semibold uppercase tracking-wider text-ink-3">Filiallar</h2>
          <ul className="mt-4 space-y-3 text-sm">
            {branches.map((b) => (
              <li key={b.id}>
                <Link href={`/filiallar/${b.id}`} className="group block">
                  <span className="font-semibold text-ink group-hover:text-purple">{b.city}</span>
                  <span className="block text-ink-3">{b.landmark}</span>
                </Link>
              </li>
            ))}
          </ul>
        </div>

        <div>
          <h2 className="text-xs font-semibold uppercase tracking-wider text-ink-3">Aloqa</h2>
          <ul className="mt-4 space-y-2 text-sm">
            <li>
              <a href={`tel:${site.phone}`} className="font-semibold text-purple hover:underline">
                {site.phoneDisplay}
              </a>
            </li>
            <li>
              <a
                href={site.telegramUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="text-ink-2 transition-colors hover:text-purple"
              >
                Telegram: @{site.telegram}
              </a>
            </li>
            <li>
              <a
                href={site.instagramUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="text-ink-2 transition-colors hover:text-purple"
              >
                Instagram: @{site.instagram}
              </a>
            </li>
            <li>
              <a
                href={site.telegramBotUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="text-ink-2 transition-colors hover:text-purple"
              >
                Taklif: @{site.telegramBot}
              </a>
            </li>
          </ul>
        </div>
      </div>

      <div className="border-t border-line">
        <div className="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-5 text-xs text-ink-3 sm:flex-row sm:items-center sm:justify-between sm:px-8">
          <p>
            © {new Date().getFullYear()} {site.name}. {site.serviceArea}.
          </p>
          <p className="flex flex-wrap items-center gap-x-5">
            <Link href="/filiallar" className="transition-colors hover:text-purple">
              Filiallar
            </Link>
            <Link href="/maxfiylik" className="transition-colors hover:text-purple">
              Maxfiylik siyosati
            </Link>
          </p>
        </div>
      </div>
    </footer>
  );
}

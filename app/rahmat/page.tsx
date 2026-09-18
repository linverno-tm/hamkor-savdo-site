import type { Metadata } from "next";
import Link from "next/link";
import { site } from "@/data/site";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ActionBar } from "@/components/layout/ActionBar";
import { Logo } from "@/components/ui/Logo";

/**
 * Where a plain (non-JavaScript) form submission lands after it succeeds.
 *
 * This page only ever says "received", and that is always true: api/lead.php
 * redirects here only on success and renders its own page when something goes
 * wrong. Visitors with JavaScript never get here — they see the result inline
 * on the form instead.
 */
export const metadata: Metadata = {
  title: "Arizangiz qabul qilindi — HAMKOR SAVDO",
  robots: { index: false, follow: true },
};

export default function ThanksPage() {
  return (
    <>
      <Header />
      <main id="asosiy" tabIndex={-1}>
        <section className="on-purple relative flex min-h-[70svh] items-center overflow-hidden bg-purple py-24 text-white">
          <Logo
            variant="mark"
            className="pointer-events-none absolute left-1/2 top-1/2 h-52 w-auto -translate-x-1/2 -translate-y-1/2 text-white/[0.07] sm:h-72"
          />
          <div className="relative mx-auto max-w-2xl px-5 text-center sm:px-8">
            <p className="kicker">Rahmat</p>
            <h1 className="display mt-4 text-4xl sm:text-5xl">Arizangiz qabul qilindi</h1>
            <p className="mt-5 text-lg leading-relaxed text-on-purple-2">
              Mutaxassisimiz siz qoldirgan raqamga tez orada qo&apos;ng&apos;iroq qiladi va barcha
              savollaringizga javob beradi.
            </p>

            <div className="mt-9 flex flex-wrap items-center justify-center gap-3">
              <a href={`tel:${site.phone}`} className="btn btn-yellow">
                {site.phoneDisplay}
              </a>
              <Link href="/" className="btn btn-outline">
                Bosh sahifaga qaytish
              </Link>
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
        </section>
      </main>
      <Footer />
      {/* Telefonda `body` ning pastki bo'shlig'i shu qator uchun ajratilgan
          (globals.css) — qator bo'lmasa, futer tagida sababsiz bo'sh joy
          qolar edi. Ariza endi yuborilgani uchun ikkinchi tugma formaga
          emas, filiallar ro'yxatiga olib boradi. */}
      <ActionBar href="/filiallar/" label="Filiallar" />
    </>
  );
}

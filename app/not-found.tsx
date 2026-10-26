import Link from "next/link";
import type { Metadata } from "next";
import { branches } from "@/data/branches";
import { site } from "@/data/site";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ActionBar } from "@/components/layout/ActionBar";

/**
 * The 404 page.
 *
 * Without this file Next serves its own: an English sentence on a white page,
 * carrying the home page's <title>. Someone who mistypes an address then meets
 * a page that is not in their language, not obviously this company's, and
 * offers no way onward — and every wrong URL reports itself to Google under the
 * home page's name.
 *
 * So this one does the three things a shop's 404 has to do: say plainly that
 * the page is gone, hand over the phone number, and list the four branches so
 * the visit is not wasted.
 */
export const metadata: Metadata = {
  title: "Sahifa topilmadi — HAMKOR SAVDO",
  description:
    "Siz qidirgan sahifa mavjud emas. Filiallarimiz manzillari va telefon raqamlari shu yerda.",
  robots: { index: false, follow: true },
};

export default function NotFound() {
  return (
    <>
      <Header />
      <main id="asosiy" tabIndex={-1}>
        {/* Sahna: ulkan "404" va atrofida "adashgan" real 3D mahsulotlar
            (tools/hero3d/hamkor3d.js, "lost"). 3D yuklanmasa — raqamning o'zi. */}
        <section className="nf-stage on-purple text-white">
          <div className="hero-bg" aria-hidden="true">
            <span className="hero-orb hero-orb-1" />
            <span className="hero-orb hero-orb-2" />
            <span className="hero-grid" />
          </div>
          <div className="nf-3d" data-hero3d="lost" aria-hidden="true" />
          <p className="nf-num numeral" aria-hidden="true">
            404
          </p>
          <div className="nf-copy">
            <p className="kicker in-1">Xatolik 404</p>
            <h1 className="display mt-3 break-words text-3xl leading-[1.05] min-[400px]:text-4xl sm:text-5xl in-3">
              Bu sahifa topilmadi
            </h1>
            <p className="mt-5 text-lg leading-relaxed text-on-purple-2 in-4">
              Manzil noto&apos;g&apos;ri yozilgan yoki sahifa ko&apos;chirilgan bo&apos;lishi mumkin.
              Quyidagi filiallardan birini tanlang yoki bizga qo&apos;ng&apos;iroq qiling — savolingizga
              javob beramiz.
            </p>

            <div className="in-5 mt-8 flex flex-wrap justify-center gap-3">
              <Link href="/" className="btn btn-yellow btn-glow">
                Bosh sahifaga qaytish
              </Link>
              <a href={`tel:${site.phone}`} className="btn btn-outline">
                {site.phoneDisplay}
              </a>
            </div>
          </div>
        </section>

        <section className="mx-auto max-w-3xl px-5 pb-20 pt-14 sm:px-8">
          <h2 className="display text-2xl">Filiallarimiz</h2>
          <ul data-m="" data-m-stagger className="mt-5 grid gap-3 sm:grid-cols-2">
            {branches.map((b) => (
              <li key={b.id}>
                <Link href={`/filiallar/${b.id}`} className="card card-hover block h-full p-5">
                  <span className="numeral text-xl tracking-[0.06em] text-purple">{b.city}</span>
                  <span className="mt-1 block text-sm text-ink-2">{b.landmark}</span>
                  <span className="mt-3 block text-sm font-semibold text-purple">Batafsil &rarr;</span>
                </Link>
              </li>
            ))}
          </ul>
        </section>
      </main>
      <Footer />
      <ActionBar href="/#ariza" />
    </>
  );
}

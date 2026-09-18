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
      <main id="asosiy" tabIndex={-1} className="mx-auto max-w-3xl px-5 pb-20 pt-36 sm:px-8 sm:pt-44">
        <p className="kicker">Xatolik 404</p>
        <h1 className="display mt-3 text-4xl leading-[1.05] sm:text-5xl">
          Bu sahifa topilmadi
        </h1>
        <p className="mt-5 text-lg leading-relaxed text-ink-2">
          Manzil noto&apos;g&apos;ri yozilgan yoki sahifa ko&apos;chirilgan bo&apos;lishi mumkin.
          Quyidagi filiallardan birini tanlang yoki bizga qo&apos;ng&apos;iroq qiling — savolingizga
          javob beramiz.
        </p>

        <div className="mt-8 flex flex-wrap gap-3">
          <Link href="/" className="btn btn-primary">
            Bosh sahifaga qaytish
          </Link>
          <a href={`tel:${site.phone}`} className="btn btn-outline">
            {site.phoneDisplay}
          </a>
        </div>

        <h2 className="display mt-14 text-2xl">Filiallarimiz</h2>
        <ul className="mt-5 grid gap-3 sm:grid-cols-2">
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
      </main>
      <Footer />
      <ActionBar href="/#ariza" />
    </>
  );
}

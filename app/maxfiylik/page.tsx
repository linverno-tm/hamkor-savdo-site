import Link from "next/link";
import type { Metadata } from "next";
import { site } from "@/data/site";
import { absolute } from "@/lib/seo";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ActionBar } from "@/components/layout/ActionBar";

/**
 * Privacy notice for the lead form.
 *
 * The form collects a name and a phone number, which makes it personal data
 * under Uzbek law, and until now the site said nothing about what happens to
 * it. This page states only what the site actually does — the fields it sends,
 * where they go, and how to have them deleted.
 *
 * [LEGAL DETAILS REQUIRED] A formal notice normally names the operator: the
 * registered company name, INN and legal address. Those are not published
 * anywhere the site can verify, so they are deliberately absent rather than
 * invented. Once the owner supplies them they belong at the top of this page.
 */
export const metadata: Metadata = {
  title: "Maxfiylik siyosati — HAMKOR SAVDO",
  description:
    "Saytdagi ariza formasi qanday ma'lumot yig'adi, u qayerga boradi, qanday o'chiriladi va tashriflar statistikasi qanday yuritiladi.",
  alternates: { canonical: absolute("/maxfiylik") },
  robots: { index: true, follow: true },
};

const yigiladi = [
  ["Ismingiz", "Sizga qanday murojaat qilishni bilishimiz uchun."],
  ["Telefon raqamingiz", "Qayta qo'ng'iroq qilish uchun. Boshqa maqsadda ishlatilmaydi."],
  ["Tanlagan filialingiz", "Arizani o'sha filialning xodimiga yo'naltirish uchun."],
  ["So'rovingiz matni", "Ixtiyoriy. Siz yozgan bo'lsangiz — qo'ng'iroqqa tayyorgarlik uchun."],
  [
    "Ariza yuborilgan sahifa manzili",
    "Qaysi bo'lim orqali murojaat qilganingizni bilish uchun. Bu texnik ma'lumot.",
  ],
];

export default function PrivacyPage() {
  return (
    <>
      <Header />
      <main className="mx-auto max-w-3xl px-5 pb-20 pt-32 sm:px-8 sm:pt-40">
        <nav aria-label="Yo'l" className="text-sm text-ink-3">
          <Link href="/" className="tap hover:text-purple">
            Bosh sahifa
          </Link>
          <span className="px-2" aria-hidden="true">
            /
          </span>
          <span className="text-ink-2">Maxfiylik siyosati</span>
        </nav>

        <p className="kicker mt-6">Maxfiylik</p>
        <h1 className="display mt-3 text-4xl leading-[1.05] sm:text-5xl">
          Ma&apos;lumotlaringiz bilan nima qilamiz
        </h1>
        <p className="mt-5 text-lg leading-relaxed text-ink-2">
          Saytda bitta forma bor — ariza qoldirish formasi. Quyida u nima yig&apos;ishi, bu
          ma&apos;lumot qayerga borishi va uni qanday o&apos;chirish mumkinligi yozilgan.
          Bundan tashqari sayt tashriflar statistikasini yuritadi — bu haqda sahifa oxirida.
        </p>

        <h2 className="display mt-12 text-2xl">Nima yig&apos;iladi</h2>
        <dl className="mt-5 divide-y divide-line border-y border-line">
          {yigiladi.map(([nom, sabab]) => (
            <div key={nom} className="grid gap-1 py-4 sm:grid-cols-[13rem_1fr] sm:gap-6">
              <dt className="font-semibold text-ink">{nom}</dt>
              <dd className="text-ink-2">{sabab}</dd>
            </div>
          ))}
        </dl>

        <h2 className="display mt-12 text-2xl">Qayerga boradi</h2>
        <p className="mt-4 leading-relaxed text-ink-2">
          Ariza to&apos;g&apos;ridan-to&apos;g&apos;ri {site.name} savdo bo&apos;limining
          ichki Telegram guruhiga yuboriladi. Uni faqat mijozlar bilan ishlaydigan xodimlar
          ko&apos;radi. Ma&apos;lumot uchinchi shaxslarga sotilmaydi, reklama uchun berilmaydi va
          boshqa hech qayerga uzatilmaydi.
        </p>
        <p className="mt-4 leading-relaxed text-ink-2">
          Saytning o&apos;zida ma&apos;lumotlar bazasi yo&apos;q — arizalar serverda
          saqlanmaydi, faqat xabar sifatida yetkaziladi.
        </p>

        <h2 className="display mt-12 text-2xl">Qancha saqlanadi</h2>
        <p className="mt-4 leading-relaxed text-ink-2">
          Ariza sizning so&apos;rovingiz hal bo&apos;lgunicha savdo bo&apos;limida saqlanadi.
          Siz so&apos;rasangiz, undan oldin ham o&apos;chiriladi.
        </p>

        <h2 className="display mt-12 text-2xl">O&apos;chirishni so&apos;rash</h2>
        <p className="mt-4 leading-relaxed text-ink-2">
          Ma&apos;lumotlaringizni o&apos;chirishni yoki qo&apos;ng&apos;iroqlarni to&apos;xtatishni
          xohlasangiz, shu raqamga qo&apos;ng&apos;iroq qiling — hech qanday ariza yozish shart emas.
        </p>
        <div className="mt-6 flex flex-wrap gap-3">
          <a href={`tel:${site.phone}`} className="btn btn-primary">
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

        <h2 className="display mt-12 text-2xl">Tashriflar statistikasi va cookie</h2>
        <p className="mt-4 leading-relaxed text-ink-2">
          Saytni yaxshilash uchun Yandex Metrika xizmatidan foydalanamiz. U sayt nechta odam
          ochganini, ular qaysi shahardan va qaysi manbadan (Google, Instagram, Telegram)
          kelganini, qaysi qurilmadan kirganini hamda sahifada qanday harakat qilganini
          (bosishlar, sahifani aylantirish) ko&apos;rsatadi. Buning uchun Yandex brauzeringizga
          cookie fayllarini yozadi. Bu ma&apos;lumotlar umumlashgan holda ko&apos;riladi va sizning
          ismingiz bilan bog&apos;lanmaydi.
        </p>
        <p className="mt-4 leading-relaxed text-ink-2">
          Ariza formasiga yozgan ismingiz, telefon raqamingiz va so&apos;rovingiz matni
          statistika xizmatiga yuborilmaydi — ular faqat yuqorida aytilgan yo&apos;l bilan
          savdo bo&apos;limiga boradi.
        </p>
        <p className="mt-4 leading-relaxed text-ink-2">
          Shuningdek, sayt siz tanlagan yozuvni (lotin yoki kirill) keyingi safar eslab qolish
          uchun uni brauzeringizda saqlaydi. Statistikani to&apos;xtatish uchun brauzer
          sozlamalarida cookie fayllarini o&apos;chirib qo&apos;yishingiz mumkin — sayt baribir
          to&apos;liq ishlaydi.
        </p>
      </main>
      <Footer />
      <ActionBar />
    </>
  );
}

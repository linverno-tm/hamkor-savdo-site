import type { Metadata } from "next";
import { branches } from "@/data/branches";
import { site } from "@/data/site";
import { topics } from "@/data/topics";
import { absolute } from "@/lib/seo";
import { ActionBar } from "@/components/layout/ActionBar";
import { RuHeader, RuFooter, CITY_RU } from "@/components/layout/RuChrome";
import { LeadForm } from "@/components/sections/LeadForm";

/**
 * Ruscha bosh sahifa. Rus tilida qidiradigan odam ("рассрочка Андижан",
 * "магазин техники Шахрихан") uchun kirish nuqtasi: yo'nalishlar, shartlar,
 * magazinlar va ariza. To'liq o'zbekcha bosh sahifaning tarjimasi emas —
 * qidiruvga kerakli qismi.
 */

const M = site.facts.installmentMonthsMax;
const title = `HAMKOR SAVDO — бытовая техника, мебель и золото в рассрочку | Андижан, Шахрихан, Асака`;
const description = `Магазины HAMKOR SAVDO в Андижанской области: бытовая техника, мебель и золотые украшения в рассрочку до ${M} месяцев по паспорту и пластиковой карте. Бесплатная доставка и установка по Андижанской области. ${branches.length} магазина.`;

export const metadata: Metadata = {
  title,
  description,
  alternates: {
    canonical: absolute("/ru"),
    languages: { uz: absolute("/"), "uz-Cyrl": absolute("/uz-kr"), ru: absolute("/ru"), "x-default": absolute("/") },
  },
  openGraph: { title, description, type: "website", locale: "ru_RU", siteName: site.name },
};

const faq = [
  {
    q: "Какие документы нужны для рассрочки?",
    a: "Только паспорт и пластиковая карта — без поручителей и справок.",
  },
  {
    q: "На какой срок можно оформить рассрочку?",
    a: `До ${M} месяцев. Ежемесячный платёж зависит от цены и срока — точную сумму рассчитаем по телефону или в магазине.`,
  },
  {
    q: "Есть ли доставка?",
    a: "По Андижанской области доставка и установка бесплатные. В другие регионы Узбекистана тоже доставляем.",
  },
  {
    q: "Можно оформить товар, которого нет в вашем магазине?",
    a: "Да. Назовите товар и цену, которую видели в другом месте, — оформим его в рассрочку.",
  },
];

export default function RuHome() {
  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: faq.map((f) => ({ "@type": "Question", name: f.q, acceptedAnswer: { "@type": "Answer", text: f.a } })),
  };
  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />
      <RuHeader uzHref="/" />
      <main id="asosiy" tabIndex={-1}>
        <section className="pt-28 sm:pt-36">
          <div className="mx-auto max-w-7xl px-5 pb-16 sm:px-8">
            <p className="kicker">Андижанская область · {branches.length} магазина</p>
            <h1 className="display mt-3 max-w-4xl text-4xl sm:text-5xl lg:text-6xl">
              Бытовая техника, мебель и золото в рассрочку
            </h1>
            <p className="mt-6 max-w-2xl text-lg leading-relaxed text-ink-2 sm:text-xl">
              Более {site.facts.productCount.replace("+", "")} товаров в одном магазине. Рассрочка до {M} месяцев — нужны
              только паспорт и пластиковая карта. Бесплатная доставка и установка по Андижанской области.
            </p>
            <div className="mt-8 flex flex-wrap gap-3">
              <a href="#ariza" className="btn btn-primary">
                Оставить заявку
              </a>
              <a href={`tel:${site.phone}`} className="btn btn-outline">
                {site.phoneDisplay}
              </a>
            </div>
          </div>
        </section>

        <section aria-labelledby="ru-dirs" className="bg-ground-2 py-16 sm:py-20">
          <div className="mx-auto max-w-7xl px-5 sm:px-8">
            <h2 id="ru-dirs" className="display text-3xl sm:text-4xl">
              Что можно купить в рассрочку
            </h2>
            <ul className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
              {topics.map((t) => (
                <li key={t.id}>
                  <a href={`${t.ruPath}/`} className="card card-hover flex h-full flex-col overflow-hidden">
                    {t.photo ? (
                      <img src={t.photo.src} width={900} height={675} alt={t.photo.alt.ru} loading="lazy" decoding="async" className="aspect-[4/3] w-full object-cover" />
                    ) : (
                      <span className="on-purple flex aspect-[4/3] w-full flex-col justify-center bg-purple p-6 text-white">
                        <span className="numeral text-6xl text-yellow">{M}</span>
                        <span className="display text-xl">месяцев</span>
                      </span>
                    )}
                    <span className="p-6">
                      <span className="numeral block text-2xl text-purple">{t.ru.kicker}</span>
                      <span className="mt-2 block text-sm leading-relaxed text-ink-2">{t.ru.lead}</span>
                    </span>
                  </a>
                </li>
              ))}
            </ul>
          </div>
        </section>

        <section aria-labelledby="ru-branches" className="py-16 sm:py-20">
          <div className="mx-auto max-w-7xl px-5 sm:px-8">
            <h2 id="ru-branches" className="display text-3xl sm:text-4xl">
              Наши магазины
            </h2>
            <ul className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              {branches.map((b) => (
                <li key={b.id} className="card p-6">
                  <span className="numeral block text-2xl text-purple">{CITY_RU[b.city] ?? b.city}</span>
                  <span className="mt-1 block text-sm text-ink-2">{b.landmark}</span>
                  <span className="mt-3 block text-sm text-ink-3">Часы работы: {b.hours}</span>
                  <a href={`tel:${b.phone ?? site.phone}`} className="mt-1 block text-sm font-semibold text-purple hover:underline">
                    {b.phoneDisplay ?? site.phoneDisplay}
                  </a>
                </li>
              ))}
            </ul>
          </div>
        </section>

        <section aria-labelledby="ru-faq" className="bg-ground-2 py-16 sm:py-20">
          <div className="mx-auto max-w-3xl px-5 sm:px-8">
            <h2 id="ru-faq" className="display text-3xl sm:text-4xl">
              Частые вопросы
            </h2>
            <div className="mt-8 divide-y divide-line border-y border-line">
              {faq.map((f) => (
                <details key={f.q} className="group py-5">
                  <summary className="flex cursor-pointer list-none items-start justify-between gap-6 text-lg font-semibold text-ink">
                    {f.q}
                    <span aria-hidden="true" className="mt-1 text-2xl leading-none text-purple transition-transform group-open:rotate-45">
                      +
                    </span>
                  </summary>
                  <p className="mt-3 leading-relaxed text-ink-2">{f.a}</p>
                </details>
              ))}
            </div>
          </div>
        </section>

        <LeadForm lang="ru" page="/ru/" />
      </main>
      <RuFooter />
      <ActionBar label="Оставить заявку" callLabel="Позвонить" />
    </>
  );
}

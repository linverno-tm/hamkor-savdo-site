import type { Metadata } from "next";
import { branches } from "@/data/branches";
import { site } from "@/data/site";
import { topics } from "@/data/topics";
import { absolute } from "@/lib/seo";
import { ActionBar } from "@/components/layout/ActionBar";
import { RuHeader, RuFooter, CITY_RU } from "@/components/layout/RuChrome";
import { LeadForm } from "@/components/sections/LeadForm";
import { SplitWords } from "@/components/ui/SplitWords";
import { OpenStatus } from "@/components/ui/OpenStatus";

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
        <section className="tp-hero on-purple text-white" data-spot>
          <div className="hero-bg" aria-hidden="true">
            <span className="hero-orb hero-orb-1" />
            <span className="hero-orb hero-orb-2" />
            <span className="hero-orb hero-orb-3" />
            <span className="hero-spot" />
            <span className="hero-grid" />
          </div>
          <div className="wrap relative">
            <p className="hero-pill in-1">
              <span className="hero-pill-dot" aria-hidden="true" />
              Андижанская область · {branches.length} магазина
            </p>
            <h1 className="tp-title !max-w-[20ch]">
              <span className="hero-line">
                <span className="in-2">Бытовая техника, мебель и золото в рассрочку</span>
              </span>
            </h1>
            <p className="hero-lead in-4 !max-w-[42rem]">
              Более {site.facts.productCount.replace("+", "")} товаров в одном магазине. Рассрочка до {M} месяцев — нужны
              только паспорт и пластиковая карта. Бесплатная доставка и установка по Андижанской области.
            </p>
            <div className="in-5 mt-9 flex flex-wrap gap-3">
              <a href="#ariza" className="btn btn-yellow btn-glow">
                Оставить заявку
              </a>
              <a href={`tel:${site.phone}`} className="btn btn-outline">
                {site.phoneDisplay}
              </a>
            </div>
          </div>
        </section>

        <section aria-labelledby="ru-dirs" className="sec">
          <div className="wrap">
            <div data-m>
              <h2 id="ru-dirs" className="sh-title">
                <SplitWords text="Что можно купить в рассрочку" />
              </h2>
            </div>
            <ul data-m="" data-m-stagger className="tp-others">
              {topics.map((t) => (
                <li key={t.id}>
                  <a href={`${t.ruPath}/`} className="tp-other !min-h-[20rem]">
                    {t.photo ? (
                      <img src={t.photo.src} width={900} height={675} alt={t.photo.alt.ru} loading="lazy" decoding="async" className="tp-other-img" />
                    ) : (
                      <span className="absolute left-6 top-5 flex flex-col">
                        <span className="numeral text-7xl leading-none text-yellow">{M}</span>
                        <span className="text-xl font-extrabold">месяцев</span>
                      </span>
                    )}
                    <span className="tp-other-shade" aria-hidden="true" />
                    <span className="tp-other-body">
                      <span className="tp-other-kicker">{t.ru.kicker}</span>
                      <span className="tp-other-h">{t.ru.lead}</span>
                    </span>
                    <span aria-hidden="true" className="tp-other-arrow">
                      &rarr;
                    </span>
                  </a>
                </li>
              ))}
            </ul>
          </div>
        </section>

        <section aria-labelledby="ru-branches" className="sec bg-ground-2">
          <div className="wrap">
            <div data-m>
              <h2 id="ru-branches" className="sh-title">
                <SplitWords text="Наши магазины" />
              </h2>
            </div>
            <ul data-m="" data-m-stagger className="tp-branches">
              {branches.map((b) => (
                <li key={b.id} className="branch-item">
                  <div className="branch-top">
                    <span className="branch-idx numeral" aria-hidden="true">
                      {b.index}
                    </span>
                    <div className="min-w-0 flex-1">
                      <p className="branch-city">{CITY_RU[b.city] ?? b.city}</p>
                      <p className="branch-land">{b.landmark}</p>
                      <OpenStatus hours={b.hours} closed={b.closed} />
                    </div>
                  </div>
                  <p className="branch-addr">Часы работы: {b.hours}</p>
                  <div className="branch-actions">
                    <a href={`tel:${b.phone ?? site.phone}`} className="branch-call">
                      {b.phoneDisplay ?? site.phoneDisplay}
                    </a>
                  </div>
                </li>
              ))}
            </ul>
          </div>
        </section>

        <section aria-labelledby="ru-faq" className="sec faq">
          <div className="wrap faq-grid">
            <div className="faq-side">
              <div data-m className="faq-sticky">
                <h2 id="ru-faq" className="sh-title">
                  <SplitWords text="Частые вопросы" />
                </h2>
              </div>
            </div>
            <div data-m="" data-m-stagger className="faq-list">
              {faq.map((f) => (
                <details key={f.q} className="faq-item">
                  <summary className="faq-q">
                    <span>{f.q}</span>
                    <span aria-hidden="true" className="faq-plus" />
                  </summary>
                  <p className="faq-a">{f.a}</p>
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

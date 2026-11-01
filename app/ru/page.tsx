import type { Metadata } from "next";
import { branches } from "@/data/branches";
import { site } from "@/data/site";
import { topics } from "@/data/topics";
import { absolute } from "@/lib/seo";
import { ActionBar } from "@/components/layout/ActionBar";
import { RuHeader, RuFooter } from "@/components/layout/RuChrome";
import { LeadForm } from "@/components/sections/LeadForm";
import { SplitWords } from "@/components/ui/SplitWords";
import { UyTopHero } from "@/components/uy/UyTopHero";
import { UyUylar } from "@/components/uy/UyUylar";
import { UyFiliallar } from "@/components/uy/UyFiliallar";
import "@/app/uy.css";

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
      <main id="asosiy" tabIndex={-1} className="uy-page">
        {/* Bosh sahifa bilan bir xil "uy" tili; matn — avvalgi ruscha matn. */}
        <UyTopHero
          lang="ru"
          title="Бытовая техника, мебель и золото в рассрочку"
          lead={`Более ${site.facts.productCount.replace("+", "")} товаров в одном магазине. Рассрочка до ${M} месяцев — нужны только паспорт и пластиковая карта. Бесплатная доставка и установка по Андижанской области.`}
          applyLabel="Оставить заявку"
          crumbs={<p className="uh-crumbs">Андижанская область · {branches.length} магазина</p>}
          photo={{
            src: "/uy/xona-640.webp",
            srcSet: "/uy/xona-640.webp 640w, /uy/xona-1000.webp 1000w, /uy/xona-1400.webp 1400w",
            sizes: "(min-width: 1024px) 46vw, 86vw",
            w: 1000,
            h: 732,
            alt: "Светлая гостиная: телевизор, комод и кресло",
            position: "30% 60%",
          }}
        />

        <section aria-labelledby="ru-dirs" className="uy-sec uy">
          <div className="u-wrap">
            <h2 id="ru-dirs" className="u-h2">
              Что можно купить в рассрочку
            </h2>
            <UyUylar lang="ru" items={topics} />
          </div>
        </section>

        <UyFiliallar lang="ru" title="Наши магазины" />

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

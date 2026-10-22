import type { Metadata } from "next";
import Link from "next/link";
import { branches } from "@/data/branches";
import { site } from "@/data/site";
import { topics, type Topic, type Lang } from "@/data/topics";
import { installmentSteps } from "@/data/installment";
import { absolute } from "@/lib/seo";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ActionBar } from "@/components/layout/ActionBar";
import { RuHeader, RuFooter, CITY_RU } from "@/components/layout/RuChrome";
import { LeadForm } from "@/components/sections/LeadForm";
import { SplitWords } from "@/components/ui/SplitWords";
import { ScrubText } from "@/components/ui/ScrubText";
import { OpenStatus } from "@/components/ui/OpenStatus";

/**
 * Yo'nalish sahifasi (/texnika/, /ru/tehnika/ ...). Matn `data/topics.ts` da.
 *
 * Til havolalari (hreflang): o'zbekcha sahifaga lotin/kirill juftligini
 * `tools/uz-kr-build.mjs` qo'yadi, bu yerda faqat ruscha havola beriladi —
 * skript uni o'chirmaydi. Ruscha sahifa esa skriptdan o'tmaydi, shuning
 * uchun uning to'liq ro'yxati shu yerda.
 */

const UI = {
  uz: {
    home: "Bosh sahifa",
    apply: "Ariza qoldirish",
    call: "Qo'ng'iroq",
    more: "Batafsil",
    howTitle: "Qanday rasmiylashtiriladi",
    branchesTitle: "Filiallarimiz",
    branchesLead: "Kelib ko'ring — mahsulotni jonli ko'rib, joyida rasmiylashtirasiz.",
    hours: "Ish vaqti",
    otherTitle: "Boshqa yo'nalishlar",
    faqTitle: "Ko'p so'raladigan savollar",
    pageInfo: "Filial sahifasi",
  },
  ru: {
    home: "Главная",
    apply: "Оставить заявку",
    call: "Позвонить",
    more: "Подробнее",
    howTitle: "Как оформить",
    branchesTitle: "Наши магазины",
    branchesLead: "Приходите — посмотрите товар вживую и оформите рассрочку на месте.",
    hours: "Часы работы",
    otherTitle: "Другие разделы",
    faqTitle: "Частые вопросы",
    pageInfo: "Страница магазина (на узбекском)",
  },
};

const STEPS_RU = [
  { index: "01", title: "Выберите товар", detail: "Приходите в магазин — выберите нужное в отделе техники, мебели или золота." },
  { index: "02", title: "Оформите", detail: "Оформляем на месте по паспорту и пластиковой карте." },
  { index: "03", title: "Заберите покупку", detail: "Забираете в тот же день или мы доставим." },
  { index: "04", title: "Платите частями", detail: `Оплата частями до ${site.facts.installmentMonthsMax} месяцев.` },
];

/* Ruscha sahifada ichki havolalar oddiy <a>: ular Next marshrutizatoridan
   o'tmasin — o'zbekcha sahifaga o'tishda React ruscha sarlavhani saqlab
   qolmasligi uchun to'liq yuklanish kerak. */
function LangLink({ lang, to, className, children }: { lang: Lang; to: string; className?: string; children: React.ReactNode }) {
  return lang === "ru" ? (
    <a href={to} className={className}>
      {children}
    </a>
  ) : (
    <Link href={to} className={className}>
      {children}
    </Link>
  );
}

const href = (lang: Lang, t: Topic) => `${lang === "ru" ? t.ruPath : t.path}/`;

export function topicMetadata(topic: Topic, lang: Lang): Metadata {
  const t = topic[lang];
  const uz = absolute(topic.path);
  const ru = absolute(topic.ruPath);
  return {
    title: t.title,
    description: t.description,
    alternates:
      lang === "uz"
        ? { canonical: uz, languages: { ru } }
        : {
            canonical: ru,
            languages: { uz, "uz-Cyrl": absolute(`/uz-kr${topic.path}`), ru, "x-default": uz },
          },
    openGraph: {
      title: t.title,
      description: t.description,
      type: "website",
      locale: lang === "ru" ? "ru_RU" : "uz_UZ",
      siteName: site.name,
      images: topic.photo ? [{ url: absolute(topic.photo.src) }] : undefined,
    },
  };
}

export function TopicPage({ topic, lang }: { topic: Topic; lang: Lang }) {
  const t = topic[lang];
  const ui = UI[lang];
  const self = href(lang, topic);
  const homeHref = lang === "ru" ? "/ru/" : "/";
  const cityName = (c: string) => (lang === "ru" ? CITY_RU[c] ?? c : c);
  const steps = lang === "ru" ? STEPS_RU : installmentSteps;
  const others = topics.filter((o) => o.id !== topic.id);

  const breadcrumbLd = {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: [
      { "@type": "ListItem", position: 1, name: ui.home, item: absolute(homeHref) },
      { "@type": "ListItem", position: 2, name: t.kicker, item: absolute(self) },
    ],
  };
  const faqLd = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: t.faq.map((f) => ({ "@type": "Question", name: f.q, acceptedAnswer: { "@type": "Answer", text: f.a } })),
  };


  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify([breadcrumbLd, faqLd]) }} />
      {lang === "ru" ? <RuHeader uzHref={`${topic.path}/`} /> : <Header />}
      <main id="asosiy" tabIndex={-1}>
        {/* ---- asosiy qism: binafsha sahna, o'ngda yo'nalish surati ---- */}
        <section className={`tp-hero tp-hero--${topic.id} on-purple text-white`}>
          <div className="hero-bg" aria-hidden="true">
            <span className="hero-orb hero-orb-1" />
            <span className="hero-orb hero-orb-2" />
            <span className="hero-grid" />
          </div>
          {/* Orqada yo'nalish nomi ulkan kontur harflarda — sekin suzadi
              (app/pages.css > .tp-giant). Bezak, matn yuqorida bor. */}
          <span className="tp-giant numeral" aria-hidden="true">
            {t.kicker}
          </span>
          <div className="wrap relative grid items-center gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:gap-14">
            <div>
              <nav aria-label={lang === "ru" ? "Навигация" : "Qayerdaman"} className="tp-crumbs in-1">
                <LangLink lang={lang} to={homeHref} className="hover:text-white">
                  {ui.home}
                </LangLink>
                <span className="mx-2" aria-hidden="true">
                  /
                </span>
                <span className="text-white">{t.kicker}</span>
              </nav>
              <p className="hero-pill in-1 mt-7">
                <span className="hero-pill-dot" aria-hidden="true" />
                {t.kicker}
              </p>
              <h1 className="tp-title">
                <span className="hero-line">
                  <span className="in-2">{t.h1}</span>
                </span>
              </h1>
              <p className="hero-lead in-4">{t.lead}</p>
              <div className="in-5 mt-9 flex flex-wrap gap-3">
                <a href="#ariza" className="btn btn-yellow btn-glow">
                  {ui.apply}
                </a>
                <a href={`tel:${site.phone}`} className="btn btn-outline">
                  {site.phoneDisplay}
                </a>
              </div>
            </div>
            {topic.photo && topic.id === "tilla" ? (
              /* Tilla — zargarlik do'konlari uslubida: iliq fondagi baland
                 surat va pastida "mahsulot kartasi" kvadrat strelka bilan. */
              <div className="jewel in-7">
                <div className="jewel-photo">
                  <img
                    src={topic.photo.src}
                    width={900}
                    height={675}
                    alt={topic.photo.alt[lang]}
                    decoding="async"
                    fetchPriority="high"
                  />
                </div>
                <a href="#ariza" className="jewel-card">
                  <span>
                    <span className="jewel-name">{t.kicker}</span>
                    <span className="jewel-note">
                      {site.facts.installmentMonthsMax} {lang === "ru" ? "месяцев рассрочки" : "oygacha muddatli to'lov"}
                    </span>
                  </span>
                  <span className="jewel-arrow" aria-hidden="true">
                    &rarr;
                  </span>
                </a>
              </div>
            ) : topic.photo ? (
              <div className="tp-photo in-7">
                <img
                  src={topic.photo.src}
                  width={900}
                  height={675}
                  alt={topic.photo.alt[lang]}
                  decoding="async"
                  fetchPriority="high"
                  className="h-full w-full object-cover"
                />
              </div>
            ) : (
              <div className="tp-badge in-7">
                <p className="tp-badge-num numeral">{site.facts.installmentMonthsMax}</p>
                <p className="tp-badge-title">{lang === "ru" ? "месяцев рассрочки" : "oygacha muddatli to'lov"}</p>
                <p className="mt-4 text-on-purple-2">{lang === "ru" ? "Паспорт + пластиковая карта" : "Pasport + plastik karta"}</p>
              </div>
            )}
          </div>
        </section>

        {/* ---- asosiy afzalliklar ---- */}
        <section aria-label={t.kicker} className="tp-points">
          <ul data-m="" data-m-stagger className="wrap tp-points-grid">
            {t.points.map((p, i) => (
              <li key={p.title} className={`tile ${i === 0 ? "tile-yellow" : ""}`}>
                <span className="tp-point-n numeral" aria-hidden="true">
                  {String(i + 1).padStart(2, "0")}
                </span>
                <div>
                  <p className="tp-point-title">{p.title}</p>
                  <p className="tile-note mt-2 leading-relaxed">{p.text}</p>
                </div>
              </li>
            ))}
          </ul>
        </section>

        {/* ---- matn: aylantirilgan sari so'zma-so'z yorishadi ---- */}
        <section aria-labelledby="topic-more" className="sec">
          <div className="wrap tp-body">
            <div data-m>
              <h2 id="topic-more" className="sh-title">
                <SplitWords text={ui.more} />
              </h2>
            </div>
            <div className="tp-body-text">
              {t.body.map((para, i) => (
                <p key={i} className="scrub">
                  <ScrubText text={para} />
                </p>
              ))}
            </div>
          </div>
        </section>

        {/* ---- qadamlar ---- */}
        <section aria-labelledby="topic-how" className="inst on-purple text-white">
          <div className="inst-bg" aria-hidden="true">
            <span className="inst-glow" />
          </div>
          <div className="wrap">
            <div data-m>
              <h2 id="topic-how" className="sh-title text-white">
                <SplitWords text={ui.howTitle} />
              </h2>
            </div>
            <ol data-m="" data-m-stagger className="tp-steps">
              {steps.map((s) => (
                <li key={s.index} className="tp-step">
                  <span className="tp-step-n numeral">{s.index}</span>
                  <p className="tp-step-title">{s.title}</p>
                  <p className="mt-2 leading-relaxed text-on-purple-2">{s.detail}</p>
                </li>
              ))}
            </ol>
          </div>
        </section>

        {/* ---- filiallar ---- */}
        <section aria-labelledby="topic-branches" className="sec">
          <div className="wrap">
            <div data-m className="sh">
              <h2 id="topic-branches" className="sh-title">
                <SplitWords text={ui.branchesTitle} />
              </h2>
              <p className="sh-lead">{ui.branchesLead}</p>
            </div>
            <ul data-m="" data-m-stagger className="tp-branches">
              {branches.map((b) => (
                <li key={b.id} className="branch-item flex flex-col">
                  <div className="branch-top">
                    <span className="branch-idx numeral" aria-hidden="true">
                      {b.index}
                    </span>
                    <div className="min-w-0 flex-1">
                      <p className="branch-city">{cityName(b.city)}</p>
                      <p className="branch-land">{b.landmark}</p>
                      <OpenStatus hours={b.hours} closed={b.closed} />
                    </div>
                  </div>
                  <p className="branch-addr">
                    {ui.hours}: {b.hours}
                  </p>
                  <div className="branch-actions mt-auto pt-4">
                    <a href={`tel:${b.phone ?? site.phone}`} className="branch-call">
                      {b.phoneDisplay ?? site.phoneDisplay}
                    </a>
                    <Link href={`/filiallar/${b.id}`} className="branch-link">
                      {ui.pageInfo} →
                    </Link>
                  </div>
                </li>
              ))}
            </ul>
          </div>
        </section>

        {/* ---- savol-javob ---- */}
        <section aria-labelledby="topic-faq" className="sec faq tp-faq">
          <div className="wrap faq-grid">
            <div className="faq-side">
              <div data-m className="faq-sticky">
                <h2 id="topic-faq" className="sh-title">
                  <SplitWords text={ui.faqTitle} />
                </h2>
              </div>
            </div>
            <div data-m="" data-m-stagger className="faq-list">
              {t.faq.map((f) => (
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

        <LeadForm lang={lang} page={self} lead={t.cta} />

        {/* ---- boshqa yo'nalishlar (ichki havolalar qidiruv uchun ham muhim) ---- */}
        <section aria-labelledby="topic-other" className="sec">
          <div className="wrap">
            <div data-m>
              <h2 id="topic-other" className="sh-title">
                <SplitWords text={ui.otherTitle} />
              </h2>
            </div>
            <ul data-m="" data-m-stagger className="tp-others">
              {others.map((o) => (
                <li key={o.id}>
                  <LangLink lang={lang} to={href(lang, o)} className="tp-other">
                    {o.photo ? (
                      <img src={o.photo.src} alt="" width={900} height={675} loading="lazy" decoding="async" className="tp-other-img" />
                    ) : null}
                    <span className="tp-other-shade" aria-hidden="true" />
                    <span className="tp-other-body">
                      <span className="tp-other-kicker">{o[lang].kicker}</span>
                      <span className="tp-other-h">{o[lang].h1}</span>
                    </span>
                    <span aria-hidden="true" className="tp-other-arrow">
                      &rarr;
                    </span>
                  </LangLink>
                </li>
              ))}
            </ul>
          </div>
        </section>
      </main>
      {lang === "ru" ? <RuFooter /> : <Footer />}
      <ActionBar label={ui.apply} callLabel={ui.call} />
    </>
  );
}

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
        {/* ---- asosiy qism ---- */}
        <section className="relative overflow-hidden pt-28 sm:pt-32">
          <div className="mx-auto grid max-w-7xl items-center gap-10 px-5 pb-16 sm:px-8 lg:grid-cols-[1.1fr_0.9fr] lg:gap-14">
            <div>
              <nav aria-label={lang === "ru" ? "Навигация" : "Qayerdaman"} className="text-sm text-ink-3">
                <LangLink lang={lang} to={homeHref} className="hover:text-purple">
                  {ui.home}
                </LangLink>
                <span className="mx-2" aria-hidden="true">
                  /
                </span>
                <span className="text-ink-2">{t.kicker}</span>
              </nav>
              <p className="kicker mt-8">{t.kicker}</p>
              <h1 className="display mt-3 text-4xl sm:text-5xl lg:text-6xl">{t.h1}</h1>
              <p className="mt-6 max-w-2xl text-lg leading-relaxed text-ink-2 sm:text-xl">{t.lead}</p>
              <div className="mt-8 flex flex-wrap gap-3">
                <a href="#ariza" className="btn btn-primary">
                  {ui.apply}
                </a>
                <a href={`tel:${site.phone}`} className="btn btn-outline">
                  {site.phoneDisplay}
                </a>
              </div>
            </div>
            {topic.photo ? (
              <div className="photo overflow-hidden rounded-[28px]">
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
              <div className="on-purple rounded-[28px] bg-purple p-10 text-white">
                <p className="numeral text-7xl text-yellow">{site.facts.installmentMonthsMax}</p>
                <p className="display mt-2 text-3xl">{lang === "ru" ? "месяцев рассрочки" : "oygacha muddatli to'lov"}</p>
                <p className="mt-4 text-on-purple-2">{lang === "ru" ? "Паспорт + пластиковая карта" : "Pasport + plastik karta"}</p>
              </div>
            )}
          </div>
        </section>

        {/* ---- asosiy afzalliklar ---- */}
        <section aria-label={t.kicker} className="bg-ground-2 py-14">
          <ul className="mx-auto grid max-w-7xl gap-5 px-5 sm:grid-cols-2 sm:px-8 lg:grid-cols-4">
            {t.points.map((p) => (
              <li key={p.title} className="card p-6">
                <p className="display text-2xl text-purple">{p.title}</p>
                <p className="mt-2 leading-relaxed text-ink-2">{p.text}</p>
              </li>
            ))}
          </ul>
        </section>

        {/* ---- matn ---- */}
        <section aria-labelledby="topic-more" className="py-16 sm:py-20">
          <div className="mx-auto max-w-3xl px-5 sm:px-8">
            <h2 id="topic-more" className="display text-3xl sm:text-4xl">
              {ui.more}
            </h2>
            {t.body.map((para, i) => (
              <p key={i} className="mt-5 text-lg leading-relaxed text-ink-2">
                {para}
              </p>
            ))}
          </div>
        </section>

        {/* ---- qadamlar ---- */}
        <section aria-labelledby="topic-how" className="on-purple bg-purple py-16 text-white sm:py-20">
          <div className="mx-auto max-w-7xl px-5 sm:px-8">
            <h2 id="topic-how" className="display text-3xl sm:text-4xl">
              {ui.howTitle}
            </h2>
            <ol className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
              {steps.map((s) => (
                <li key={s.index}>
                  <span className="numeral text-3xl text-yellow">{s.index}</span>
                  <p className="mt-2 text-xl font-semibold">{s.title}</p>
                  <p className="mt-2 leading-relaxed text-on-purple-2">{s.detail}</p>
                </li>
              ))}
            </ol>
          </div>
        </section>

        {/* ---- filiallar ---- */}
        <section aria-labelledby="topic-branches" className="py-16 sm:py-20">
          <div className="mx-auto max-w-7xl px-5 sm:px-8">
            <h2 id="topic-branches" className="display text-3xl sm:text-4xl">
              {ui.branchesTitle}
            </h2>
            <p className="mt-3 max-w-2xl text-ink-2">{ui.branchesLead}</p>
            <ul className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              {branches.map((b) => (
                <li key={b.id} className="card flex flex-col p-6">
                  <span className="numeral text-2xl text-purple">{cityName(b.city)}</span>
                  <span className="mt-1 text-sm text-ink-2">{b.landmark}</span>
                  <span className="mt-3 text-sm text-ink-3">
                    {ui.hours}: {b.hours}
                  </span>
                  <a href={`tel:${b.phone ?? site.phone}`} className="mt-1 text-sm font-semibold text-purple hover:underline">
                    {b.phoneDisplay ?? site.phoneDisplay}
                  </a>
                  <Link href={`/filiallar/${b.id}`} className="mt-4 text-sm font-semibold text-ink underline-offset-4 hover:text-purple hover:underline">
                    {ui.pageInfo} →
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </section>

        {/* ---- savol-javob ---- */}
        <section aria-labelledby="topic-faq" className="bg-ground-2 py-16 sm:py-20">
          <div className="mx-auto max-w-3xl px-5 sm:px-8">
            <h2 id="topic-faq" className="display text-3xl sm:text-4xl">
              {ui.faqTitle}
            </h2>
            <div className="mt-8 divide-y divide-line border-y border-line">
              {t.faq.map((f) => (
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

        <LeadForm lang={lang} page={self} lead={t.cta} />

        {/* ---- boshqa yo'nalishlar (ichki havolalar qidiruv uchun ham muhim) ---- */}
        <section aria-labelledby="topic-other" className="bg-ground-2 py-16">
          <div className="mx-auto max-w-7xl px-5 sm:px-8">
            <h2 id="topic-other" className="display text-3xl">
              {ui.otherTitle}
            </h2>
            <ul className="mt-8 grid gap-4 sm:grid-cols-3">
              {others.map((o) => (
                <li key={o.id}>
                  <LangLink lang={lang} to={href(lang, o)} className="card card-hover flex h-full flex-col p-6">
                    <span className="numeral text-2xl text-purple">{o[lang].kicker}</span>
                    <span className="mt-2 text-sm text-ink-2">{o[lang].h1}</span>
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

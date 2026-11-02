import type { Metadata } from "next";
import Link from "next/link";
import { site } from "@/data/site";
import { topics, type Topic, type Lang } from "@/data/topics";
import { installmentSteps } from "@/data/installment";
import { absolute, ldJson } from "@/lib/seo";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ActionBar } from "@/components/layout/ActionBar";
import { RuHeader, RuFooter } from "@/components/layout/RuChrome";
import { LeadForm } from "@/components/sections/LeadForm";
import { Vitrina3D } from "@/components/sections/Vitrina3D";
import { UyTopHero } from "@/components/uy/UyTopHero";
import { UyFiliallar } from "@/components/uy/UyFiliallar";
import { UyUylar } from "@/components/uy/UyUylar";
import { SplitWords } from "@/components/ui/SplitWords";
import { ScrubText } from "@/components/ui/ScrubText";
import { Rich } from "@/lib/content";
import "@/app/uy.css";

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
    readAll: "To'liq o'qish",
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
    readAll: "Читать полностью",
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
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: ldJson([breadcrumbLd, faqLd]) }} />
      {lang === "ru" ? <RuHeader uzHref={`${topic.path}/`} /> : <Header />}
      <main id="asosiy" tabIndex={-1} className="uy-page tpu">
        {/* ---- bosh qism: bosh sahifadagi "uy" tili — tomli oynada yo'nalish surati ---- */}
        <UyTopHero
          lang={lang}
          title={t.h1}
          lead={t.lead}
          applyLabel={ui.apply}
          crumbs={
            <nav aria-label={lang === "ru" ? "Навигация" : "Qayerdaman"} className="uh-crumbs">
              <LangLink lang={lang} to={homeHref}>
                {ui.home}
              </LangLink>
              <span aria-hidden="true">/</span>
              <span aria-current="page">{t.kicker}</span>
            </nav>
          }
          photo={
            topic.photo
              ? {
                  src: topic.photo.src.replace("-900.", "-480."),
                  srcSet: photoSrcSet(topic.photo.src),
                  sizes: PHOTO_SIZES,
                  w: 900,
                  h: 675,
                  alt: topic.photo.alt[lang],
                  lqip: topic.id,
                }
              : {
                  src: "/uy/xona-640.webp",
                  srcSet: "/uy/xona-640.webp 640w, /uy/xona-1000.webp 1000w, /uy/xona-1400.webp 1400w",
                  sizes: PHOTO_SIZES,
                  w: 1000,
                  h: 732,
                  alt: lang === "ru" ? "Светлая гостиная: телевизор, комод и кресло" : "Yorug' mehmonxona: devorda televizor, yog'och komod va yumshoq kreslo",
                  position: "30% 60%",
                  lqip: "xona",
                }
          }
          product={PRODUCT[topic.id]}
        />

        {/* ---- asosiy afzalliklar: har biri yongan "deraza" ---- */}
        <section aria-label={t.kicker} className="uy-sec tpu-points">
          <ul data-m="" data-m-stagger className="u-wrap tpu-points-grid">
            {t.points.map((p) => (
              <li key={p.title}>
                <span className="tpu-win" aria-hidden="true" />
                <p className="tpu-point-title">{p.title}</p>
                <p className="tpu-point-text">{p.text}</p>
              </li>
            ))}
          </ul>
        </section>

        {/* ---- 3D vitrina: shu yo'nalishning real 3D modellari ---- */}
        {topic.id === "texnika" || topic.id === "mebel" || topic.id === "tilla" || topic.id === "skuter" ? (
          <Vitrina3D only={topic.id} lang={lang} />
        ) : null}

        {/* ---- matn: aylantirilgan sari so'zma-so'z yorishadi ---- */}
        <section aria-labelledby="topic-more" className="uy-sec uy">
          <div className="u-wrap tp-body">
            <h2 id="topic-more" className="u-h2">
              {ui.more}
            </h2>
            {/* Egasi 27.10: matn — sarlavha va bitta xatboshi; qolgani yopiq
                bo'limda (qidiruv tizimlari uni baribir o'qiydi). */}
            <div className="tp-body-text">
              <p className="scrub">
                <ScrubText text={t.body[0]} />
              </p>
              {t.body.length > 1 ? (
                <details className="more-text">
                  <summary>{ui.readAll}</summary>
                  {t.body.slice(1).map((para, i) => (
                    <p key={i}>
                      <Rich text={para} />
                    </p>
                  ))}
                </details>
              ) : null}
            </div>
          </div>
        </section>

        {/* ---- qadamlar ---- */}
        <section aria-labelledby="topic-how" className="uy-sec uy-tun tpu-how">
          <div className="u-wrap">
            <h2 id="topic-how" className="u-h2">
              {ui.howTitle}
            </h2>
            <ol data-m="" data-m-stagger className="tp-steps">
              {steps.map((s, i) => (
                <li key={s.index} className="tp-step">
                  <span className="tp-step-top">
                    <span className="tp-step-n numeral">{s.index}</span>
                    <StepArt i={i} />
                  </span>
                  <p className="tp-step-title">{s.title}</p>
                  <p className="mt-2 leading-relaxed text-on-purple-2">{s.detail}</p>
                </li>
              ))}
            </ol>
          </div>
        </section>

        {/* ---- filiallar: o'z SVG xaritasi, video va kartalar (bosh sahifa bilan bir xil) ---- */}
        <UyFiliallar lang={lang} title={ui.branchesTitle} />

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
        <section aria-labelledby="topic-other" className="uy-sec">
          <div className="u-wrap">
            <h2 id="topic-other" className="u-h2">
              {ui.otherTitle}
            </h2>
            <UyUylar lang={lang} items={others} />
          </div>
        </section>
      </main>
      {lang === "ru" ? <RuFooter /> : <Footer />}
      <ActionBar label={ui.apply} callLabel={ui.call} />
    </>
  );
}

/* Bosh qism surati — telefonda sarlavha va matndan keyin birinchi ekranda
   turadi va eng katta element (LCP), shuning uchun UyTopHero uni yuqori
   ustunlik bilan yuklaydi. public/yonalishlar da har surat -480 va -900. */
const PHOTO_SIZES = "(min-width: 1024px) 46vw, 86vw";
/* Tom ustidan chiqib turadigan mahsulot (public/3d/<id>-320.webp). */
const PRODUCT: Partial<Record<Topic["id"], string>> = { texnika: "muzlatgich", mebel: "divan", tilla: "uzuk", skuter: "skuter" };
function photoSrcSet(src: string) {
  return `${src.replace("-900.", "-480.")} 480w, ${src.replace("-900.", "-640.")} 640w, ${src} 900w`;
}

/**
 * Qadam rasmi — matn o'qilmasa ham qadam ko'rinib tursin: tanlash (xarid
 * sumkasi), rasmiylashtirish (pasport + karta), olish (yetkazish mashinasi —
 * BranchRoute'dagi 3D model rendari), to'lash (oylar halqasi). Bezak.
 */
function StepArt({ i }: { i: number }) {
  if (i === 2)
    return <img src="/3d/yetkazish-160.webp" alt="" width={80} height={52} loading="lazy" decoding="async" className="tp-step-art tp-step-truck" />;
  const months = site.facts.installmentMonthsMax;
  return (
    <svg viewBox="0 0 64 64" aria-hidden="true" className="tp-step-art" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
      {i === 0 ? (
        <>
          <path pathLength={1} className="sa-draw" d="M14 22h36l-3 32H17Z" />
          <path pathLength={1} className="sa-draw" d="M24 26v-8a8 8 0 0 1 16 0v8" />
        </>
      ) : i === 1 ? (
        <>
          <rect pathLength={1} className="sa-draw" x="6" y="14" width="34" height="24" rx="4" />
          <circle cx="16" cy="25" r="4" />
          <path d="M24 23h10M24 29h7" />
          <rect pathLength={1} className="sa-draw sa-late" x="24" y="30" width="34" height="22" rx="4" fill="var(--purple)" />
          <path d="M24 37h34" />
        </>
      ) : (
        <>
          {Array.from({ length: months }, (_, k) => {
            const a0 = (k / months) * 2 * Math.PI - Math.PI / 2 + 0.06;
            const a1 = ((k + 1) / months) * 2 * Math.PI - Math.PI / 2 - 0.06;
            const p = (a: number) => `${(32 + 24 * Math.cos(a)).toFixed(1)} ${(32 + 24 * Math.sin(a)).toFixed(1)}`;
            return <path key={k} d={`M${p(a0)}A24 24 0 0 1 ${p(a1)}`} className="sa-seg" style={{ ["--k" as string]: k }} />;
          })}
          <text x="32" y="39" textAnchor="middle" className="sa-num numeral">
            {months}
          </text>
        </>
      )}
    </svg>
  );
}

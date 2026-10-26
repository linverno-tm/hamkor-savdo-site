import type { CSSProperties, ReactNode } from "react";
import { site } from "@/data/site";
import { SectionHeading } from "@/components/ui/SectionHeading";

/**
 * "3 qadamda xarid qiling" — skroll bilan boshqariladigan 3D sahna
 * (egasi 26.10: "3D grafikalik animatsiyalar, motion graphics").
 *
 * Bo'lim ekranda qotib turadi (sticky), aylantirgan sari o'ngdagi sahnada
 * xarid voqeasi davom etadi: mahsulot qutisi javondan ko'tariladi (tanlang),
 * telefon kirib ariza to'ladi (ariza), pasport va karta qutiga tushib
 * tasdiq bosiladi, ortida oylar halqasi yoyiladi (rasmiylashtiring). Chapda
 * faol qadam yonadi. Matnlar — avvalgi uch qadamniki, yangi matn yo'q.
 *
 * Hammasi CSS (app/home-scene.css, `view-timeline`), JavaScript'siz —
 * kirillcha nusxada ham ishlaydi. Brauzer qo'llamasa yoki harakat
 * kamaytirilgan bo'lsa — sahnaning yakuniy kadri va oddiy ro'yxat.
 */
const STEPS = [
  {
    title: "Mahsulotni tanlang",
    text: "Filialda ko'rib tanlang yoki saytdagi kategoriyalardan kerakligini toping.",
  },
  {
    title: "Ariza qoldiring",
    text: "Ism va raqamingizni qoldiring yoki qo'ng'iroq qiling — o'zimiz bog'lanamiz.",
  },
  {
    title: "Xaridingizni rasmiylashtiring",
    text: `Pasport va plastik karta bilan joyida rasmiylashtiramiz — ${site.facts.installmentMonthsMax} oygacha bo'lib to'laysiz.`,
  },
];

/** CSS 3D quti: o'lchamlar em'da, oltita yuz. */
function Box({ w, h, d, className, front }: { w: number; h: number; d: number; className: string; front?: ReactNode }) {
  const style = { "--w": `${w}em`, "--h": `${h}em`, "--d": `${d}em` } as CSSProperties;
  return (
    <span className={`sc-box ${className}`} style={style}>
      <span className="sc-f sc-front">{front}</span>
      <span className="sc-f sc-back" />
      <span className="sc-f sc-right">{front}</span>
      <span className="sc-f sc-left" />
      <span className="sc-f sc-top" />
      <span className="sc-f sc-bottom" />
    </span>
  );
}

/** Brend belgisi (H) — qutining yuzida. */
const Mark = (
  <svg className="sc-mark" viewBox="0 0 24 24" aria-hidden="true">
    <path d="M5 4h4v6.5h6V4h4v16h-4v-6h-6v6H5Z" fill="currentColor" />
  </svg>
);

export function HowItWorks() {
  const months = Array.from({ length: site.facts.installmentMonthsMax }, (_, k) => k + 1);

  return (
    <section id="qanday-ishlaydi" aria-labelledby="how-title" className="how scene">
      <div className="scene-track">
        <div className="scene-stage">
          <div className="wrap scene-grid">
            <div className="scene-copy">
              <SectionHeading id="how-title" kicker="Oson va tez" title="3 qadamda xarid qiling" />
              <ol className="scene-steps">
                {STEPS.map((s, i) => (
                  <li key={s.title} className="scene-step">
                    <span className="scene-n numeral" aria-hidden="true">
                      {String(i + 1).padStart(2, "0")}
                    </span>
                    <span className="scene-step-body">
                      <h3 className="scene-step-title">{s.title}</h3>
                      <p className="scene-step-text">{s.text}</p>
                    </span>
                  </li>
                ))}
              </ol>
            </div>

            {/* Bezak: hammasi yuqoridagi matnda aytilgan. */}
            <div className="scene-view" aria-hidden="true">
              <div className="sc-world">
                <span className="sc-floor" />
                <span className="sc-glow" />

                <span className="sc-slot sc-slot--tall">
                  <Box w={6} h={13} d={6} className="sc-tall" />
                </span>
                <span className="sc-slot sc-slot--flat">
                  <Box w={12} h={7} d={1.4} className="sc-flat" />
                </span>

                {/* Soat siferblatidek: oxirgi oy tepada, qolganlari soat mili bo'ylab. */}
                <span className="sc-months">
                  {months.map((m) => (
                    <span key={m} className="sc-month" style={{ "--k": m % months.length } as CSSProperties}>
                      <span className="sc-month-in numeral">{m}</span>
                    </span>
                  ))}
                </span>

                <span className="sc-slot sc-slot--main">
                  <Box w={8} h={8} d={8} className="sc-main" front={Mark} />
                </span>

                <span className="sc-slot sc-slot--phone">
                  <span className="sc-phone">
                    <span className="sc-screen">
                      <span className="sc-bar" />
                      <span className="sc-line sc-line--1" />
                      <span className="sc-line sc-line--2" />
                      <span className="sc-btn" />
                    </span>
                  </span>
                </span>

                <span className="sc-slot sc-slot--id">
                  <span className="sc-card sc-card--id">
                    <span className="sc-photo" />
                    <span className="sc-rows" />
                  </span>
                </span>
                <span className="sc-slot sc-slot--bank">
                  <span className="sc-card sc-card--bank">
                    <span className="sc-chip" />
                  </span>
                </span>

                <span className="sc-check">
                  <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M5 12.5 10 17.5 19.5 7" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
                  </svg>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

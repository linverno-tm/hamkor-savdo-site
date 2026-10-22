import { site } from "@/data/site";
import { SectionHeading } from "@/components/ui/SectionHeading";

/**
 * "3 qadamda xarid qiling" — haqiqiy ketma-ketlik, shuning uchun raqamli.
 *
 * Qadamlar orasidagi yo'l aylantirishga ergashib chiziladi (app/home.css >
 * .steps-track, `animation-timeline: view()`), har bir qadam belgisi
 * o'z navbatida "yonadi". Brauzer qo'llamasa — yo'l to'liq chizilgan.
 */
const STEPS = [
  {
    title: "Mahsulotni tanlang",
    text: "Filialda ko'rib tanlang yoki saytdagi kategoriyalardan kerakligini toping.",
    icon: <path d="M4 7h16l-1.5 10.5a2 2 0 0 1-2 1.5h-9a2 2 0 0 1-2-1.5L4 7Zm4 0a4 4 0 0 1 8 0" />,
  },
  {
    title: "Ariza qoldiring",
    text: "Ism va raqamingizni qoldiring yoki qo'ng'iroq qiling — o'zimiz bog'lanamiz.",
    icon: <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z" />,
  },
  {
    title: "Xaridingizni rasmiylashtiring",
    text: `Pasport va plastik karta bilan joyida rasmiylashtiramiz — ${site.facts.installmentMonthsMax} oygacha bo'lib to'laysiz.`,
    icon: <path d="M4 12.5 9 17.5 20 6.5" />,
  },
];

export function HowItWorks() {
  return (
    <section id="qanday-ishlaydi" aria-labelledby="how-title" className="sec how">
      <div className="wrap">
        <SectionHeading id="how-title" align="center" kicker="Oson va tez" title="3 qadamda xarid qiling" />

        <div className="steps">
          <span className="steps-track" aria-hidden="true">
            <span className="steps-fill" />
          </span>
          <ol data-m="" data-m-stagger className="steps-list">
            {STEPS.map((s, i) => (
              <li key={s.title} className="step">
                <span className="step-dot" aria-hidden="true">
                  <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    {s.icon}
                  </svg>
                </span>
                <span className="step-n numeral" aria-hidden="true">
                  {String(i + 1).padStart(2, "0")}
                </span>
                <h3 className="step-title">{s.title}</h3>
                <p className="step-text">{s.text}</p>
              </li>
            ))}
          </ol>
        </div>
      </div>
    </section>
  );
}

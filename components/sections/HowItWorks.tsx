import { site } from "@/data/site";
import { SectionHeading } from "@/components/ui/SectionHeading";

/** "3 qadamda xarid qiling" — minimal belgi va bir jumladan. */
const STEPS = [
  {
    title: "Mahsulotni tanlang",
    text: "Filialda ko'rib tanlang yoki saytdagi kategoriyalardan kerakligini toping.",
    icon: (
      <path d="M4 7h16l-1.5 10.5a2 2 0 0 1-2 1.5h-9a2 2 0 0 1-2-1.5L4 7Zm4 0a4 4 0 0 1 8 0" />
    ),
  },
  {
    title: "Ariza qoldiring",
    text: "Ism va raqamingizni qoldiring yoki qo'ng'iroq qiling — o'zimiz bog'lanamiz.",
    icon: (
      <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z" />
    ),
  },
  {
    title: "Xaridingizni rasmiylashtiring",
    text: `Pasport va plastik karta bilan joyida rasmiylashtiramiz — ${site.facts.installmentMonthsMax} oygacha bo'lib to'laysiz.`,
    icon: <path d="M4 12.5 9 17.5 20 6.5" />,
  },
];

export function HowItWorks() {
  return (
    <section id="qanday-ishlaydi" aria-labelledby="how-title" className="bg-ground-2 py-16 sm:py-24">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <SectionHeading id="how-title" align="center" kicker="Oson va tez" title="3 qadamda xarid qiling" />
        <ol data-reveal className="mt-12 grid gap-5 md:grid-cols-3">
          {STEPS.map((s, i) => (
            <li key={s.title} className="card relative p-7">
              <div className="flex items-center justify-between">
                <span className="flex h-12 w-12 items-center justify-center rounded-[14px] bg-purple-50 text-purple">
                  <svg
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden="true"
                  >
                    {s.icon}
                  </svg>
                </span>
                <span className="numeral text-4xl text-purple-100" aria-hidden="true">
                  {String(i + 1).padStart(2, "0")}
                </span>
              </div>
              <h3 className="display mt-6 text-xl">{s.title}</h3>
              <p className="mt-2 leading-relaxed text-ink-2">{s.text}</p>
            </li>
          ))}
        </ol>
      </div>
    </section>
  );
}

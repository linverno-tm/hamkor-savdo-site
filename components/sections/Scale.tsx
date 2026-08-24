import { site } from "@/data/site";
import { groupDigits } from "@/lib/format";

interface Stat {
  /** Numeric target for the count-up; omit for values that are not a plain number. */
  count?: number;
  suffix?: string;
  value?: string;
  label: string;
  note: string;
}

/** Verified numbers only — each traces to data/site.ts. */
const stats: Stat[] = [
  {
    count: 7000,
    suffix: "+",
    label: "MAHSULOT",
    note: "Tilla, texnika va mebel bo'limlarida",
  },
  {
    count: site.facts.branchCount,
    label: "FILIAL",
    note: "Shahrixon (2), Asaka va Andijonda",
  },
  {
    count: site.facts.installmentMonthsMax,
    suffix: " oy",
    label: "MUDDAT",
    note: "Muddatli to'lovning eng uzun muddati",
  },
  {
    value: site.facts.instagramFollowers,
    label: "OBUNACHI",
    note: "Instagram sahifamizda bizni kuzatadi",
  },
];

/** Scene 03 — "Qanchalik katta?" Numbers count up once, on entry. */
export function Scale() {
  return (
    <section
      id="kolam"
      aria-labelledby="scale-title"
      className="on-purple relative overflow-hidden bg-purple py-16 text-white sm:py-24"
    >
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <div data-reveal className="text-center">
          <p className="kicker">Ko&apos;lam</p>
          <h2 id="scale-title" className="display mt-3 text-4xl sm:text-5xl">
            Raqamlarda HAMKOR SAVDO
          </h2>
        </div>

        <dl data-reveal className="mt-16 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-4">
          {stats.map((s) => (
            <div key={s.label} className="border-t border-white/25 pt-6">
              <dd className="numeral text-6xl text-yellow sm:text-7xl">
                {s.count !== undefined ? (
                  <span data-count={s.count} data-count-suffix={s.suffix ?? ""}>
                    {groupDigits(s.count)}
                    {s.suffix ?? ""}
                  </span>
                ) : (
                  s.value
                )}
              </dd>
              <dt className="numeral mt-4 text-xl tracking-[0.22em]">{s.label}</dt>
              <p className="mt-3 text-sm leading-relaxed text-on-purple-2">{s.note}</p>
            </div>
          ))}
        </dl>
      </div>
    </section>
  );
}

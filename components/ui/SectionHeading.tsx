import { SplitWords } from "@/components/ui/SplitWords";

interface SectionHeadingProps {
  kicker: string;
  title: React.ReactNode;
  lead?: string;
  id?: string;
  align?: "left" | "center";
  /** Binafsha fon ustida. */
  dark?: boolean;
}

/**
 * Bo'lim boshi: kichik belgi (sariq chiziq + so'z), katta sarlavha va
 * ixtiyoriy izoh. Ekranga kirganda chiziq cho'ziladi, sarlavha so'zlari
 * navbat bilan ko'tariladi, izoh oxirida chiqadi (`data-m`, lib/motion.ts).
 */
export function SectionHeading({ kicker, title, lead, id, align = "left", dark = false }: SectionHeadingProps) {
  const centered = align === "center";
  return (
    <div data-m className={`sh ${centered ? "sh-center" : ""} ${dark ? "sh-dark" : ""}`}>
      <p className="eyebrow">{kicker}</p>
      <h2 id={id} className="sh-title">
        {typeof title === "string" ? <SplitWords text={title} /> : title}
      </h2>
      {lead ? <p className="sh-lead">{lead}</p> : null}
    </div>
  );
}

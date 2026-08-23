interface SectionHeadingProps {
  kicker: string;
  title: React.ReactNode;
  lead?: string;
  id?: string;
  align?: "left" | "center";
}

/** Each section opens by answering its own question in plain, readable text. */
export function SectionHeading({
  kicker,
  title,
  lead,
  id,
  align = "left",
}: SectionHeadingProps) {
  const centered = align === "center";
  return (
    <div data-reveal className={centered ? "text-center" : ""}>
      <p className="kicker">{kicker}</p>
      <h2
        id={id}
        className={`display mt-3 text-4xl sm:text-5xl lg:text-6xl ${centered ? "mx-auto max-w-4xl" : "max-w-4xl"}`}
      >
        {title}
      </h2>
      {lead ? (
        <p
          className={`mt-5 max-w-2xl text-lg leading-relaxed text-ink-2 ${centered ? "mx-auto" : ""}`}
        >
          {lead}
        </p>
      ) : null}
    </div>
  );
}

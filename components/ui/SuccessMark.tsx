/**
 * "Qabul qilindi" belgisi: aylana chiziladi, ichida tasdiq belgisi, atrofga to'lqin
 * va mayda nuqtalar sachraydi (app/pages.css > .ok-mark). Bir marta, ko'rinishi bilan.
 * Harakat kamaytirilgan bo'lsa — tayyor belgi. Bezak: matn yonida aytilgan.
 */
export function SuccessMark({ size = "lg" }: { size?: "lg" | "sm" }) {
  return (
    <span className={`ok-mark ok-mark--${size}`} aria-hidden="true">
      {size === "lg" ? (
        <>
          <span className="ok-ring" />
          <span className="ok-ring ok-ring-2" />
          {Array.from({ length: 10 }, (_, i) => (
            <span key={i} className="ok-dot" style={{ ["--a" as string]: `${i * 36}deg` }} />
          ))}
        </>
      ) : null}
      <svg viewBox="0 0 52 52">
        <circle className="ok-circle" cx="26" cy="26" r="23" />
        <path className="ok-tick" d="M15.5 27.5 22.5 34.5 37 19" />
      </svg>
    </span>
  );
}

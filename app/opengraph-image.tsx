import { ImageResponse } from "next/og";
import { readFileSync } from "node:fs";
import { join } from "node:path";
import { site } from "@/data/site";

/**
 * The link preview card. This matters more than usual here: the business shares
 * its link mainly through Telegram and Instagram, where a bare URL looks like
 * nothing. Rendered once at build time, so it costs visitors nothing.
 *
 * Fonts are the real brand faces, read from disk rather than fetched, so a
 * build never depends on the network. They are used only by this generator and
 * are never sent to a browser.
 */
/** Required by `output: "export"`: render this once at build time. */
export const dynamic = "force-static";

export const alt = "HAMKOR SAVDO — Oilangizga ishonchli hamkor";
export const size = { width: 1200, height: 630 };
export const contentType = "image/png";

const dir = join(process.cwd(), "app", "_og-fonts");
const outfit = readFileSync(join(dir, "Outfit-Bold.ttf"));
const bebas = readFileSync(join(dir, "BebasNeue-Regular.ttf"));
const markSvg = readFileSync(join(dir, "mark-white.svg"), "utf-8");
const markUri = `data:image/svg+xml;base64,${Buffer.from(markSvg).toString("base64")}`;

export default async function Image() {
  const facts = [
    { value: site.facts.productCount, label: "MAHSULOT" },
    { value: String(site.facts.branchCount), label: "FILIAL" },
    { value: `${site.facts.installmentMonthsMax} OY`, label: "MUDDATLI TO'LOV" },
  ];

  return new ImageResponse(
    (
      <div
        style={{
          width: "100%",
          height: "100%",
          display: "flex",
          flexDirection: "column",
          justifyContent: "space-between",
          background: "#5a3089",
          padding: "68px 72px",
          fontFamily: "Outfit",
          color: "#ffffff",
        }}
      >
        <div style={{ display: "flex", alignItems: "center", gap: 28 }}>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={markUri} width={92} height={105} alt="" />
          <div style={{ display: "flex", flexDirection: "column" }}>
            <span style={{ fontSize: 46, letterSpacing: -1 }}>HAMKOR SAVDO</span>
            <span style={{ fontSize: 24, color: "#cbb8e0", marginTop: 4 }}>
              {site.serviceArea}
            </span>
          </div>
        </div>

        <div style={{ display: "flex", flexDirection: "column" }}>
          <span style={{ fontSize: 76, lineHeight: 1.05, letterSpacing: -2 }}>
            Oilangizga ishonchli
          </span>
          <span style={{ display: "flex", alignItems: "center" }}>
            <span
              style={{
                fontSize: 76,
                lineHeight: 1.05,
                letterSpacing: -2,
                background: "#dfd01f",
                color: "#1a1130",
                padding: "0 18px",
              }}
            >
              hamkor
            </span>
          </span>
          <span style={{ fontSize: 30, color: "#cbb8e0", marginTop: 24 }}>
            Tilla · Texnika · Mebel
          </span>
        </div>

        <div style={{ display: "flex", gap: 64 }}>
          {facts.map((f) => (
            <div key={f.label} style={{ display: "flex", flexDirection: "column" }}>
              <span style={{ fontFamily: "Bebas", fontSize: 68, color: "#dfd01f", lineHeight: 1 }}>
                {f.value}
              </span>
              <span style={{ fontFamily: "Bebas", fontSize: 24, letterSpacing: 3, marginTop: 6 }}>
                {f.label}
              </span>
            </div>
          ))}
        </div>
      </div>
    ),
    {
      ...size,
      fonts: [
        { name: "Outfit", data: outfit, style: "normal", weight: 700 },
        { name: "Bebas", data: bebas, style: "normal", weight: 400 },
      ],
    },
  );
}

import { Logo } from "@/components/ui/Logo";

/**
 * The frame a branch card shows while it has no photographs.
 *
 * A branch without pictures still keeps its card — the address and phone are
 * what most visitors came for. What it must not do is look like the page broke:
 * an empty dashed box reads as a fault, or worse, as a branch that has been
 * abandoned.
 *
 * So the frame carries the brand mark and a slow light sweep — the skeleton
 * pattern every browser already uses for content that is on its way. Without
 * needing a caption, it reads as "being prepared" rather than "missing". The
 * caption is there anyway, and sits above the sweep so the wave never washes
 * over the words.
 *
 * Shared by the home page and the branch index so the two never drift apart.
 * Motion lives in `.media-slot` in globals.css and stops entirely under
 * `prefers-reduced-motion`.
 */
export function PhotoPlaceholder({ className }: { className?: string }) {
  return (
    <span
      className={`media-slot flex w-full items-center justify-center rounded-none border-0 border-b border-dashed text-center text-sm text-ink-3 ${className ?? ""}`}
    >
      {/* z-10: yorug'lik to'lqini shu mazmunning ostidan o'tadi. */}
      <span className="relative z-10 flex flex-col items-center gap-3">
        <Logo variant="mark" className="h-10 w-auto text-purple-100" />
        Suratlar tayyorlanmoqda
      </span>
    </span>
  );
}

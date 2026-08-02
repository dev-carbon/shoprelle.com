import { cn } from '@/lib/utils';

/**
 * The oversized wordmark closing the page, set in the brand gold.
 *
 * It is decorative and hidden from screen readers — the brand name is already
 * announced in the header — which is why it can take the fill gold rather than
 * the ink step the section titles need.
 *
 * Drawn as SVG rather than styled text: `textLength` pins the word to the exact
 * width of the viewBox, so it fills the page edge to edge at every window size
 * and can never overflow — which sized text inevitably does, since the width of
 * nine glyphs depends on the font that happens to have loaded.
 *
 * The viewBox runs past the baseline so the tail of the `p` has somewhere to
 * go: cropped, it read as a rendering fault rather than as a choice. The
 * wrapper keeps a little room underneath for the same reason.
 */
export function BrandWordmark({ className }: { className?: string }) {
    return (
        <div
            aria-hidden
            className={cn('px-4 pb-6 select-none sm:pb-8', className)}
        >
            <svg
                viewBox="0 0 1000 208"
                className="block w-full"
                role="presentation"
                focusable="false"
            >
                <text
                    x="0"
                    y="160"
                    textLength="1000"
                    lengthAdjust="spacingAndGlyphs"
                    fontSize="200"
                    fontWeight="700"
                    fill="var(--color-accent-brand)"
                    className="font-sans"
                >
                    Shoprelle
                </text>
            </svg>
        </div>
    );
}

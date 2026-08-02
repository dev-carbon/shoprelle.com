import { cn } from '@/lib/utils';

/**
 * The oversized wordmark closing the page, set in the brand gold over a field
 * of dots.
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
 * Set in the display face at 900. Archivo is in this project for exactly one
 * reason — it carries a true 900, where humanist sans families stop at 700 and
 * leave the browser to fake the rest — and at two hundred pixels a faked weight
 * is not a subtlety, it is a smear.
 *
 * The viewBox runs past the baseline so the tail of the `p` has somewhere to
 * go: cropped, it read as a rendering fault rather than as a choice. The
 * padding underneath is only what keeps the descender off the very last pixel
 * of the page.
 */

/** The wallpaper's tile, in user units — about a twenty-pixel grid at full page width. */
const TILE = 14;

export function BrandWordmark({ className }: { className?: string }) {
    return (
        <div aria-hidden className={cn('px-4 pb-2 select-none', className)}>
            <svg
                viewBox="0 0 1000 208"
                className="block w-full"
                role="presentation"
                focusable="false"
            >
                <defs>
                    {/* Wallpaper, in the same idiom as the dotted world behind
                        the hero — so the page opens and closes on one texture
                        rather than on two unrelated ideas. A tiled pattern and
                        not a projection: there is nothing to read here, only a
                        ground for the word to sit on. */}
                    <pattern
                        id="shoprelle-wordmark-dots"
                        width={TILE}
                        height={TILE}
                        patternUnits="userSpaceOnUse"
                    >
                        <circle
                            cx={TILE / 2}
                            cy={TILE / 2}
                            r={1.5}
                            className="fill-muted-foreground/30"
                        />
                    </pattern>

                    {/* Faded from the middle out, so the field never shows an
                        edge of its own: a rectangle of dots that simply stops
                        reads as a panel somebody forgot to delete. */}
                    <radialGradient
                        id="shoprelle-wordmark-fade"
                        cx="50%"
                        cy="48%"
                        r="62%"
                    >
                        <stop offset="0%" stopColor="white" stopOpacity="1" />
                        <stop
                            offset="55%"
                            stopColor="white"
                            stopOpacity="0.7"
                        />
                        <stop offset="100%" stopColor="white" stopOpacity="0" />
                    </radialGradient>

                    <mask id="shoprelle-wordmark-mask">
                        <rect
                            width="1000"
                            height="208"
                            fill="url(#shoprelle-wordmark-fade)"
                        />
                    </mask>
                </defs>

                <rect
                    width="1000"
                    height="208"
                    fill="url(#shoprelle-wordmark-dots)"
                    mask="url(#shoprelle-wordmark-mask)"
                />

                <text
                    x="0"
                    y="160"
                    textLength="1000"
                    lengthAdjust="spacingAndGlyphs"
                    fontSize="200"
                    fontWeight="900"
                    fill="var(--color-accent-brand)"
                    className="font-display"
                >
                    Shoprelle
                </text>
            </svg>
        </div>
    );
}

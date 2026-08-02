import atlas from '@/data/hero-dots.json';
import { cn } from '@/lib/utils';

/**
 * The world as a coarse field of dots, projected once at build time.
 *
 * The same Equal Earth frame the delivery map uses, sampled on an even grid in
 * projected space — even in *projected* space and not in degrees, because a
 * grid laid out in degrees bunches towards the poles, and the giveaway of a
 * cheap dotted map is Scandinavia coming out twice as dense as the Sahel.
 *
 * Coarser than a map would be drawn, deliberately: at the opacity this sits at,
 * a fine grid turns to mush and only costs bytes to do it.
 */
const { view: VIEW, dots: DOTS } = atlas as unknown as {
    view: [number, number, number, number];
    dots: number[];
};

/**
 * Every dot, as a single path.
 *
 * Each one is a zero-length subpath, which a round line cap renders as a
 * circle — so seventeen hundred dots cost one element and one attribute
 * instead of seventeen hundred `<circle>`s for the browser to lay out. Their
 * size is the stroke width, so the whole field can be scaled without the
 * geometry being touched.
 */
const DOT_PATH = ((): string => {
    let d = '';

    for (let index = 0; index < DOTS.length; index += 2) {
        d += `M${DOTS[index]} ${DOTS[index + 1]}l0 0`;
    }

    return d;
})();

const MASK = [
    'radial-gradient(58% 52% at 50% 40%, transparent 22%, black 82%)',
    'linear-gradient(to bottom, black 0%, black 38%, transparent 74%)',
].join(', ');

/**
 * The world, ghosted behind the hero.
 *
 * Purely atmospheric: it says "everywhere" before a single word has been read,
 * and then gets out of the way. Which is why it is masked rather than merely
 * faded — an even wash at this opacity still puts dots directly behind the
 * headline, and text over texture is the one thing that would make the hero
 * cheaper rather than richer. The mask takes it to nothing well above the copy
 * and again at both edges, so the map is only ever present at the margins.
 *
 * No markers, no routes, nothing to read: the map that carries information is
 * further down the page, and this one would only compete with it.
 */
export function WorldBackdrop({ className }: { className?: string }) {
    return (
        <div
            aria-hidden
            className={cn(
                'pointer-events-none absolute inset-0 overflow-hidden select-none',
                className,
            )}
        >
            <svg
                viewBox={VIEW.join(' ')}
                preserveAspectRatio="xMidYMid slice"
                role="presentation"
                focusable="false"
                className="size-full"
                style={{
                    // Two masks intersected. The radial punches a clearing out
                    // of the middle, which is where the headline, the field and
                    // the button all live — dots behind type is the one thing
                    // that would make the hero cheaper rather than richer. The
                    // linear then takes what is left to nothing before the
                    // cards at the bottom. What survives is the margins, which
                    // is the only place an atmosphere belongs.
                    maskImage: MASK,
                    maskComposite: 'intersect',
                    WebkitMaskImage: MASK,
                    WebkitMaskComposite: 'source-in',
                }}
            >
                <path
                    d={DOT_PATH}
                    fill="none"
                    strokeLinecap="round"
                    strokeWidth={2.6}
                    className="stroke-muted-foreground/15"
                />
            </svg>
        </div>
    );
}

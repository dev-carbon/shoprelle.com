import { useMemo } from 'react';

import atlas from '@/data/hero-dots.json';
import { toNumericIds } from '@/lib/destinations';
import { cn } from '@/lib/utils';

/**
 * The world as a coarse field of dots, projected once at build time.
 *
 * The same Equal Earth frame the delivery map uses, sampled on an even grid in
 * projected space — even in *projected* space and not in degrees, because a
 * grid laid out in degrees bunches towards the poles, and the giveaway of a
 * cheap dotted map is Scandinavia coming out twice as dense as the Sahel.
 *
 * Coarser than a map would be drawn, deliberately: at the opacity this sits at
 * a fine grid turns to mush, and only costs bytes to do it.
 */
const { view: VIEW, dots: DOTS } = atlas as unknown as {
    view: [number, number, number, number];
    dots: Record<string, number[]>;
};

/**
 * A run of dots, as one path.
 *
 * Each dot is a zero-length subpath, which a round line cap renders as a
 * circle — so two thousand dots cost one element and one attribute instead of
 * two thousand `<circle>`s for the browser to lay out. Their size is the stroke
 * width, so the field can be scaled without the geometry being touched.
 */
const dotPath = (points: number[]): string => {
    let d = '';

    for (let index = 0; index < points.length; index += 2) {
        d += `M${points[index]} ${points[index + 1]}l0 0`;
    }

    return d;
};

/** Every dot on Earth, built once: the ground never changes. */
const ALL_DOTS = dotPath(Object.values(DOTS).flat());

/**
 * Two masks, intersected.
 *
 * The radial does not clear the middle so much as turn it down. A hole would
 * take the continents with it, and being able to make them out is the whole
 * point — so behind the headline the map is held at just under half strength,
 * present but never competing. The linear then takes what is left to nothing
 * before the cards, which carry their own surfaces and have no room for a
 * texture underneath.
 */
const MASK = [
    'radial-gradient(60% 55% at 50% 40%, rgba(0,0,0,0.45) 22%, black 84%)',
    'linear-gradient(to bottom, black 0%, black 45%, transparent 86%)',
].join(', ');

/**
 * The world, ghosted behind the hero.
 *
 * Atmosphere, not information: it says "everywhere" before a word has been
 * read, and the destinations glow inside it so the promise is made before it is
 * explained. Everything it implies is stated in text further down the page —
 * this carries none of it alone, and is hidden from screen readers entirely.
 *
 * Fitted rather than cropped. Filling the hero's box meant scaling a map twice
 * as wide as it is tall to cover something nearly square, which cropped away
 * both edges and left a zoomed-in band of Africa nobody could place. Fitted, the
 * whole globe is in frame and the continents read.
 */
export function WorldBackdrop({
    codes = [],
    className,
}: {
    /** Alpha-2 codes of the countries to light up. */
    codes?: string[];
    className?: string;
}) {
    const served = useMemo(
        () => dotPath(toNumericIds(codes).flatMap((id) => DOTS[id] ?? [])),
        [codes],
    );

    return (
        <div
            aria-hidden
            className={cn(
                'pointer-events-none absolute inset-0 flex items-start justify-center overflow-hidden select-none',
                className,
            )}
        >
            <svg
                viewBox={VIEW.join(' ')}
                role="presentation"
                focusable="false"
                className="h-auto w-full"
                style={{
                    maskImage: MASK,
                    maskComposite: 'intersect',
                    WebkitMaskImage: MASK,
                    WebkitMaskComposite: 'source-in',
                }}
            >
                <g fill="none" strokeLinecap="round">
                    <path
                        d={ALL_DOTS}
                        strokeWidth={2.6}
                        className="stroke-muted-foreground/30"
                    />

                    {/* The destinations, in the brand blue and a size up, over
                        a halo of the same dots drawn far wider and far fainter.
                        On a backdrop this quiet a colour alone would not carry,
                        and at this spacing a country is only five or six dots
                        across — too small to be found without something around
                        it to catch the eye first. */}
                    <path
                        d={served}
                        strokeWidth={11}
                        className="stroke-primary/15"
                    />
                    <path
                        d={served}
                        strokeWidth={3.6}
                        className="stroke-primary/80"
                    />
                </g>
            </svg>
        </div>
    );
}

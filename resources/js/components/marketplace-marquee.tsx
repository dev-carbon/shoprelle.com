import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

/**
 * The marketplaces Shoprelle buys from, as two rows of tiles scrolling past in
 * opposite directions.
 *
 * Each row renders its own list twice inside the track: the animation shifts it
 * by exactly half the track width, so it lands back on the first copy and the
 * loop is invisible. Edges fade out through a mask rather than a gradient
 * overlay, so it works on any background. Hovering anywhere pauses both rows.
 *
 * A tile shows the brand's mark when one is supplied through `logos`, and its
 * name set as a wordmark otherwise — the same mix the reference layout uses.
 * Tiles keep a fixed height and a square minimum, but widen to fit their
 * contents: brand wordmarks are far wider than they are tall, and squeezing one
 * into a square would shrink it below legibility.
 *
 * The tile is white in both themes, and each mark is drawn in its own brand
 * colour through `colors`. That is the pairing every brand publishes its logo
 * for; a tinted tile would mean recolouring the marks to survive it, which is
 * exactly what a brand's guidelines forbid. A brand with no colour given falls
 * back to the tile's ink.
 *
 * The tiles are aria-hidden and duplicated visually only; a static, readable
 * list is exposed to screen readers instead.
 */
export function MarketplaceMarquee({
    marketplaces,
    logos = {},
    colors = {},
    className,
}: {
    marketplaces: string[];
    /** Brand marks by marketplace name. Render an `<svg>` sized `size-6`. */
    logos?: Record<string, ReactNode>;
    /** Brand colours by marketplace name, as CSS colours. */
    colors?: Record<string, string>;
    className?: string;
}) {
    // Alternating rather than splitting down the middle keeps the two rows a
    // similar width, so neither finishes its loop visibly ahead of the other.
    const rows = [
        marketplaces.filter((_, index) => index % 2 === 0),
        marketplaces.filter((_, index) => index % 2 === 1),
    ];

    return (
        <div className={cn('group relative', className)}>
            <div
                aria-hidden
                className="flex flex-col gap-3 overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_8%,black_92%,transparent)]"
            >
                {rows.map((row, rowIndex) => (
                    <div
                        key={rowIndex}
                        className={cn(
                            'flex w-max gap-3 group-hover:[animation-play-state:paused]',
                            rowIndex % 2 === 0
                                ? 'animate-marquee'
                                : 'animate-marquee-reverse',
                        )}
                    >
                        {[0, 1].map((copy) => (
                            <ul key={copy} className="flex shrink-0 gap-3">
                                {row.map((marketplace) => (
                                    <li
                                        key={`${copy}-${marketplace}`}
                                        style={{ color: colors[marketplace] }}
                                        className="flex h-16 min-w-16 shrink-0 items-center justify-center rounded-xl border border-black/[0.07] bg-surface-tile px-4 text-neutral-900 shadow-sm transition-[transform,box-shadow] duration-200 hover:scale-105 hover:shadow-md"
                                    >
                                        {logos[marketplace] ?? (
                                            <span className="text-center text-sm leading-tight font-semibold tracking-tight">
                                                {marketplace}
                                            </span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        ))}
                    </div>
                ))}
            </div>

            <p className="sr-only">
                Plateformes disponibles : {marketplaces.join(', ')}, et bien
                d'autres.
            </p>
        </div>
    );
}

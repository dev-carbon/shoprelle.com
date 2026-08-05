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
 *
 * `onSelect` makes each tile clickable, as a pointer-only shortcut: the tracks
 * are aria-hidden and every tile exists twice, so a real button in there would
 * either enter the tab order duplicated and invisible to screen readers, or
 * carry a `tabindex` that silences it anyway. Nothing is lost by leaving it to
 * the pointer — the click is a head start on a journey that keeps its own
 * full-fledged doors: the « Commander » buttons, which keyboard and
 * screen-reader users already reach.
 */
export function MarketplaceMarquee({
    marketplaces,
    logos = {},
    colors = {},
    onSelect,
    className,
}: {
    marketplaces: string[];
    /** Brand marks by marketplace name. Render an `<svg>` sized `size-6`. */
    logos?: Record<string, ReactNode>;
    /** Brand colours by marketplace name, as CSS colours. */
    colors?: Record<string, string>;
    /** Called with the marketplace name when a tile is clicked. */
    onSelect?: (marketplace: string) => void;
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
                className="flex flex-col gap-4 overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_8%,black_92%,transparent)]"
            >
                {rows.map((row, rowIndex) => (
                    <div
                        key={rowIndex}
                        className={cn(
                            'flex w-max gap-4 group-hover:[animation-play-state:paused]',
                            rowIndex % 2 === 0
                                ? 'animate-marquee'
                                : 'animate-marquee-reverse',
                        )}
                    >
                        {[0, 1].map((copy) => (
                            <ul key={copy} className="flex shrink-0 gap-4">
                                {row.map((marketplace) => (
                                    <li
                                        key={`${copy}-${marketplace}`}
                                        style={{ color: colors[marketplace] }}
                                        onClick={
                                            onSelect &&
                                            (() => onSelect(marketplace))
                                        }
                                        className={cn(
                                            'flex h-20 min-w-20 shrink-0 items-center justify-center rounded-2xl border border-black/[0.07] bg-surface-tile px-6 text-neutral-900 shadow-sm transition-[transform,box-shadow] duration-300 hover:scale-105 hover:shadow-lg',
                                            onSelect && 'cursor-pointer',
                                        )}
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

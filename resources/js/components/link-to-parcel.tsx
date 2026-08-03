import { Check, MessageCircle, Truck } from 'lucide-react';
import type { ComponentProps, ReactNode } from 'react';

import {
    MARKETPLACE_COLORS,
    MARKETPLACE_LOGOS,
} from '@/components/marketplace-logos';
import jacket from '@/images/products/veste-matelassee.webp';
import { cn } from '@/lib/utils';

/**
 * A pasted link turning into a parcel, as a timeline.
 *
 * This is the promise of the product, so the hero states it rather than
 * describing it. The assistant appears as one step of four and not as the
 * subject: what a visitor is buying is the transformation, and a screenshot of
 * a chat window only shows the machinery.
 *
 * Entirely decorative — every figure in it is an illustration, not a real
 * order — so the whole block is hidden from screen readers and cannot be
 * selected.
 */
const PROGRESS = 70;

/**
 * How far apart the four cards light up, inside one loop of the story.
 *
 * The cycle itself lives in the stylesheet; only the offsets are here, because
 * they are what the reading order is made of.
 */
const STEP_DELAY = 2.2;

const stageStyle = (step: number, offset = 0) => ({
    animationDelay: `${(step - 1) * STEP_DELAY + offset}s`,
});

function Card({
    step,
    label,
    highlight,
    children,
}: {
    step: number;
    label: string;
    highlight?: boolean;
    children: ReactNode;
}) {
    return (
        <div
            style={stageStyle(step)}
            className={cn(
                'flex h-full animate-stage flex-col rounded-3xl border p-5 text-left transition-colors sm:p-6',
                highlight
                    ? 'border-transparent bg-accent-brand text-accent-brand-foreground shadow-lg shadow-accent-brand/30'
                    : 'bg-card shadow-sm',
            )}
        >
            <p className="flex items-center gap-2.5">
                <span
                    className={cn(
                        'flex size-6 shrink-0 items-center justify-center rounded-full text-[11px] font-black tabular-nums',
                        highlight
                            ? 'bg-accent-brand-foreground text-accent-brand'
                            : 'bg-accent-brand text-accent-brand-foreground',
                    )}
                >
                    {step}
                </span>
                <span className="font-display text-eyebrow font-extrabold uppercase">
                    {label}
                </span>
            </p>

            <div className="mt-5 flex flex-1 flex-col">{children}</div>
        </div>
    );
}

/**
 * A confirmation line, pinned to the bottom of a card so all four agree.
 */
function Confirmation({
    step,
    children,
    tone = 'success',
}: {
    step: number;
    children: ReactNode;
    tone?: 'success' | 'muted';
}) {
    return (
        <p
            // Half a second behind its card: the tick is the answer, and an
            // answer that lands with the question is not an answer.
            style={stageStyle(step, 0.9)}
            // Pushed to the bottom rather than spaced from the content: the
            // four cards are equal height, and footers that float at whatever
            // height their card's content ends is what makes a row of cards
            // look accidental.
            className={cn(
                'mt-auto flex animate-stage items-center gap-1.5 pt-4 text-xs font-semibold',
                tone === 'success' ? 'text-success' : 'text-muted-foreground',
            )}
        >
            {children}
        </p>
    );
}

export function LinkToParcel({ className, ...props }: ComponentProps<'div'>) {
    return (
        <div aria-hidden {...props} className={cn('select-none', className)}>
            <ol className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
                <li>
                    <Card step={1} label="Lien">
                        {/* The platform's own mark, in the platform's own
                            colour, on white — the same treatment as the tiles
                            further down the page. */}
                        <span
                            className="flex h-11 w-fit items-center rounded-lg border border-black/[0.07] bg-surface-tile px-3"
                            style={{ color: MARKETPLACE_COLORS.Temu }}
                        >
                            {MARKETPLACE_LOGOS.Temu}
                        </span>

                        <p className="mt-3 truncate font-mono text-xs text-muted-foreground">
                            women-jacket-p-8842
                        </p>

                        <Confirmation step={1}>
                            <Check className="size-3.5" />
                            Produit détecté
                        </Confirmation>
                    </Card>
                </li>

                <li>
                    <Card step={2} label="Détails">
                        <ul className="flex flex-wrap gap-1.5">
                            {['Noir', 'XL', '×1'].map((value) => (
                                <li
                                    key={value}
                                    className="rounded-full border bg-background px-3 py-1 text-xs font-semibold"
                                >
                                    {value}
                                </li>
                            ))}
                        </ul>

                        <Confirmation step={2} tone="muted">
                            <MessageCircle className="size-3.5" />
                            Détails enregistrés
                        </Confirmation>
                    </Card>
                </li>

                <li>
                    <Card step={3} label="Commande">
                        <div className="flex items-start gap-3">
                            {/* Eager and sized: it sits in the hero, so lazy
                                loading would only guarantee it arrives late,
                                and the explicit dimensions keep the card from
                                jumping once it does. */}
                            <img
                                src={jacket}
                                alt=""
                                width={56}
                                height={56}
                                className="size-14 shrink-0 rounded-lg border object-cover"
                            />

                            <div className="min-w-0 flex-1">
                                <p className="truncate font-display text-sm font-extrabold">
                                    Veste matelassée
                                </p>
                                <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                    Noir · XL · ×1
                                </p>
                            </div>
                        </div>

                        <p className="mt-3 font-display text-xl font-black tracking-tight tabular-nums">
                            24 500
                            <span className="ml-1 align-middle text-xs font-bold text-muted-foreground">
                                XAF
                            </span>
                        </p>

                        <Confirmation step={3}>
                            <Check className="size-3.5" />
                            Commande validée
                        </Confirmation>
                    </Card>
                </li>

                {/* The arrival, and the only card that is filled: three white
                    cards leading to a gold one is what makes the eye finish the
                    journey instead of stopping halfway. */}
                <li>
                    <Card step={4} label="Livraison" highlight>
                        <p className="font-display text-base font-extrabold text-balance">
                            📦 En route vers Douala
                        </p>

                        <p className="mt-1.5 text-xs text-accent-brand-foreground/70">
                            Expédié le 12 juillet
                        </p>
                        <p className="truncate font-mono text-xs text-accent-brand-foreground/70">
                            SHP-2608-4KJ9X2
                        </p>

                        <div className="mt-auto pt-4">
                            <div className="flex items-center gap-2.5">
                                <Truck className="size-4 shrink-0 animate-shuttle" />

                                <div className="relative h-1.5 flex-1 rounded-full bg-accent-brand-foreground/20">
                                    <div
                                        className="h-full animate-stage-fill rounded-full bg-accent-brand-foreground"
                                        style={{
                                            width: `${PROGRESS}%`,
                                            ...stageStyle(4, 0.3),
                                        }}
                                    />
                                    {/* The head of the fill, marked: a bar that
                                        just stops reads as unfinished, a bar
                                        with a head reads as travelling. */}
                                    <span
                                        className="absolute top-1/2 size-3 -translate-x-1/2 -translate-y-1/2 animate-stage rounded-full border-2 border-accent-brand bg-accent-brand-foreground"
                                        style={{
                                            left: `${PROGRESS}%`,
                                            ...stageStyle(4, 1),
                                        }}
                                    />
                                </div>

                                <span className="shrink-0 text-xs font-black tabular-nums">
                                    {PROGRESS}%
                                </span>
                            </div>
                        </div>
                    </Card>
                </li>
            </ol>
        </div>
    );
}

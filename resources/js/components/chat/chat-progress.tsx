import { Check } from 'lucide-react';

import { cn } from '@/lib/utils';
import type { ChatProgress as Progress } from '@/types';

/**
 * Where the customer stands in their request.
 *
 * Knowing there are six short stages, and which one is running, is what turns a
 * long form into an errand someone is walking you through.
 *
 * A path, rather than a gauge with a list under it. The previous version
 * stacked a hairline progress bar and a row of labels: the bar landed just
 * above the header's own border and the two read as a double rule, and nothing
 * in the row said it was a journey. Here the milestones are joined, and the
 * line fills in behind the customer — the progress *is* the shape, not an
 * indicator set beside it.
 *
 * The line borrows the delivery map's vocabulary: joined dots, blue once
 * reached, grey until then.
 *
 * Below `sm`, six labels cannot sit side by side, so the gauge comes back —
 * headed by the stage's own name rather than by a generic title, which is the
 * one thing worth reading when there is only room for one.
 */
export function ChatProgress({ progress }: { progress: Progress }) {
    const percent = Math.round(
        ((progress.current - 1) / (progress.total - 1)) * 100,
    );

    return (
        <div className="animate-enter">
            <p className="sr-only">
                Étape {progress.current} sur {progress.total}
            </p>

            {/* ── Le chemin, à partir de `sm` ─────────────────────────── */}
            <ol
                className="hidden sm:grid"
                style={{
                    gridTemplateColumns: `repeat(${progress.milestones.length}, minmax(0, 1fr))`,
                }}
            >
                {progress.milestones.map((milestone, index) => {
                    const reached = milestone.state !== 'todo';

                    return (
                        <li
                            key={milestone.label}
                            className="relative flex flex-col items-center gap-2"
                        >
                            {/* Le segment qui rejoint le jalon précédent : il
                                part du centre de celui-ci et s'arrête au centre
                                de celui-là, d'où la largeur pleine tirée depuis
                                la moitié. */}
                            {index > 0 && (
                                <span
                                    aria-hidden
                                    className={cn(
                                        'absolute top-[9px] right-1/2 h-0.5 w-full -translate-y-1/2 rounded-full transition-colors duration-500',
                                        reached ? 'bg-primary' : 'bg-border',
                                    )}
                                />
                            )}

                            <span
                                aria-hidden
                                className={cn(
                                    'relative z-10 flex size-[18px] items-center justify-center rounded-full transition-colors duration-300',
                                    milestone.state === 'done' &&
                                        'bg-primary text-primary-foreground',
                                    milestone.state === 'current' &&
                                        'bg-primary ring-4 ring-primary/15',
                                    milestone.state === 'todo' &&
                                        'border-2 border-border bg-background',
                                )}
                            >
                                {milestone.state === 'done' && (
                                    <Check className="size-3" strokeWidth={3} />
                                )}
                            </span>

                            <span
                                className={cn(
                                    'text-center text-[11px] leading-tight text-balance transition-colors',
                                    milestone.state === 'current'
                                        ? 'font-semibold text-foreground'
                                        : 'text-muted-foreground',
                                    milestone.state === 'todo' &&
                                        'text-muted-foreground/60',
                                )}
                            >
                                {milestone.label}
                            </span>
                        </li>
                    );
                })}
            </ol>

            {/* ── La jauge, en dessous de `sm` ────────────────────────── */}
            <div className="sm:hidden">
                <div className="flex items-baseline justify-between gap-4">
                    <p className="text-sm font-medium">
                        {progress.milestones.find(
                            (milestone) => milestone.state === 'current',
                        )?.label ?? 'Votre demande'}
                    </p>
                    <p className="text-xs text-muted-foreground tabular-nums">
                        {progress.current} / {progress.total}
                    </p>
                </div>

                <div
                    className="mt-2 h-1.5 overflow-hidden rounded-full bg-border"
                    role="progressbar"
                    aria-valuenow={progress.current}
                    aria-valuemin={1}
                    aria-valuemax={progress.total}
                    aria-label="Progression de votre demande"
                >
                    <div
                        className="h-full rounded-full bg-primary transition-[width] duration-500 ease-out"
                        style={{ width: `${Math.max(percent, 4)}%` }}
                    />
                </div>
            </div>
        </div>
    );
}

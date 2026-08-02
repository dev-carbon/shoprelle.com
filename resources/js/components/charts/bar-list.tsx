import { cn } from '@/lib/utils';

export type BarDatum = {
    label: string;
    count: number;
    /** Optional secondary figure shown after the count, e.g. "42 %". */
    note?: string;
};

/**
 * Horizontal bars for a ranked magnitude.
 *
 * One hue throughout: the bars encode "how many", never "which one" — the label
 * beside each bar carries identity. That keeps the chart readable for every kind
 * of colour vision without needing a legend at all.
 *
 * Bars are drawn in plain markup rather than SVG: at this size a div is more
 * accessible, reflows on its own, and costs no charting dependency.
 */
export function BarList({
    data,
    emptyLabel = 'Aucune donnée pour le moment.',
    className,
}: {
    data: BarDatum[];
    emptyLabel?: string;
    className?: string;
}) {
    const max = Math.max(...data.map((datum) => datum.count), 1);
    const rows = data.filter((datum) => datum.count > 0);

    if (rows.length === 0) {
        return (
            <p className={cn('text-sm text-muted-foreground', className)}>
                {emptyLabel}
            </p>
        );
    }

    return (
        <ul className={cn('space-y-2.5', className)}>
            {rows.map((datum) => (
                <li key={datum.label}>
                    <div className="flex items-baseline justify-between gap-4 text-sm">
                        <span className="truncate">{datum.label}</span>
                        <span className="shrink-0 text-muted-foreground tabular-nums">
                            {datum.count}
                            {datum.note && (
                                <span className="ml-1.5">{datum.note}</span>
                            )}
                        </span>
                    </div>
                    <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted">
                        <div
                            className="h-full rounded-full bg-primary transition-[width] duration-500 ease-out"
                            style={{
                                width: `${Math.max((datum.count / max) * 100, 2)}%`,
                            }}
                        />
                    </div>
                </li>
            ))}
        </ul>
    );
}

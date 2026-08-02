import { useId, useState } from 'react';

export type TrendPoint = {
    date: string;
    label: string;
    count: number;
};

const WIDTH = 720;
const HEIGHT = 160;
const PADDING = { top: 12, right: 4, bottom: 4, left: 4 };

/**
 * A single-series area chart of requests over time.
 *
 * One series, so no legend: the card title names it. Hovering reveals a
 * crosshair and the exact figure, because a shape alone never answers "how many
 * on the 14th".
 *
 * Drawn as inline SVG with a viewBox, so it scales to any container width
 * without a charting library.
 */
export function TrendChart({ data }: { data: TrendPoint[] }) {
    const gradientId = useId();
    const [active, setActive] = useState<number | null>(null);

    if (data.length < 2) {
        return (
            <p className="text-sm text-muted-foreground">
                Pas encore assez de données pour tracer une tendance.
            </p>
        );
    }

    const max = Math.max(...data.map((point) => point.count), 1);
    const plotWidth = WIDTH - PADDING.left - PADDING.right;
    const plotHeight = HEIGHT - PADDING.top - PADDING.bottom;

    const x = (index: number) =>
        PADDING.left + (index / (data.length - 1)) * plotWidth;
    const y = (count: number) =>
        PADDING.top + plotHeight - (count / max) * plotHeight;

    const line = data
        .map((point, index) => `${x(index)},${y(point.count)}`)
        .join(' ');

    const area = `${PADDING.left},${PADDING.top + plotHeight} ${line} ${PADDING.left + plotWidth},${PADDING.top + plotHeight}`;

    const point = active === null ? null : data[active];

    return (
        <div className="relative">
            <svg
                viewBox={`0 0 ${WIDTH} ${HEIGHT}`}
                className="w-full"
                role="img"
                aria-label={`Demandes par jour, maximum ${max}`}
                preserveAspectRatio="none"
                onMouseLeave={() => setActive(null)}
            >
                <defs>
                    <linearGradient id={gradientId} x1="0" y1="0" x2="0" y2="1">
                        <stop
                            offset="0%"
                            stopColor="var(--color-primary)"
                            stopOpacity="0.18"
                        />
                        <stop
                            offset="100%"
                            stopColor="var(--color-primary)"
                            stopOpacity="0"
                        />
                    </linearGradient>
                </defs>

                <polygon points={area} fill={`url(#${gradientId})`} />

                <polyline
                    points={line}
                    fill="none"
                    stroke="var(--color-primary)"
                    strokeWidth="2"
                    strokeLinejoin="round"
                    strokeLinecap="round"
                    vectorEffect="non-scaling-stroke"
                />

                {active !== null && (
                    <g>
                        <line
                            x1={x(active)}
                            x2={x(active)}
                            y1={PADDING.top}
                            y2={PADDING.top + plotHeight}
                            stroke="var(--color-border)"
                            strokeWidth="1"
                            vectorEffect="non-scaling-stroke"
                        />
                        <circle
                            cx={x(active)}
                            cy={y(data[active].count)}
                            r="4"
                            fill="var(--color-primary)"
                            stroke="var(--color-card)"
                            strokeWidth="2"
                            vectorEffect="non-scaling-stroke"
                        />
                    </g>
                )}

                {/* Invisible hit areas, far wider than the marks themselves. */}
                {data.map((datum, index) => (
                    <rect
                        key={datum.date}
                        x={x(index) - plotWidth / data.length / 2}
                        y={0}
                        width={plotWidth / data.length}
                        height={HEIGHT}
                        fill="transparent"
                        onMouseEnter={() => setActive(index)}
                    />
                ))}
            </svg>

            <div className="mt-2 flex items-center justify-between text-xs text-muted-foreground">
                <span>{data[0].label}</span>
                {point ? (
                    <span className="font-medium text-foreground">
                        {point.label} — {point.count} demande
                        {point.count > 1 ? 's' : ''}
                    </span>
                ) : (
                    <span>Survolez la courbe pour le détail</span>
                )}
                <span>{data[data.length - 1].label}</span>
            </div>
        </div>
    );
}

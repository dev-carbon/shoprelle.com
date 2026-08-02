import { geoCentroid, geoEqualEarth, geoPath } from 'd3-geo';
import { memo, useMemo, useState } from 'react';

import world from '@/data/world.json';
import { useInView } from '@/hooks/use-in-view';
import { cn } from '@/lib/utils';

/**
 * ISO 3166-1 numeric ids for the destinations, keyed by the alpha-2 codes
 * `config/shoprelle.php` uses.
 *
 * The map's own features carry the numeric code and an English name; ours are
 * keyed by alpha-2 and named in French, so the two have to be joined on
 * something, and the code is the only thing that will not drift.
 *
 * An id with no matching country is simply never looked up, so opening a
 * destination is a line in `config/shoprelle.php` and nothing here.
 */
const ISO_NUMERIC: Record<string, string> = {
    CM: '120', // Cameroun
    CI: '384', // Côte d'Ivoire
    SN: '686', // Sénégal
    GA: '266', // Gabon
    CG: '178', // Congo
};

/** France, where every order is bought and every parcel leaves from. */
const ORIGIN_ID = '250';

/**
 * The hub itself, as a point rather than as France's centroid.
 *
 * Paris, because that is a place a parcel can leave from; the centroid of the
 * country is a spot in the Berry that means nothing to anybody, and it moves
 * the day the outline file is regenerated at a different resolution.
 */
const HUB: [number, number] = [2.35, 48.85];

const WIDTH = 1000;
const HEIGHT = 500;

/**
 * The window the map is cropped to, in degrees.
 *
 * The projection still covers the whole globe — this only decides what is in
 * frame. Fitted to the land, the map spent a third of its width on the Pacific
 * and the destinations came out too small to read; cropped here, Africa is
 * nearly half again as large.
 *
 * The eastern edge is 158°, not 135°: at 135 the crop runs through the middle
 * of Australia, and a continent cut in half reads as a bug rather than a frame.
 */
const FRAME: [[number, number], [number, number]] = [
    [-95, -45],
    [158, 66],
];

/**
 * Equal Earth, not Mercator: on a whole world Mercator makes Greenland the size
 * of Africa, which is exactly the wrong impression for a page about shipping to
 * Africa. Fitted to the globe rather than to the data, so the antimeridian —
 * which Russia and Fiji both straddle — cannot throw the extent.
 */
const projection = geoEqualEarth().fitExtent(
    [
        [8, 8],
        [WIDTH - 8, HEIGHT - 8],
    ],
    { type: 'Sphere' },
);

const toPath = geoPath(projection);

/**
 * The viewport, measured by walking the frame's own boundary.
 *
 * A rectangle in degrees is not a rectangle once projected — its edges bow — so
 * the corners alone would crop the map inside its own frame. Sampling along all
 * four sides catches the bulge.
 *
 * Kept as numbers and not only as a `viewBox` string: the markers are HTML laid
 * over the drawing rather than shapes inside it, and they are placed by turning
 * a projected point back into a percentage of this box.
 */
const VIEW = ((): { x: number; y: number; width: number; height: number } => {
    const [[west, south], [east, north]] = FRAME;

    let minX = Infinity;
    let minY = Infinity;
    let maxX = -Infinity;
    let maxY = -Infinity;

    const measure = (lon: number, lat: number) => {
        const point = projection([lon, lat]);

        if (!point) {
            return;
        }

        minX = Math.min(minX, point[0]);
        maxX = Math.max(maxX, point[0]);
        minY = Math.min(minY, point[1]);
        maxY = Math.max(maxY, point[1]);
    };

    const steps = 60;

    for (let i = 0; i <= steps; i++) {
        const lon = west + ((east - west) * i) / steps;
        const lat = south + ((north - south) * i) / steps;

        measure(lon, south);
        measure(lon, north);
        measure(west, lat);
        measure(east, lat);
    }

    return { x: minX, y: minY, width: maxX - minX, height: maxY - minY };
})();

const VIEW_BOX = [VIEW.x, VIEW.y, VIEW.width, VIEW.height].join(' ');

type Feature = (typeof world)['features'][number];

/** Every outline, built once at module scope: the geometry never changes. */
const SHAPES: { id: string; d: string }[] = world.features.flatMap(
    (feature: Feature) => {
        const d = toPath(feature as unknown as Parameters<typeof toPath>[0]);

        return d ? [{ id: String(feature.id), d }] : [];
    },
);

/** The same outlines, addressable by id, for the one country under the cursor. */
const SHAPE_BY_ID: Record<string, string> = Object.fromEntries(
    SHAPES.map((shape) => [shape.id, shape.d]),
);

/** Where each country's marker goes, already projected. */
const POINTS: Record<string, [number, number]> = Object.fromEntries(
    world.features.flatMap((feature: Feature) => {
        const point = projection(
            geoCentroid(
                feature as unknown as Parameters<typeof geoCentroid>[0],
            ),
        );

        return point ? [[String(feature.id), point] as const] : [];
    }),
);

const SPHERE = toPath({ type: 'Sphere' } as unknown as Parameters<
    typeof toPath
>[0]);

const HUB_POINT = projection(HUB) ?? [0, 0];

const round = (value: number) => Math.round(value * 100) / 100;

/**
 * Where a projected point sits inside the frame, as percentages.
 *
 * The drawing is an `<svg>` at full width with `height: auto`, so the element's
 * box and its `viewBox` share an aspect ratio exactly — which is what lets an
 * HTML marker be placed on a percentage and land on the country underneath it,
 * at every width, without anything ever being measured.
 */
const percent = ([x, y]: [number, number]) => ({
    left: `${round(((x - VIEW.x) / VIEW.width) * 100)}%`,
    top: `${round(((y - VIEW.y) / VIEW.height) * 100)}%`,
});

/**
 * The route from the hub to a destination, as a quadratic arc.
 *
 * A straight line between two points on a world map reads as a diagram; the
 * bow is what makes it read as a flight. Its control point is pushed off the
 * midpoint along the perpendicular, always to the same side and always by the
 * same fraction of the distance — so several routes leaving one hub fan out
 * evenly and never cross each other.
 *
 * That side is the west one, which sends the arcs out over the Atlantic rather
 * than across the Sahara: an empty ground behind a curve is what keeps it
 * legible.
 */
const arc = (to: [number, number]): string => {
    const [x1, y1] = HUB_POINT;
    const [x2, y2] = to;

    const span = Math.hypot(x2 - x1, y2 - y1);

    if (span === 0) {
        return `M ${round(x1)} ${round(y1)}`;
    }

    const bow = span * 0.17;
    const controlX = (x1 + x2) / 2 - ((y2 - y1) / span) * bow;
    const controlY = (y1 + y2) / 2 + ((x2 - x1) / span) * bow;

    return `M ${round(x1)} ${round(y1)} Q ${round(controlX)} ${round(controlY)} ${round(x2)} ${round(y2)}`;
};

export type Destination = { code: string; name: string };

/** A destination that was found on the map, with its route already traced. */
type Placed = Destination & {
    id: string;
    point: [number, number];
    route: string;
    served: boolean;
};

/**
 * The drawing: the world, the routes, and the parcels running them.
 *
 * Memoised and deliberately unaware of what the cursor is doing. This is a
 * hundred and sixty-nine paths, and re-conciling all of them every time a
 * pointer crosses a marker is work with nothing to show for it — the highlight
 * is one extra path in a layer of its own, above.
 */
const Network = memo(function Network({
    destinations,
    drawn,
}: {
    destinations: Placed[];
    drawn: boolean;
}) {
    const tiers = useMemo(() => {
        const byId: Record<string, 'served' | 'upcoming'> = {};

        for (const destination of destinations) {
            byId[destination.id] = destination.served ? 'served' : 'upcoming';
        }

        return byId;
    }, [destinations]);

    return (
        <svg
            viewBox={VIEW_BOX}
            className="block h-auto w-full"
            role="presentation"
            focusable="false"
            aria-hidden
        >
            {/* The globe's edge, as a fill and nothing more: it gives the land
                something to sit in without drawing attention to a diagram
                nobody is here to read. */}
            {SPHERE && (
                <path d={SPHERE} className="fill-muted-foreground/[0.05]" />
            )}

            {/* Four tiers, separated by measurement rather than by eye: solid
                blue reads at 4.75:1 on the band behind it, the announced
                countries at 2.29:1, and the rest of the world at 1.21:1 — near
                enough to the ground to sit back as context. France is a step
                above that last one: it has to be findable as the origin of
                every route without ever competing with a destination. */}
            {SHAPES.map((shape) => (
                <path
                    key={shape.id}
                    d={shape.d}
                    strokeWidth={0.5}
                    className={cn(
                        'stroke-background',
                        tiers[shape.id] === 'served'
                            ? 'fill-primary'
                            : tiers[shape.id] === 'upcoming'
                              ? 'fill-primary/55'
                              : shape.id === ORIGIN_ID
                                ? 'fill-muted-foreground/35'
                                : 'fill-muted-foreground/15',
                    )}
                />
            ))}

            {/* The network. Routes to open destinations get a soft underlay a
                few units wide, which is the whole of the depth in this drawing:
                a blurred shadow at this scale only makes the line look
                out of focus. */}
            <g fill="none" strokeLinecap="round">
                {destinations.map((destination, index) => (
                    <g key={destination.code}>
                        {destination.served && (
                            <path
                                d={destination.route}
                                pathLength={1}
                                strokeWidth={4}
                                strokeDasharray={1}
                                strokeDashoffset={drawn ? undefined : 1}
                                style={{ animationDelay: `${index * 140}ms` }}
                                className={cn(
                                    'stroke-primary/15',
                                    drawn && 'animate-route',
                                )}
                            />
                        )}

                        {/* `pathLength="1"` rescales the dash maths to a single
                            unit, so one keyframe draws a short hop and a long
                            haul alike — see the stylesheet. Until the section
                            is reached the offset holds the route fully
                            retracted; a map that arrives already finished has
                            nothing left to say. */}
                        <path
                            d={destination.route}
                            pathLength={1}
                            strokeWidth={destination.served ? 1.4 : 1}
                            strokeDasharray={1}
                            strokeDashoffset={drawn ? undefined : 1}
                            style={{ animationDelay: `${index * 140}ms` }}
                            className={cn(
                                destination.served
                                    ? 'stroke-primary'
                                    : 'stroke-primary/35',
                                drawn && 'animate-route',
                            )}
                        />
                    </g>
                ))}
            </g>

            {/* The parcels. Only on the routes that are actually flying: a dot
                travelling to a country the assistant still refuses would be the
                page promising something the conversation takes back.

                Held back until the routes exist, because `offset-path` places
                the dot along a curve — with no curve drawn yet it would sit
                waiting at the origin of the coordinate system, in the middle of
                the Atlantic. */}
            {drawn &&
                destinations
                    .filter((destination) => destination.served)
                    .map((destination, index) => (
                        <circle
                            key={destination.code}
                            r={2.6}
                            style={{
                                offsetPath: `path('${destination.route}')`,
                                animationDelay: `${1200 + index * 900}ms`,
                            }}
                            className="route-parcel fill-primary"
                        />
                    ))}
        </svg>
    );
});

/**
 * One destination, as a marker laid over the drawing.
 *
 * HTML rather than a shape inside the SVG, which is the whole reason this is
 * reachable at all: a `<button>` is focusable, nameable and hit-testable for
 * free, where the same behaviour on an SVG `<g>` has to be rebuilt by hand and
 * still announces itself differently in every screen reader.
 */
function Marker({
    destination,
    index,
    active,
    onActivate,
    onDismiss,
}: {
    destination: Placed;
    index: number;
    active: boolean;
    onActivate: () => void;
    onDismiss: () => void;
}) {
    return (
        <li className="absolute" style={percent(destination.point)}>
            <button
                type="button"
                aria-label={
                    destination.served
                        ? `${destination.name} — livraison ouverte`
                        : `${destination.name} — bientôt`
                }
                onMouseEnter={onActivate}
                onMouseLeave={onDismiss}
                onFocus={onActivate}
                onBlur={onDismiss}
                className="absolute flex size-7 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
            >
                {/* A ping on each country that ships today. Two rings half a
                    cycle apart, so one is always on its way out as the next
                    leaves — a single ring reads as a stutter.

                    The announced countries get none: the point of the marker is
                    to say "here, now", and putting one on a country the
                    assistant still refuses would say the opposite. */}
                {destination.served &&
                    [0, 1.5].map((delay) => (
                        <span
                            key={delay}
                            aria-hidden
                            style={{
                                animationDelay: `${index * 0.25 + delay}s`,
                            }}
                            className="absolute size-3 animate-ripple rounded-full bg-primary/40"
                        />
                    ))}

                <span
                    aria-hidden
                    className={cn(
                        'relative rounded-full transition-transform duration-200',
                        active && 'scale-125',
                        destination.served
                            ? 'size-3 bg-primary ring-2 ring-background'
                            : 'size-2.5 border-2 border-primary/70 bg-background',
                    )}
                />
            </button>
        </li>
    );
}

/**
 * Shoprelle's delivery network, drawn on the whole world.
 *
 * Plain SVG over `d3-geo`, with no mapping library above it: nothing here pans
 * or zooms, so a library offering those would only be bringing its own copy of
 * d3 along for the ride. The outlines ship with the page rather than being
 * fetched from a CDN — a landing page should not phone a third party before it
 * can finish drawing itself.
 *
 * The drawing carries no name a screen reader could read, so it is hidden and
 * the destinations are exposed as an ordinary list of buttons on top of it —
 * which is also what makes them reachable by keyboard. The page repeats them in
 * text below regardless: a map is never the only place a fact should live.
 */
export function DeliveryMap({
    countries,
    upcomingCountries = [],
    className,
}: {
    countries: Destination[];
    upcomingCountries?: Destination[];
    className?: string;
}) {
    // A late trigger on purpose: the routes draw over a second and a half, and
    // a network that finished animating above the fold was never watched.
    const { ref, inView } = useInView<HTMLDivElement>('0px 0px -25% 0px');
    const [active, setActive] = useState<string | null>(null);

    const destinations = useMemo<Placed[]>(() => {
        const listed = [
            ...countries.map((country) => ({ ...country, served: true })),
            ...upcomingCountries.map((country) => ({
                ...country,
                served: false,
            })),
        ];

        return listed.flatMap((destination) => {
            const id = ISO_NUMERIC[destination.code];
            const point = id ? POINTS[id] : undefined;

            return point
                ? [{ ...destination, id, point, route: arc(point) }]
                : [];
        });
    }, [countries, upcomingCountries]);

    const highlighted = destinations.find(
        (destination) => destination.code === active,
    );

    return (
        <div ref={ref} className={cn('relative select-none', className)}>
            <Network destinations={destinations} drawn={inView} />

            {/* The country under the cursor, redrawn on top rather than
                restyled in place: the layer below is memoised, and reaching
                into it would cost a reconciliation of the entire world to
                change one fill. */}
            <svg
                viewBox={VIEW_BOX}
                aria-hidden
                role="presentation"
                focusable="false"
                className="pointer-events-none absolute inset-0 block h-auto w-full"
            >
                {highlighted && SHAPE_BY_ID[highlighted.id] && (
                    <path
                        d={SHAPE_BY_ID[highlighted.id]}
                        strokeWidth={1}
                        className={cn(
                            'stroke-primary',
                            highlighted.served
                                ? 'fill-primary'
                                : 'fill-primary/70',
                        )}
                    />
                )}
            </svg>

            {/* The hub. Gold rather than blue, because it is the one point on
                this map that is not a destination — and gold is the only other
                colour the brand allows itself. */}
            <div className="absolute" style={percent(HUB_POINT)}>
                <span
                    aria-hidden
                    className="absolute flex size-8 -translate-x-1/2 -translate-y-1/2 items-center justify-center"
                >
                    {[0, 1.5].map((delay) => (
                        <span
                            key={delay}
                            style={{ animationDelay: `${delay}s` }}
                            className="absolute size-3.5 animate-ripple rounded-full bg-accent-brand/50"
                        />
                    ))}
                    <span className="relative size-3.5 rounded-full bg-accent-brand ring-2 ring-background" />
                </span>

                {/* Named on the map because the hub is the claim the section is
                    making — "we buy in France" — and a gold dot alone makes it
                    only on a screen wide enough to have somewhere to put the
                    words. */}
                <span
                    aria-hidden
                    className="absolute top-1/2 left-0 ml-4 hidden -translate-y-1/2 rounded-full border bg-card px-2.5 py-1 font-display text-[11px] font-extrabold whitespace-nowrap shadow-sm sm:inline-block"
                >
                    Hub · France
                </span>
            </div>

            <ul>
                {destinations.map((destination, index) => (
                    <Marker
                        key={destination.code}
                        destination={destination}
                        index={index}
                        active={destination.code === active}
                        onActivate={() => setActive(destination.code)}
                        onDismiss={() =>
                            setActive((current) =>
                                current === destination.code ? null : current,
                            )
                        }
                    />
                ))}
            </ul>

            {/* The tooltip, kept out of the accessibility tree: the button
                underneath already carries the same words as its name, and a
                screen reader that meets both reads the country twice. */}
            {highlighted && (
                <div
                    aria-hidden
                    style={percent(highlighted.point)}
                    className="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full pb-4"
                >
                    <div className="animate-enter rounded-lg border bg-popover px-3 py-2 text-center shadow-md">
                        <p className="font-display text-sm font-extrabold whitespace-nowrap text-popover-foreground">
                            {highlighted.name}
                        </p>
                        <p
                            className={cn(
                                'mt-0.5 text-[11px] font-semibold whitespace-nowrap',
                                highlighted.served
                                    ? 'text-success'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {highlighted.served
                                ? 'Livraison ouverte'
                                : 'Bientôt'}
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
}

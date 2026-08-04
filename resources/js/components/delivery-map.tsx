import { memo, useMemo, useState } from 'react';

import atlas from '@/data/world-atlas.json';
import { CAN_OBSERVE, useInView } from '@/hooks/use-in-view';
import { ISO_NUMERIC, ORIGIN_ID, ROUTE_SIDE } from '@/lib/destinations';
import { cn } from '@/lib/utils';

/**
 * The world, projected and committed rather than computed.
 *
 * `world-atlas.json` was generated once from the country outlines — Equal
 * Earth, cropped to the frame the section wants — by an ad hoc script. Which is
 * why nothing here imports `d3-geo`: the projection ran at build time, and what
 * ships is a path string per country.
 *
 * ── Ce que le fichier ne contient plus
 *
 * Les tracés sont arrondis à l'unité. Le cadre fait 957 unités de large pour
 * une carte rendue au plus autour de 1200 px : une unité vaut à peu près un
 * pixel, donc la décimale valait un dixième de pixel et pesait un tiers du
 * fichier. Les ancres et le hub, eux, gardent la leur — ce sont eux qui posent
 * un marqueur sur un pays, et un demi-pixel s'y verrait.
 *
 * Le `graticule` a disparu avec : vingt et un kilo-octets de méridiens et de
 * parallèles, chargés par chaque visiteur, que ce composant ne dessine plus
 * depuis qu'il les a retirés — et que seule cette annotation de type lisait
 * encore.
 *
 * The shape is asserted rather than inferred: a JSON module widens every array
 * to `number[]`, so the pairs and the viewport come back having forgotten how
 * many numbers they hold. The generator fixes that shape, and this is the one
 * place that says so.
 */
const {
    view: ATLAS_VIEW,
    hub: HUB_POINT,
    anchors: ANCHORS,
    shapes: SHAPES,
} = atlas as unknown as {
    view: [number, number, number, number];
    hub: [number, number];
    anchors: Record<string, [number, number]>;
    shapes: Record<string, string>;
};

/**
 * Le cadre effectivement dessiné : celui de l'atlas, resserré.
 *
 * L'atlas cadre le monde entier, alors que tout ce qui bouge ici tient dans une
 * bande étroite entre la France et le golfe de Guinée — le reste n'est que du
 * contexte. Resserrer autour d'elle grossit ce qu'on vient regarder sans
 * toucher à la projection : rien n'est reprojeté, on regarde la même image de
 * plus près.
 *
 * Le rapport hauteur/largeur est conservé, ce qui est la condition de tout le
 * reste : les marqueurs sont du HTML posé en pourcentages de ce cadre, et ils
 * ne retombent sur le bon pays que parce que la boîte de l'`<svg>` et son
 * `viewBox` ont exactement la même forme.
 *
 * Le point visé n'est pas le centre du cadre mais celui du trafic, qui se tient
 * plus haut : centrer sur le cadre aurait grossi l'Atlantique sud.
 */
const ZOOM = 1.2;

const FOCUS: [number, number] = [500, 190];

const VIEW: [number, number, number, number] = (() => {
    const width = ATLAS_VIEW[2] / ZOOM;
    const height = ATLAS_VIEW[3] / ZOOM;

    return [FOCUS[0] - width / 2, FOCUS[1] - height / 2, width, height];
})();

const VIEW_BOX = VIEW.join(' ');

/**
 * Le méridien-parallèle a disparu du dessin, et c'est le premier des trois
 * changements qui séparent cette carte de la précédente.
 *
 * Un quadrillage de méridiens dit « atlas scolaire » avant de dire quoi que ce
 * soit d'autre. Il était là pour signifier « le globe » ; mais la projection le
 * dit déjà — l'Équateur se voit à la forme de l'Afrique, pas à un trait posé
 * dessus — et il coûtait une trame bleue de plus sous un réseau qui est
 * lui-même bleu. La donnée reste dans l'atlas, personne ne la dessine.
 *
 * Les deux autres changements sont dans le `<defs>` en dessous : le sol prend
 * un dégradé au lieu d'un aplat, et les pays desservis émettent de la lumière.
 */

/** Où se trouve le hub dans le cadre, en fractions — pour l'atmosphère. */
const HUB_FRACTION = {
    x: (HUB_POINT[0] - VIEW[0]) / VIEW[2],
    y: (HUB_POINT[1] - VIEW[1]) / VIEW[3],
};

/**
 * The frame, dissolved rather than cut.
 *
 * This runs edge to edge of the page, so the crop lands in the middle of
 * Scandinavia at the top and of the Pacific at the sides. A hard edge there
 * reads as a screenshot of a bigger map; fading the last few percent reads as a
 * map that simply stops. It is kept off the routes and the markers — nothing
 * that carries meaning is anywhere near an edge.
 */
const EDGE_MASK = [
    'linear-gradient(to bottom, transparent 0%, black 9%, black 94%, transparent 100%)',
    'linear-gradient(to right, transparent 0%, black 6%, black 94%, transparent 100%)',
].join(', ');

/**
 * Every landmass on Earth, as a single path built once at module scope.
 *
 * One path for a hundred and sixty-seven countries, which is what lets the
 * ground read as a single silhouette: its parts are told apart by a hairline of
 * the page's own colour, not by a hundred and sixty-seven drawn borders.
 *
 * The lit countries are drawn *over* this rather than cut out of it: overdrawing
 * five shapes costs nothing, and subtracting them would mean rebuilding the
 * whole basemap every time the destination list changes.
 */
const LAND = Object.values(SHAPES).join('');

const ORIGIN_SHAPE = SHAPES[ORIGIN_ID] ?? '';

export type Destination = {
    code: string;
    name: string;
    /** Null for any country nobody has measured; the tooltip drops the line. */
    deliveryTime?: string | null;
};

/** A destination that was found on the map, with its route already traced. */
type Placed = Destination & {
    id: string;
    point: [number, number];
    route: string;
    served: boolean;
};

const round = (value: number) => Math.round(value * 100) / 100;

/**
 * Where a point sits inside the frame, as percentages.
 *
 * The drawing is an `<svg>` at full width with `height: auto`, so the element's
 * box and its `viewBox` share an aspect ratio exactly — which is what lets an
 * HTML marker be placed on a percentage and land on the country underneath it, at
 * every width, without anything ever being measured.
 */
const percent = ([x, y]: [number, number]) => ({
    left: `${round(((x - VIEW[0]) / VIEW[2]) * 100)}%`,
    top: `${round(((y - VIEW[1]) / VIEW[3]) * 100)}%`,
});

/**
 * The route from the hub to a destination, as a quadratic arc.
 *
 * A straight line between two points on a world map reads as a diagram; the bow
 * is what makes it read as a flight. The control point is pushed off the
 * midpoint along the perpendicular, by a generous fraction of the distance —
 * a timid curve just looks like a line somebody failed to draw straight.
 *
 * Which side it bows to is the destination's own: everything west of Paris
 * bows further west, everything east of it bows east. So the routes leave the
 * hub as a fan opening on both sides rather than as a bundle all leaning the
 * same way, and no two of them cross. `ROUTE_SIDE` overrides that where the
 * longitude gives an answer the frame disagrees with — see the note there.
 */
const BOW = 0.4;

const arc = (to: [number, number], override?: 'west' | 'east'): string => {
    const [x1, y1] = HUB_POINT;
    const [x2, y2] = to;

    const span = Math.hypot(x2 - x1, y2 - y1);

    if (span === 0) {
        return `M ${round(x1)} ${round(y1)}`;
    }

    const side = (override ?? (x2 < x1 ? 'west' : 'east')) === 'west' ? 1 : -1;
    const bow = span * BOW * side;

    const controlX = (x1 + x2) / 2 - ((y2 - y1) / span) * bow;
    const controlY = (y1 + y2) / 2 + ((x2 - x1) / span) * bow;

    return `M ${round(x1)} ${round(y1)} Q ${round(controlX)} ${round(controlY)} ${round(x2)} ${round(y2)}`;
};

/**
 * The drawing: the world, the routes, and the parcels running them.
 *
 * Memoised and deliberately unaware of what the cursor is doing, so a pointer
 * crossing a marker cannot cost a redraw of the map — the highlight is one
 * extra path in a layer of its own, above.
 */
const Network = memo(function Network({
    destinations,
    drawn,
}: {
    destinations: Placed[];
    drawn: boolean;
}) {
    const lit = useMemo(() => {
        const tier = (served: boolean) =>
            destinations
                .filter((destination) => destination.served === served)
                .map((destination) => SHAPES[destination.id] ?? '')
                .join('');

        return { served: tier(true), upcoming: tier(false) };
    }, [destinations]);

    return (
        <svg
            viewBox={VIEW_BOX}
            className="block h-auto w-full"
            role="presentation"
            focusable="false"
            aria-hidden
            style={{
                maskImage: EDGE_MASK,
                maskComposite: 'intersect',
                WebkitMaskImage: EDGE_MASK,
                WebkitMaskComposite: 'source-in',
            }}
        >
            <defs>
                {/*
                 * L'atmosphère : une seule source de lumière, posée sur le hub.
                 *
                 * C'est ce qui fait qu'une carte plate n'est pas une carte
                 * morte. Rien à y lire — c'est un fond, à une opacité où l'on
                 * ne saurait pas dire s'il est là — mais il donne au dessin un
                 * point d'où la lumière vient, et tout le réseau part de ce
                 * point-là. Le hasard fait bien les choses : c'est aussi d'où
                 * partent les colis.
                 */}
                <radialGradient
                    id="shoprelle-map-atmosphere"
                    className="text-primary"
                    cx={HUB_FRACTION.x}
                    cy={HUB_FRACTION.y}
                    r={0.75}
                >
                    <stop
                        offset="0%"
                        stopColor="currentColor"
                        stopOpacity={0.14}
                    />
                    <stop
                        offset="100%"
                        stopColor="currentColor"
                        stopOpacity={0}
                    />
                </radialGradient>

                {/* Le sol, en dégradé du haut vers le bas plutôt qu'en aplat.
                    Neuf points d'opacité d'écart, ce qui ne se voit pas et se
                    ressent : l'aplat donnait au monde l'air d'un autocollant. */}
                <linearGradient
                    id="shoprelle-map-land"
                    className="text-muted-foreground"
                    x1="0"
                    y1="0"
                    x2="0"
                    y2="1"
                >
                    <stop
                        offset="0%"
                        stopColor="currentColor"
                        stopOpacity={0.19}
                    />
                    <stop
                        offset="100%"
                        stopColor="currentColor"
                        stopOpacity={0.1}
                    />
                </linearGradient>

                {/*
                 * Le halo des pays desservis.
                 *
                 * C'est le seul filtre de la carte, et il est appliqué à une
                 * poignée de formes qui ne bougent jamais : le navigateur le
                 * calcule une fois et le garde en cache. La pulsation est posée
                 * sur un groupe *au-dessus* du filtre, et joue sur l'opacité
                 * seule — animer quoi que ce soit à l'intérieur du filtre le
                 * ferait recalculer à chaque image, ce qui est la façon la plus
                 * sûre de rendre une carte saccadée.
                 */}
                <filter
                    id="shoprelle-map-glow"
                    x="-25%"
                    y="-25%"
                    width="150%"
                    height="150%"
                    colorInterpolationFilters="sRGB"
                >
                    <feGaussianBlur stdDeviation={2.4} />
                </filter>
            </defs>

            <rect
                x={VIEW[0]}
                y={VIEW[1]}
                width={VIEW[2]}
                height={VIEW[3]}
                fill="url(#shoprelle-map-atmosphere)"
            />

            {/* Four tiers, each a single flat fill.

                Measured rather than eyeballed: the ground sits at 1.21:1 on the
                page behind it — near enough to sit back as context — the
                announced countries at 2.29:1, and the open ones at 4.75:1.

                Each tier is opaque inside a group that is *then* faded, rather
                than being drawn in a translucent colour. Two reasons, and both
                of them are visible: a stroke over its own translucent fill
                double-blends into a brighter coastline, and the outlines are
                simplified to a tenth of a degree, so neighbouring countries no
                longer share an exact border and the hairline gaps between them
                show through as darker seams. Stroking each tier in its own
                colour closes those gaps; flattening the group before fading it
                is what stops the repair from being visible. */}
            <g className="text-muted-foreground">
                <path
                    d={LAND}
                    fill="url(#shoprelle-map-land)"
                    stroke="currentColor"
                    strokeOpacity={0.15}
                    strokeWidth={0.6}
                    strokeLinejoin="round"
                />
            </g>

            {/* The countries, separated by a hairline of the page's own colour
                rather than by a drawn border. It is the ground showing through,
                not a line added on top, which is what keeps the landmass
                reading as one silhouette while its parts stay legible.

                Only on the base: a lit country is drawn over this and comes out
                whole, which is the point — the eye should find one shape, not a
                country with its provinces marked. */}
            <path
                d={LAND}
                fill="none"
                strokeWidth={0.45}
                className="stroke-background/60"
            />

            {/* France, a step up from the ground: it has to be findable as the
                origin of every route without ever competing with a
                destination. */}
            <g className="text-muted-foreground" opacity={0.35}>
                <path
                    d={ORIGIN_SHAPE}
                    fill="currentColor"
                    stroke="currentColor"
                    strokeWidth={0.6}
                    strokeLinejoin="round"
                />
            </g>

            <g className="text-primary" opacity={0.45}>
                <path
                    d={lit.upcoming}
                    fill="currentColor"
                    stroke="currentColor"
                    strokeWidth={0.6}
                    strokeLinejoin="round"
                />
            </g>

            {/* Les pays desservis émettent de la lumière : le halo d'abord, la
                forme nette par-dessus. C'est ce qui remplace le contour or de
                la version précédente, et ce qui fait la différence entre une
                carte où cinq pays sont coloriés et une carte où cinq pays sont
                allumés. La pulsation est très lente et n'atteint jamais zéro —
                un clignotement dirait « alerte », pas « en service ». */}
            <g className="animate-glow">
                <g filter="url(#shoprelle-map-glow)">
                    <path d={lit.served} className="fill-primary" />
                </g>
            </g>

            <path d={lit.served} className="fill-primary" />

            {/* The destinations, outlined in the brand gold.

                The gold is the only other colour the palette allows, and it is
                spent here rather than on the world's borders. Every country
                edged in gold would be an atlas in fancy dress, and the eye
                would have nowhere to land; on these five it does the opposite,
                lifting them off a blue map that is otherwise all one hue —
                and tying them to the gold hub they are fed from. */}
            <g fill="none" strokeLinejoin="round">
                <path
                    d={lit.upcoming}
                    strokeWidth={0.8}
                    className="stroke-accent-brand/40"
                />
                {/* Plus fin qu'avant : le halo bleu fait désormais le travail
                    de détachement, et un contour épais par-dessus une forme qui
                    rayonne se voit comme un cerne. */}
                <path
                    d={lit.served}
                    strokeWidth={1.1}
                    className="stroke-accent-brand"
                />
            </g>

            {/* The network. Routes to open destinations get a soft underlay a
                few units wide, which is the whole of the depth in this drawing:
                a blurred shadow at this scale only makes the line look out of
                focus. */}
            <g fill="none" strokeLinecap="round">
                {destinations.map((destination, index) => (
                    <g key={destination.code}>
                        {/* Deux sous-couches au lieu d'une, larges et très
                            pâles. C'est toute la profondeur de ce dessin : une
                            ombre floutée à cette échelle ne donne pas du relief
                            à un trait d'un point et demi, elle le rend flou. */}
                        {destination.served &&
                            [
                                { width: 8, tone: 'stroke-primary/8' },
                                { width: 4, tone: 'stroke-primary/18' },
                            ].map((layer) => (
                                <path
                                    key={layer.width}
                                    d={destination.route}
                                    pathLength={1}
                                    strokeWidth={layer.width}
                                    strokeDasharray={1}
                                    strokeDashoffset={drawn ? undefined : 1}
                                    style={{
                                        animationDelay: `${index * 140}ms`,
                                    }}
                                    className={cn(
                                        layer.tone,
                                        drawn && 'animate-route',
                                    )}
                                />
                            ))}

                        {/* `pathLength="1"` rescales the dash maths to a single
                            unit, so one keyframe draws a short hop and a long
                            haul alike — see the stylesheet. Until the section is
                            reached the offset holds the route fully retracted; a
                            map that arrives already finished has nothing left to
                            say. */}
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
                waiting at the origin of the coordinate system. */}
            {drawn &&
                destinations
                    .filter((destination) => destination.served)
                    .map((destination, index) => {
                        const travel = {
                            offsetPath: `path('${destination.route}')`,
                            animationDelay: `${1200 + index * 900}ms`,
                        };

                        return (
                            // Deux disques sur exactement le même parcours, le
                            // même retard et la même durée : un large et flou
                            // sous un petit et net. C'est ce qui fait un point
                            // de lumière plutôt qu'une puce qui glisse — et
                            // c'est fait de deux cercles, sans une image de
                            // plus à charger ni un filtre à recalculer, la
                            // teinte de la traîne étant simplement le bleu à
                            // une opacité où il ne se lit plus.
                            <g key={destination.code}>
                                <circle
                                    r={7}
                                    style={travel}
                                    className="route-parcel fill-primary/25"
                                />
                                <circle
                                    r={2.4}
                                    style={travel}
                                    className="route-parcel fill-primary"
                                />
                            </g>
                        );
                    })}
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
    const status = destination.served
        ? 'Livraison disponible'
        : 'Bientôt desservi';

    return (
        <li className="absolute" style={percent(destination.point)}>
            <button
                type="button"
                aria-label={[destination.name, status, destination.deliveryTime]
                    .filter(Boolean)
                    .join(' — ')}
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
 * Plain SVG with no mapping library above it: nothing here pans or zooms, so a
 * library offering those would only be bringing its own copy of a projection
 * along for the ride. The atlas ships with the page rather than being fetched
 * from a CDN — a landing page should not phone a third party before it can
 * finish drawing itself.
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

    /**
     * Nothing is labelled until it is asked for.
     *
     * A tooltip left open by default sits on top of the very country it names
     * and of the routes converging on it, so the map has to be read around its
     * own caption. The names are in text under the map regardless — this layer
     * answers "which one is that?", and only when the question is put.
     */
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
            const point = id ? ANCHORS[id] : undefined;

            return point
                ? [
                      {
                          ...destination,
                          id,
                          point,
                          route: arc(point, ROUTE_SIDE[destination.code]),
                      },
                  ]
                : [];
        });
    }, [countries, upcomingCountries]);

    const highlighted = destinations.find(
        (destination) => destination.code === active,
    );

    return (
        <div ref={ref} className={cn('relative select-none', className)}>
            {/* Sans observateur, le réseau se dessine d'emblée : mieux vaut
                une carte finie qu'une carte vide. */}
            <Network
                destinations={destinations}
                drawn={inView || !CAN_OBSERVE}
            />

            {/* The country under the cursor, redrawn on top rather than
                restyled in place: the layer below is memoised, and reaching
                into it would cost a rebuild of the entire basemap to brighten
                one country. */}
            <svg
                viewBox={VIEW_BOX}
                aria-hidden
                role="presentation"
                focusable="false"
                className="pointer-events-none absolute inset-0 block h-auto w-full"
            >
                {highlighted && (
                    <path
                        d={SHAPES[highlighted.id] ?? ''}
                        strokeWidth={1.5}
                        strokeLinejoin="round"
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
                    {/* Une lueur fixe sous les ondes, pour que le hub soit
                        aussi la source de lumière que l'atmosphère du fond
                        laisse entendre. Elle ne pulse pas : c'est le point qui
                        ne bouge jamais de cette carte. */}
                    <span className="absolute size-8 rounded-full bg-accent-brand/25 blur-md" />

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
                    className="absolute top-1/2 left-0 ml-4 hidden -translate-y-1/2 rounded-full border bg-card/85 px-3 py-1.5 font-display text-[11px] font-extrabold whitespace-nowrap shadow-md backdrop-blur-sm sm:inline-block"
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
                    <div className="min-w-44 animate-enter rounded-2xl border bg-popover/90 px-5 py-4 text-center shadow-xl backdrop-blur-md">
                        <p className="font-display text-sm font-extrabold whitespace-nowrap text-popover-foreground">
                            {highlighted.name}
                        </p>
                        <p
                            className={cn(
                                'mt-1 text-xs font-semibold whitespace-nowrap',
                                highlighted.served
                                    ? 'text-success'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {highlighted.served
                                ? 'Livraison disponible'
                                : 'Bientôt desservi'}
                        </p>

                        {highlighted.deliveryTime && (
                            <p className="mt-2.5 border-t pt-2.5 text-xs whitespace-nowrap text-muted-foreground">
                                Délai estimé
                                <span className="mt-0.5 block font-display text-sm font-extrabold text-popover-foreground">
                                    {highlighted.deliveryTime}
                                </span>
                            </p>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

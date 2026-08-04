import { useMemo } from 'react';

import atlas from '@/data/world-atlas.json';
import { ISO_NUMERIC, flagFor } from '@/lib/destinations';
import { cn } from '@/lib/utils';

/**
 * ── L'Afrique, colorée par ce qu'on y livre ─────────────────────────────────
 *
 * Ceci remplace la carte du monde animée. Ce qu'elle faisait bien, elle le
 * faisait pour un autre propos : elle montrait des trajets partant de France,
 * donc le monde, donc une Afrique large de trois centimètres au milieu d'un
 * océan. Or ce que le visiteur cherche sur cette page n'est pas d'où part le
 * colis — c'est si son pays à lui est desservi.
 *
 * D'où le cadrage : le continent, et rien d'autre que ce qui le borde. À cette
 * échelle un pays est une surface qu'on reconnaît, et la couleur peut enfin
 * dire quelque chose.
 *
 * ── Trois états, trois remplissages
 *
 *   — desservi : le bleu de la marque, plein. C'est la seule couleur saturée de
 *     la carte, et elle ne sert qu'à ça.
 *   — bientôt : le même bleu, très dilué. Assez pour se distinguer du sol,
 *     jamais assez pour se confondre avec un pays ouvert — la différence entre
 *     « on y va » et « on y est » est ce que cette section a de plus délicat à
 *     dire honnêtement.
 *   — le reste du continent : le gris du texte secondaire, à peine posé. Il
 *     n'est pas décoratif : sans lui, cinq pays flottent sans Afrique autour.
 *
 * Les terres hors d'Afrique qui entrent dans le cadre — l'Arabie, le sud de
 * l'Europe — sont encore un cran plus effacées. Elles ferment le dessin ; elles
 * ne sont pas le sujet.
 *
 * ── Pourquoi rien ne bouge et rien ne brille
 *
 * L'ancienne carte traçait ses routes en une seconde et demie. Une carte qui se
 * dessine est belle une fois ; celle-ci est consultée — on y cherche son pays,
 * et on y revient. Le halo qui faisait respirer les pays ouverts est parti pour
 * la même raison : c'est un dessin plat, et l'aplat suffit à dire lequel des
 * pays est ouvert.
 */

const { anchors: ANCHORS, shapes: SHAPES } = atlas as unknown as {
    view: [number, number, number, number];
    hub: [number, number];
    anchors: Record<string, [number, number]>;
    shapes: Record<string, string>;
};

/**
 * Le cadre, en unités de l'atlas : le continent plus une marge.
 *
 * Écrit plutôt que calculé sur les tracés. Un cadre déduit des formes présentes
 * changerait le jour où l'atlas gagne ou perd une île, et le cadrage d'une
 * carte est une décision de dessin, pas une conséquence de la donnée.
 */
const VIEW: [number, number, number, number] = [431, 88, 234, 319];

const VIEW_BOX = VIEW.join(' ');

/**
 * Les codes ISO numériques des pays d'Afrique.
 *
 * Cette liste est ce qui distingue le continent de ce qui l'entoure dans le
 * cadre. Elle est écrite ici parce que c'est une donnée stable — la composition
 * de l'Afrique ne bouge pas au rythme d'un site marchand — et parce que la
 * déduire d'une position dans le cadre rangerait le Yémen en Afrique.
 */
const AFRICA_IDS = new Set([
    '012',
    '024',
    '072',
    '108',
    '120',
    '132',
    '140',
    '148',
    '174',
    '178',
    '180',
    '204',
    '226',
    '231',
    '232',
    '262',
    '266',
    '270',
    '288',
    '324',
    '384',
    '404',
    '426',
    '430',
    '434',
    '450',
    '454',
    '466',
    '478',
    '480',
    '504',
    '508',
    '516',
    '562',
    '566',
    '624',
    '646',
    '678',
    '686',
    '690',
    '694',
    '706',
    '710',
    '716',
    '728',
    '729',
    '732',
    '748',
    '768',
    '788',
    '800',
    '818',
    '834',
    '854',
    '894',
]);

/** Si un tracé entre dans le cadre. Les autres ne sont jamais rendus. */
function inFrame(id: string): boolean {
    const anchor = ANCHORS[id];

    if (!anchor) {
        return false;
    }

    const [x, y] = anchor;

    return (
        x >= VIEW[0] - 30 &&
        x <= VIEW[0] + VIEW[2] + 30 &&
        y >= VIEW[1] - 30 &&
        y <= VIEW[1] + VIEW[3] + 30
    );
}

/**
 * Les deux fonds, assemblés une fois pour toutes.
 *
 * Un seul tracé par couche plutôt qu'un par pays : le sol se lit alors comme
 * une silhouette, dont les parties se distinguent par un filet de la couleur de
 * la page et non par cent frontières dessinées.
 */
const AFRICAN_LAND = Object.entries(SHAPES)
    .filter(([id]) => AFRICA_IDS.has(id) && inFrame(id))
    .map(([, path]) => path)
    .join('');

const SURROUNDING_LAND = Object.entries(SHAPES)
    .filter(([id]) => !AFRICA_IDS.has(id) && inFrame(id))
    .map(([, path]) => path)
    .join('');

export type Destination = {
    code: string;
    name: string;
    /** Null pour un pays dont personne n'a mesuré le délai. */
    deliveryTime?: string | null;
};

type Marked = Destination & {
    id: string;
    shape: string;
    /** La position de l'étiquette, en pourcentage du cadre. */
    left: number;
    top: number;
    served: boolean;
};

/**
 * Le décalage d'une étiquette, en points de pourcentage du cadre.
 *
 * Le Gabon et le Congo se touchent, et deux étiquettes centrées sur deux pays
 * voisins se recouvrent — la première disparaît sous la seconde. Où poser une
 * étiquette est une décision de dessin, comme le cadrage : elle est écrite
 * plutôt que calculée. Un pays absent de cette table reste centré sur lui-même.
 */
const LABEL_NUDGE: Record<string, [number, number]> = {
    GA: [-11, 2],
    CG: [5, 5],
};

/**
 * Le cadre, dissous plutôt que coupé — sur ses quatre bords.
 *
 * Il ne l'était que de haut en bas, et ça se voyait à droite : l'Arabie entre
 * dans le cadre et s'y arrêtait net, sur une verticale franche qui se lit comme
 * une capture d'écran d'une carte plus grande. Le dégradé horizontal la fait
 * disparaître au lieu de la trancher.
 *
 * Deux calques de masque, donc, et `mask-composite: intersect` : par défaut les
 * calques s'additionnent, et l'union de deux dégradés est opaque partout où
 * l'un des deux l'est — ce qui revient à n'avoir aucun masque. C'est leur
 * intersection qu'on veut, celle qui n'est opaque qu'au milieu des deux.
 *
 * Le bord droit est plus large que les autres : c'est le seul où quelque chose
 * d'important — le golfe d'Aden, la corne — approche de la coupe.
 */
const EDGE_MASK = [
    'linear-gradient(to bottom, transparent 0%, black 6%, black 95%, transparent 100%)',
    'linear-gradient(to right, transparent 0%, black 5%, black 88%, transparent 100%)',
].join(', ');

export function AfricaMap({
    countries,
    upcomingCountries = [],
    className,
}: {
    countries: Destination[];
    upcomingCountries?: Destination[];
    className?: string;
}) {
    const marked = useMemo<Marked[]>(() => {
        const listed = [
            ...countries.map((country) => ({ ...country, served: true })),
            ...upcomingCountries.map((country) => ({
                ...country,
                served: false,
            })),
        ];

        return listed.flatMap((country) => {
            const id = ISO_NUMERIC[country.code];
            const anchor = id ? ANCHORS[id] : undefined;
            const shape = id ? SHAPES[id] : undefined;

            if (!id || !anchor || !shape) {
                return [];
            }

            const [nudgeX, nudgeY] = LABEL_NUDGE[country.code] ?? [0, 0];

            return [
                {
                    ...country,
                    id,
                    shape,
                    left: ((anchor[0] - VIEW[0]) / VIEW[2]) * 100 + nudgeX,
                    top: ((anchor[1] - VIEW[1]) / VIEW[3]) * 100 + nudgeY,
                    served: country.served,
                },
            ];
        });
    }, [countries, upcomingCountries]);

    return (
        <div className={cn('relative isolate', className)}>
            {/* La proportion vient du cadre lui-même : les étiquettes sont du
                HTML posé en pourcentages, et elles ne retombent sur le bon pays
                que si la boîte et le `viewBox` ont exactement la même forme. */}
            <div
                className="relative w-full"
                style={{ aspectRatio: `${VIEW[2]} / ${VIEW[3]}` }}
            >
                <svg
                    viewBox={VIEW_BOX}
                    className="absolute inset-0 size-full"
                    role="img"
                    aria-label={`Carte de l'Afrique : ${countries
                        .map((country) => country.name)
                        .join(', ')} desservis${
                        upcomingCountries.length > 0
                            ? `, ${upcomingCountries
                                  .map((country) => country.name)
                                  .join(', ')} bientôt`
                            : ''
                    }.`}
                    style={{
                        maskImage: EDGE_MASK,
                        maskComposite: 'intersect',
                        WebkitMaskImage: EDGE_MASK,
                        // La propriété préfixée n'accepte pas les mêmes mots :
                        // `source-in` y est ce que `intersect` est à l'autre.
                        WebkitMaskComposite: 'source-in',
                    }}
                >
                    {/* Ce qui borde, effacé. */}
                    <path
                        d={SURROUNDING_LAND}
                        className="fill-muted-foreground/10"
                    />

                    {/* Le continent. */}
                    <path
                        d={AFRICAN_LAND}
                        className="fill-muted-foreground/20 stroke-background"
                        strokeWidth={0.6}
                    />

                    {marked.map((country) => (
                        <g key={country.code}>
                            <path
                                d={country.shape}
                                className={cn(
                                    'stroke-background',
                                    country.served
                                        ? 'fill-primary'
                                        : 'fill-primary/35',
                                )}
                                strokeWidth={0.6}
                            />
                        </g>
                    ))}
                </svg>

                {/* Les étiquettes, en HTML : dans le SVG elles suivraient
                    l'échelle du cadre et deviendraient illisibles sur un
                    téléphone. */}
                {marked.map((country) => (
                    <div
                        key={country.code}
                        className="absolute -translate-x-1/2 -translate-y-1/2"
                        style={{
                            left: `${country.left}%`,
                            top: `${country.top}%`,
                        }}
                    >
                        <span
                            className={cn(
                                'flex items-center gap-1.5 rounded-full border py-1 pr-2.5 pl-2 text-xs font-semibold whitespace-nowrap shadow-sm',
                                country.served
                                    ? 'panel-tile'
                                    : 'border-dashed bg-card/80 text-muted-foreground',
                            )}
                        >
                            <span aria-hidden className="leading-none">
                                {flagFor(country.code)}
                            </span>
                            {country.name}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}

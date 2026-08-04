import {
    Check,
    ChevronLeft,
    ChevronRight,
    Link as LinkIcon,
    MapPin,
    Truck,
} from 'lucide-react';
import { useRef, useState } from 'react';
import type { ComponentProps, ReactNode } from 'react';

import {
    MARKETPLACE_COLORS,
    MARKETPLACE_LOGOS,
} from '@/components/marketplace-logos';
import jacket from '@/images/products/veste-matelassee.webp';
import { cn } from '@/lib/utils';

/**
 * ── Du lien au colis, comme un parcours que l'on peut prendre en main ───────
 *
 * C'était quatre cartes alignées qui s'allumaient l'une après l'autre en
 * boucle. Le problème n'était pas l'animation, il était la forme : quatre
 * colonnes de la largeur d'un quart d'écran, quatre récits tassés, et rien à
 * faire sinon regarder. On lisait la première, la quatrième était déjà passée.
 *
 * C'est maintenant une seule scène, large, avec une barre d'étapes au-dessus.
 * Trois choses en découlent, et ce sont les trois raisons du changement :
 *
 *   — chaque étape a la place de se montrer pour de bon. L'étape « Commande »
 *     ne dit plus « devis clair », elle montre le devis, ligne par ligne, avec
 *     le transport séparé du produit. C'est l'engagement central du service, et
 *     il tenait dans une vignette de la taille d'un timbre.
 *   — ça défile tout seul, et on peut quand même s'en mêler. Le survol suspend,
 *     un clic va où l'on veut, les flèches aussi. Rien ne s'arrête jamais
 *     définitivement : une démonstration qui se fige au premier clic demande
 *     ensuite un bouton pour la relancer, et c'est un réglage de plus dans un
 *     bloc qui n'a rien à régler.
 *   — c'est deux fois moins haut. Le hero avait besoin de cet air-là.
 *
 * La jauge sous les onglets est à la fois ce qui montre le chemin restant
 * jusqu'à la dernière étape et l'horloge qui les fait défiler. C'est le point de
 * conception de ce fichier, et il est expliqué là où elle est rendue.
 *
 * Les chiffres sont des exemples et le disent : la légende « Exemple de
 * parcours » est visible, pas seulement annoncée aux lecteurs d'écran. Le bloc
 * n'est plus caché à ces derniers — il l'était quand il n'y avait rien à y
 * faire ; maintenant qu'il y a des boutons, le cacher reviendrait à poser un
 * piège au clavier.
 */

/**
 * Combien de temps une étape reste à l'écran avant que la suivante prenne.
 *
 * Descendue de 5,2 à 3,4 secondes : à l'ancienne cadence le cycle complet
 * durait vingt et une secondes, ce que personne n'attend sur une page
 * d'accueil, et une étape déjà lue restait trop longtemps sous les yeux. Le
 * parcours entier tient maintenant sous quatorze secondes, et chaque scène a
 * encore de quoi être lue — le devis, qui est la plus dense, fait trois lignes.
 */
const DWELL = 3400;

const PROGRESS = 70;

/**
 * Le panneau de la scène : le fond en retrait de la carte, sur lequel se pose
 * l'artefact de l'étape.
 *
 * `muted` et non `background` : sur le thème clair, la page et les cartes sont
 * toutes deux du blanc pur, et un panneau `bg-background` posé sur une carte
 * blanche n'existait que par sa bordure. Le crème très pâle du `muted` le
 * creuse pour de bon, et en thème sombre il l'éclaircit — ce qui est la
 * convention inverse et la bonne dans les deux cas.
 */
function Stage({ children, className }: ComponentProps<'div'>) {
    return (
        <div
            className={cn(
                // `min-w-0` parce que le panneau est une case de grille, et
                // qu'une case de grille prend par défaut la largeur minimale de
                // ce qu'elle contient. L'étape « Lien » contient une URL en
                // `truncate`, donc en `white-space: nowrap` : sa largeur
                // minimale est la chaîne entière, 235 px. La case gonflait donc
                // à 347 px dans une carte qui en fait 308 sur un téléphone, et
                // comme les quatre scènes partagent la même case, les trois
                // autres — le devis en tête — débordaient avec elle.
                //
                // Remis à zéro, la case suit la carte et l'URL retrouve son
                // rôle : c'est elle qui se coupe, ce qu'elle demandait déjà.
                'min-w-0 rounded-3xl border bg-muted/60 p-6 sm:p-7',
                className,
            )}
        >
            {children}
        </div>
    );
}

/** Une ligne du devis. Le total est la seule qui pèse. */
function QuoteLine({
    label,
    amount,
    total,
}: {
    label: string;
    amount: string;
    total?: boolean;
}) {
    return (
        <div
            className={cn(
                'flex items-baseline justify-between gap-4',
                total && 'border-t pt-3',
            )}
        >
            <span
                className={cn(
                    'text-sm',
                    total
                        ? 'font-display font-extrabold'
                        : 'text-muted-foreground',
                )}
            >
                {label}
            </span>
            <span
                className={cn(
                    'tabular-nums',
                    total
                        ? 'font-display text-lg font-black'
                        : 'text-sm font-medium',
                )}
            >
                {amount}
                <span className="ml-1 text-xs font-bold text-muted-foreground">
                    XAF
                </span>
            </span>
        </div>
    );
}

type StageDefinition = {
    label: string;
    title: string;
    caption: string;
    confirmation: string;
    visual: ReactNode;
};

const STAGES: StageDefinition[] = [
    {
        label: 'Lien',
        title: 'Vous collez le lien',
        caption:
            "L'assistant reconnaît la plateforme et retrouve le produit. Vous n'avez rien d'autre à saisir.",
        confirmation: 'Produit détecté',
        visual: (
            <Stage>
                {/* Le champ tel qu'il est en haut de page, pour que la
                    démonstration commence exactement là où le visiteur est. */}
                <div className="flex items-center gap-3 rounded-2xl border bg-card px-4 py-3.5">
                    <LinkIcon className="size-4 shrink-0 text-muted-foreground" />
                    <p className="truncate font-mono text-sm text-muted-foreground">
                        temu.com/women-jacket-p-8842
                    </p>
                </div>

                <div className="mt-5 flex items-center gap-3">
                    {/* La marque dans sa couleur, sur blanc : même règle que
                        les tuiles du bandeau. */}
                    <span
                        className="flex h-11 shrink-0 items-center rounded-xl border border-black/[0.07] bg-surface-tile px-3.5"
                        style={{ color: MARKETPLACE_COLORS.Temu }}
                    >
                        {MARKETPLACE_LOGOS.Temu}
                    </span>

                    <p className="text-sm text-muted-foreground">
                        Plateforme reconnue automatiquement
                    </p>
                </div>
            </Stage>
        ),
    },
    {
        label: 'Détails',
        title: 'Trois questions, pas un formulaire',
        caption:
            'La couleur, la taille, la quantité. Vous répondez en un mot, ou vous choisissez dans la liste proposée.',
        confirmation: 'Détails enregistrés',
        visual: (
            <Stage className="space-y-4">
                {[
                    { question: 'Quelle couleur ?', answer: 'Noir' },
                    { question: 'Quelle taille ?', answer: 'XL' },
                    { question: 'Combien ?', answer: '×1' },
                ].map((exchange) => (
                    <div
                        key={exchange.question}
                        className="flex items-center justify-between gap-4"
                    >
                        <p className="rounded-2xl rounded-tl-md border bg-card px-4 py-2 text-sm">
                            {exchange.question}
                        </p>
                        <p className="rounded-2xl rounded-tr-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">
                            {exchange.answer}
                        </p>
                    </div>
                ))}
            </Stage>
        ),
    },
    {
        label: 'Devis',
        title: 'Le prix, détaillé avant de payer',
        caption:
            'Le produit et le transport au poids réel, séparés. Vous validez, ou vous ne validez pas — rien n’est acheté avant.',
        confirmation: 'Devis envoyé pour validation',
        visual: (
            <Stage>
                <div className="flex items-center gap-4">
                    <img
                        src={jacket}
                        alt=""
                        width={56}
                        height={56}
                        loading="lazy"
                        decoding="async"
                        className="size-14 shrink-0 rounded-xl border object-cover"
                    />

                    <div className="min-w-0">
                        <p className="truncate font-display font-extrabold">
                            Veste matelassée
                        </p>
                        <p className="mt-0.5 truncate text-sm text-muted-foreground">
                            Noir · XL · ×1
                        </p>
                    </div>
                </div>

                <div className="mt-6 space-y-3">
                    <QuoteLine label="Produit" amount="19 500" />
                    <QuoteLine label="Transport (0,9 kg)" amount="5 000" />
                    <QuoteLine label="Total" amount="24 500" total />
                </div>
            </Stage>
        ),
    },
    {
        label: 'Livraison',
        title: 'Votre colis, suivi par sa référence',
        caption:
            'Une référence suffit pour savoir où il en est, à tout moment, sans compte ni mot de passe.',
        confirmation: 'En route vers Douala',
        visual: (
            <Stage>
                <div className="flex items-center justify-between gap-4">
                    <p className="flex items-center gap-2 font-display font-extrabold">
                        <MapPin className="size-4 shrink-0 text-primary" />
                        Douala
                    </p>
                    <p className="rounded-full bg-accent-brand px-3 py-1 font-mono text-xs font-bold text-accent-brand-foreground">
                        SHP-2608-4KJ9X2
                    </p>
                </div>

                <div className="mt-6 flex items-center gap-3">
                    <Truck className="size-5 shrink-0 animate-shuttle text-primary" />

                    {/* La piste est en `border` et non en `muted` : le panneau
                        qui la porte est déjà `muted`, et une piste de la même
                        teinte que son fond n'est pas une piste. */}
                    <div className="relative h-2 flex-1 rounded-full bg-border">
                        <div
                            className="h-full rounded-full bg-primary"
                            style={{ width: `${PROGRESS}%` }}
                        />
                        {/* La tête du remplissage, marquée : une barre qui
                            s'arrête net se lit comme inachevée, une barre avec
                            une tête se lit comme en train de voyager. */}
                        <span
                            className="absolute top-1/2 size-4 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-primary bg-background"
                            style={{ left: `${PROGRESS}%` }}
                        />
                    </div>

                    <span className="shrink-0 font-display text-sm font-black tabular-nums">
                        {PROGRESS} %
                    </span>
                </div>

                <p className="mt-5 text-sm text-muted-foreground">
                    Expédié le 12 juillet · arrivée estimée sous 5 jours
                </p>
            </Stage>
        ),
    },
];

export function LinkToParcel({ className, ...props }: ComponentProps<'div'>) {
    const [active, setActive] = useState(0);

    /**
     * Le survol, et rien d'autre.
     *
     * La boucle ne s'arrête plus jamais pour de bon. C'était le cas avant, avec
     * un bouton pour la relancer ; les deux sont partis ensemble, et c'est le
     * bon échange — un parcours qui s'arrête définitivement au premier clic
     * oblige à en fournir la commande inverse, et cette commande était un
     * réglage de plus à comprendre pour un bloc qui n'a rien à régler. Un clic
     * va simplement à l'étape demandée, et le compte à rebours repart de là.
     *
     * Le survol suspend : on regarde une étape de plus près, on n'a rien
     * demandé, et l'avance reprend là où elle en était quand le curseur s'en va.
     */
    const [hovered, setHovered] = useState(false);

    const tabs = useRef<(HTMLButtonElement | null)[]>([]);

    /** L'étape suivante. Appelée par la jauge, qui est l'horloge — voir plus bas. */
    const advance = () => setActive((current) => (current + 1) % STAGES.length);

    /** Aller à une étape. Le compte à rebours repart de zéro sur celle-là. */
    const select = (index: number) => setActive(index);

    const onKeyDown = (event: React.KeyboardEvent) => {
        const step =
            event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0;

        if (step === 0) {
            return;
        }

        event.preventDefault();

        const next = (active + step + STAGES.length) % STAGES.length;

        select(next);
        tabs.current[next]?.focus();
    };

    return (
        <div
            {...props}
            onMouseEnter={() => setHovered(true)}
            onMouseLeave={() => setHovered(false)}
            className={cn(
                'rounded-4xl border bg-card p-5 shadow-xl shadow-foreground/[0.06] sm:p-8 lg:p-10',
                className,
            )}
        >
            {/* Visible, et pas seulement annoncé : tout ce qui suit est un
                exemple, et un devis qui a l'air vrai sur une page de vente est
                la seule chose que ce site ne peut pas se permettre. */}
            <p className="text-xs text-muted-foreground">Exemple de parcours</p>

            {/*
             * Les quatre libellés côte à côte à partir de `sm` ; sur un
             * téléphone, celui de l'étape en cours et lui seul.
             *
             * En 2×2, les quatre tenaient à l'écran en même temps que la scène
             * qu'ils annoncent : on lisait « Livraison » pendant que la scène
             * montrait le lien, et la barre cessait de dire où l'on en était
             * pour devenir un sommaire. Un seul libellé à la fois redit ce que
             * la scène est en train de montrer.
             *
             * Rien n'est perdu au passage : la jauge juste en dessous garde ses
             * quatre segments, donc « troisième étape sur quatre » reste lisible
             * d'un coup d'œil, sans les libellés.
             *
             * Les quatre restent dans le document, empilés dans la même case, et
             * les inactifs sortent par `visibility` — ce qui les retire aussi du
             * clavier et des lecteurs d'écran, plutôt que de laisser trois
             * boutons invisibles sous le doigt. C'est le procédé qu'emploient
             * déjà les scènes plus bas.
             */}
            <div className="mt-7 flex items-center gap-4">
                <ol
                    role="tablist"
                    aria-label="Les étapes d'une commande"
                    onKeyDown={onKeyDown}
                    className="grid min-w-0 flex-1 grid-cols-1 gap-x-4 sm:grid-cols-4 sm:gap-x-6"
                >
                    {STAGES.map((item, index) => {
                        const done = index < active;
                        const current = index === active;

                        return (
                            <li
                                key={item.label}
                                className={cn(
                                    'col-start-1 row-start-1 transition-opacity duration-500 ease-out motion-reduce:transition-none sm:visible sm:col-auto sm:row-auto sm:opacity-100',
                                    current
                                        ? 'opacity-100'
                                        : 'invisible opacity-0',
                                )}
                            >
                                <button
                                    type="button"
                                    role="tab"
                                    id={`stage-tab-${index}`}
                                    aria-selected={current}
                                    aria-controls={`stage-panel-${index}`}
                                    tabIndex={current ? 0 : -1}
                                    ref={(node) => {
                                        tabs.current[index] = node;
                                    }}
                                    onClick={() => select(index)}
                                    className="w-full cursor-pointer text-left focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                                >
                                    <span className="flex items-center gap-2.5">
                                        <span
                                            className={cn(
                                                'flex size-6 shrink-0 items-center justify-center rounded-full text-[11px] font-black tabular-nums transition-colors',
                                                done || current
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-muted text-muted-foreground',
                                            )}
                                        >
                                            {done ? (
                                                <Check className="size-3.5" />
                                            ) : (
                                                index + 1
                                            )}
                                        </span>

                                        <span
                                            className={cn(
                                                'truncate font-display text-eyebrow font-extrabold uppercase transition-colors',
                                                current
                                                    ? 'text-foreground'
                                                    : 'text-muted-foreground',
                                            )}
                                        >
                                            {item.label}
                                        </span>
                                    </span>
                                </button>
                            </li>
                        );
                    })}
                </ol>

                {/* ── Les flèches, sur téléphone seulement ────────────────────
                    À partir de `sm`, les quatre onglets sont là et cliquer
                    l'étape voulue est plus direct que d'y aller pas à pas. En
                    dessous, un seul libellé est visible à la fois : sans ces
                    deux boutons, le seul moyen de bouger était d'attendre la
                    jauge — un carrousel qu'on ne peut pas feuilleter n'est pas
                    un carrousel, c'est une publicité. */}
                <div className="flex shrink-0 gap-2 sm:hidden">
                    <button
                        type="button"
                        aria-label="Étape précédente"
                        onClick={() =>
                            select((active - 1 + STAGES.length) % STAGES.length)
                        }
                        className="flex size-8 items-center justify-center rounded-full border text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        <ChevronLeft className="size-4" />
                    </button>
                    <button
                        type="button"
                        aria-label="Étape suivante"
                        onClick={() => select((active + 1) % STAGES.length)}
                        className="flex size-8 items-center justify-center rounded-full border text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        <ChevronRight className="size-4" />
                    </button>
                </div>
            </div>

            {/*
             * ── La jauge, d'un bout à l'autre du parcours ────────────────────
             *
             * Une seule piste sur toute la largeur, découpée en quatre. C'est
             * le remplacement des quatre barres qui vivaient chacune sous son
             * libellé : elles disaient le temps restant de l'étape en cours, et
             * rien du chemin qu'il restait à faire. Quatre jauges qui se
             * remplissent l'une après l'autre ne se lisent pas comme une
             * progression ; une seule qui avance de gauche à droite, si.
             *
             * C'est aussi l'horloge du composant. La fin de l'animation du
             * segment en cours est ce qui déclenche l'étape suivante — pas un
             * `setTimeout` en parallèle. Deux horloges pour une même durée
             * finissent toujours par diverger : il suffit d'en suspendre une —
             * ce que fait le survol — pour que la jauge affiche un reste de
             * temps que le minuteur ne respecte pas. Ici, la jauge *est* le
             * minuteur, et trois choses en découlent gratuitement :
             *
             *   — le survol suspend réellement. `animation-play-state: paused`
             *     gèle le segment là où il en est, et l'événement de fin arrive
             *     d'autant plus tard. Rien à mesurer, rien à mémoriser.
             *   — sous `prefers-reduced-motion`, la feuille de style annule
             *     `animate-dwell` : aucune animation, donc aucun événement de
             *     fin, donc aucune avance automatique. Le réglage est respecté
             *     sans qu'une ligne ici n'interroge `matchMedia`, et sans le
             *     risque d'hydratation qu'une requête de média lue au premier
             *     rendu ferait courir.
             *   — un onglet en arrière-plan, dont le navigateur ralentit les
             *     animations, ne fait pas défiler le parcours dans le vide.
             *
             * Muette pour les lecteurs d'écran : elle ne dit que ce que l'onglet
             * sélectionné dit déjà, et le ferait en continu.
             */}
            <div aria-hidden className="mt-7 flex gap-1.5">
                {STAGES.map((item, index) => (
                    <span
                        key={item.label}
                        className="h-1.5 flex-1 overflow-hidden rounded-full bg-border"
                    >
                        <span
                            // Remontée par sa clé à chaque changement d'étape :
                            // React remplace l'élément au lieu de le mettre à
                            // jour, ce qui rejoue l'animation sans qu'aucun état
                            // ne la pilote.
                            key={active}
                            onAnimationEnd={
                                index === active ? advance : undefined
                            }
                            style={
                                index === active
                                    ? { animationDuration: `${DWELL}ms` }
                                    : undefined
                            }
                            className={cn(
                                'block h-full w-full origin-left rounded-full bg-primary',
                                index === active
                                    ? cn(
                                          'animate-dwell',
                                          hovered &&
                                              '[animation-play-state:paused]',
                                      )
                                    : index < active
                                      ? 'scale-x-100'
                                      : 'scale-x-0',
                            )}
                        />
                    </span>
                ))}
            </div>

            {/*
             * ── Les quatre scènes, empilées ─────────────────────────────────
             *
             * Une seule était montée à la fois, et la page sautait à chaque
             * changement : le devis fait trois lignes de plus que la détection
             * du lien, la carte se contractait puis se rouvrait, et tout ce qui
             * suit dans la page remontait de quelques dizaines de pixels avant
             * de redescendre.
             *
             * Une hauteur fixe aurait marché, mais il aurait fallu la deviner —
             * et la redeviner à chaque changement de texte, de palier
             * d'affichage ou de taille de police choisie par le visiteur.
             *
             * Les quatre occupent donc la même cellule de grille, `col-start-1
             * row-start-1`. La grille se dimensionne sur la plus haute, une fois
             * pour toutes, et le bloc ne bouge plus jamais — quel que soit
             * l'écran, et sans qu'aucun nombre ne soit écrit nulle part.
             *
             * D'où `invisible` plutôt que `hidden` : `visibility: hidden` retire
             * l'élément du parcours au clavier et de ce que lit un lecteur
             * d'écran, mais lui laisse sa place. `display: none` la lui
             * reprendrait, et la grille se remettrait à mesurer la seule scène
             * visible — c'est-à-dire exactement le problème de départ.
             *
             * L'entrée est un glissement, pas seulement un fondu : la scène qui
             * vient se tient quelques pixels du côté d'où elle arrive — à
             * droite quand on avance, à gauche quand on revient — et se pose en
             * place pendant que la sortante s'efface vers l'autre bord. C'est le
             * mouvement que la barre d'étapes promet : un parcours qui va de
             * gauche à droite. Le déplacement reste petit, la règle de la
             * maison n'a pas changé — un concierge ne fait pas de spectacle.
             * Sous `prefers-reduced-motion`, la transition entière est annulée,
             * translation comprise.
             */}
            <div className="mt-9 grid">
                {STAGES.map((item, index) => (
                    <div
                        key={item.label}
                        role="tabpanel"
                        id={`stage-panel-${index}`}
                        aria-labelledby={`stage-tab-${index}`}
                        className={cn(
                            // `min-w-0` pour la même raison qu'au niveau du
                            // dessous, sur `Stage` : les quatre scènes sont
                            // empilées dans une seule case de grille, et sans
                            // ça cette case se dimensionne sur la plus large
                            // des quatre plutôt que sur la carte.
                            'col-start-1 row-start-1 grid min-w-0 gap-8 transition-[opacity,visibility,transform] duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] motion-reduce:transform-none motion-reduce:transition-none lg:grid-cols-2 lg:items-center lg:gap-14',
                            index === active
                                ? 'visible translate-x-0 opacity-100'
                                : index < active
                                  ? 'invisible -translate-x-6 opacity-0'
                                  : 'invisible translate-x-6 opacity-0',
                        )}
                    >
                        <div>
                            <p className="font-display text-subtitle font-extrabold">
                                {item.title}
                            </p>

                            <p className="mt-4 max-w-md text-body text-muted-foreground">
                                {item.caption}
                            </p>

                            <p className="mt-7 inline-flex items-center gap-2 rounded-full bg-success/10 px-4 py-2 text-sm font-semibold text-success">
                                <Check className="size-4 shrink-0" />
                                {item.confirmation}
                            </p>
                        </div>

                        {item.visual}
                    </div>
                ))}
            </div>
        </div>
    );
}

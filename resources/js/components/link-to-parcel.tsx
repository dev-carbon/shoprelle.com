import { Check, Link as LinkIcon, MapPin, Truck } from 'lucide-react';
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
 *   — on peut s'arrêter. La barre avance seule, mais le survol la suspend et un
 *     clic va où l'on veut. Une démonstration qu'on ne peut pas retenir est une
 *     démonstration qu'on ne relit pas.
 *   — c'est deux fois moins haut. Le hero avait besoin de cet air-là.
 *
 * Les chiffres sont des exemples et le disent : la légende « Exemple de
 * parcours » est visible, pas seulement annoncée aux lecteurs d'écran. Le bloc
 * n'est plus caché à ces derniers — il l'était quand il n'y avait rien à y
 * faire ; maintenant qu'il y a des boutons, le cacher reviendrait à poser un
 * piège au clavier.
 */

/** Combien de temps une étape reste à l'écran avant que la suivante prenne. */
const DWELL = 5200;

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
                'rounded-3xl border bg-muted/60 p-6 sm:p-7',
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
     * Deux façons d'arrêter la boucle, et elles ne sont pas la même.
     *
     * Le survol suspend : on regarde une étape de plus près, on n'a rien
     * demandé, et l'avance reprend là où elle en était quand le curseur s'en
     * va. Le clic, lui, arrête pour de bon — quelqu'un qui vient de choisir une
     * étape ne veut pas qu'on la lui reprenne cinq secondes plus tard, et c'est
     * exactement le reproche que l'on fait aux carrousels.
     */
    const [hovered, setHovered] = useState(false);
    const [takenOver, setTakenOver] = useState(false);

    const tabs = useRef<(HTMLButtonElement | null)[]>([]);

    /**
     * ── Une seule horloge ───────────────────────────────────────────────────
     *
     * L'étape suivante est déclenchée par la fin de l'animation de la barre, et
     * non par un `setTimeout` en parallèle. Deux horloges pour une même durée
     * finissent toujours par diverger : il suffit de suspendre l'une — ce que
     * fait le survol — pour que la barre affiche un reste de temps que le
     * minuteur ne respecte pas. Ici, la barre *est* le minuteur.
     *
     * Trois conséquences, et les trois sont gratuites :
     *
     *   — le survol suspend réellement. `animation-play-state: paused` gèle la
     *     barre là où elle en est, et l'événement de fin arrive d'autant plus
     *     tard. Rien à mesurer, rien à mémoriser.
     *   — sous `prefers-reduced-motion`, la feuille de style annule
     *     `animate-dwell` : aucune animation, donc aucun événement de fin, donc
     *     aucune avance automatique. Le réglage est respecté sans qu'une seule
     *     ligne ici n'interroge `matchMedia` — et sans le risque d'hydratation
     *     que poserait une requête de média lue au premier rendu.
     *   — un onglet en arrière-plan, dont le navigateur ralentit les
     *     animations, ne fait pas défiler le parcours dans le vide.
     */
    const advance = () => setActive((current) => (current + 1) % STAGES.length);

    /** Prendre la main : on va où l'on demande, et la boucle s'arrête. */
    const select = (index: number) => {
        setTakenOver(true);
        setActive(index);
    };

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

    const stage = STAGES[active];

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
            <div className="flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
                {/* Visible, et pas seulement annoncé : tout ce qui suit est un
                    exemple, et un devis qui a l'air vrai sur une page de vente
                    est la seule chose que ce site ne peut pas se permettre. */}
                <p className="text-xs text-muted-foreground">
                    Exemple de parcours
                </p>

                {/*
                 * ── Le voyant ───────────────────────────────────────────────
                 *
                 * Deux choses en une, et les deux manquaient.
                 *
                 * Il dit que ça défile tout seul. Le parcours avançait déjà de
                 * lui-même, mais rien ne l'annonçait : on tombait sur quatre
                 * onglets, on croyait devoir cliquer, et l'avance suivante
                 * ressemblait alors à une page qui bouge sans qu'on l'ait
                 * demandé. Un point qui bat et deux mots règlent ça avant que la
                 * question se pose.
                 *
                 * Et il rend la main. Un clic sur une étape arrête la boucle
                 * pour de bon — c'est ce qu'il faut — mais il n'existait aucun
                 * moyen de la relancer, ce qui fait d'un arrêt volontaire un
                 * cul-de-sac. Ce bouton est ce moyen.
                 */}
                <button
                    type="button"
                    onClick={() => setTakenOver((stopped) => !stopped)}
                    aria-pressed={!takenOver}
                    className="flex cursor-pointer items-center gap-2.5 rounded-full border px-3.5 py-2 text-xs font-semibold transition-colors hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                >
                    <span className="relative flex size-2.5 shrink-0">
                        {/* Le battement suit ce qui se passe vraiment, donc il
                            s'arrête aussi au survol — le curseur sur le bloc
                            suspend la barre, et un voyant qui continuerait de
                            clignoter pendant ce temps mentirait. Le libellé, lui,
                            ne bouge qu'au clic : un mot qui change dès qu'on
                            approche la souris est un mot qu'on n'arrive pas à
                            lire. */}
                        {!takenOver && !hovered && (
                            <span
                                aria-hidden
                                className="absolute inline-flex size-full animate-ping rounded-full bg-primary/70"
                            />
                        )}
                        <span
                            className={cn(
                                'relative size-2.5 rounded-full',
                                takenOver
                                    ? 'bg-muted-foreground/40'
                                    : 'bg-primary',
                            )}
                        />
                    </span>

                    {takenOver ? 'Défilement en pause' : 'Défilement auto'}
                </button>
            </div>

            <ol
                role="tablist"
                aria-label="Les étapes d'une commande"
                onKeyDown={onKeyDown}
                className="mt-7 grid grid-cols-2 gap-x-4 gap-y-6 sm:grid-cols-4 sm:gap-x-6"
            >
                {STAGES.map((item, index) => {
                    const done = index < active;
                    const current = index === active;

                    return (
                        <li key={item.label}>
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

                                {/* La barre de l'étape. Celle en cours se
                                    remplit en temps réel sur la durée
                                    d'affichage, ce qui dit combien de temps il
                                    reste sans qu'aucun chiffre soit écrit. */}
                                <span className="mt-3 block h-1.5 overflow-hidden rounded-full bg-border">
                                    <span
                                        // Remontée par sa clé à chaque
                                        // changement d'étape : React remplace
                                        // l'élément au lieu de le mettre à
                                        // jour, ce qui rejoue l'animation sans
                                        // qu'aucun état ne la pilote.
                                        key={active}
                                        onAnimationEnd={advance}
                                        style={
                                            current && !takenOver
                                                ? {
                                                      animationDuration: `${DWELL}ms`,
                                                  }
                                                : undefined
                                        }
                                        className={cn(
                                            'block h-full w-full origin-left rounded-full bg-primary',
                                            current && !takenOver
                                                ? cn(
                                                      'animate-dwell',
                                                      hovered &&
                                                          '[animation-play-state:paused]',
                                                  )
                                                : done || current
                                                  ? 'scale-x-100'
                                                  : 'scale-x-0',
                                        )}
                                    />
                                </span>
                            </button>
                        </li>
                    );
                })}
            </ol>

            {/* La scène. Elle aussi remontée par sa clé, pour que chaque étape
                entre au lieu de se substituer à la précédente sans transition. */}
            <div
                key={active}
                role="tabpanel"
                id={`stage-panel-${active}`}
                aria-labelledby={`stage-tab-${active}`}
                className="mt-9 grid animate-rise gap-8 lg:grid-cols-2 lg:items-center lg:gap-14"
            >
                <div>
                    <p className="font-display text-subtitle font-extrabold">
                        {stage.title}
                    </p>

                    <p className="mt-4 max-w-md text-body text-muted-foreground">
                        {stage.caption}
                    </p>

                    <p className="mt-7 inline-flex items-center gap-2 rounded-full bg-success/10 px-4 py-2 text-sm font-semibold text-success">
                        <Check className="size-4 shrink-0" />
                        {stage.confirmation}
                    </p>
                </div>

                {stage.visual}
            </div>
        </div>
    );
}

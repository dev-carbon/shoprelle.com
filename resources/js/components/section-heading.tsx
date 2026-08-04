import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

/**
 * ── Les deux pièces d'un titre de section ───────────────────────────────────
 *
 * Elles vivaient dans la page d'accueil, qui était le seul endroit à en avoir.
 * Elles sont ici parce que la même direction artistique tient maintenant sur
 * les pages publiques — l'assistant, « Mes demandes » — et que deux copies d'une
 * règle de marque finissent toujours par diverger.
 *
 * Le ton se choisit à l'usage et alterne d'une section à la suivante : les deux
 * couleurs de la marque traversent la page sans qu'aucune section n'ait à
 * changer de fond pour ça. Le sur-titre prend le ton du mot mis en avant dans
 * son propre titre — sinon la même annonce se ferait en deux couleurs.
 */
export type AccentTone = 'blue' | 'gold';

/**
 * Le sur-titre d'une section : un filet, puis deux mots en capitales.
 *
 * Il ne dit rien que le titre ne dise déjà — il annonce. C'est ce qui donne à
 * un titre de section un point de départ visuel, et ce qui fait qu'une page
 * très aérée reste lisible comme une suite de chapitres plutôt que comme une
 * série de blocs flottants.
 *
 * Sans `tone`, c'est la couleur primaire, qui est redéclarée par chaque bande
 * colorée : blanc sur le bleu, marine sur l'or. C'est ce qui vaut pour les
 * sections dont le titre ne porte aucun mot en avant.
 */
export function Eyebrow({
    tone = 'blue',
    children,
}: {
    tone?: AccentTone;
    children: ReactNode;
}) {
    return (
        <p
            className={cn(
                'flex items-center gap-3 font-display text-eyebrow font-extrabold uppercase',
                tone === 'blue' ? 'text-primary' : 'text-accent-brand-ink',
            )}
        >
            <span
                aria-hidden
                className={cn(
                    'h-px w-7',
                    tone === 'blue' ? 'bg-primary/40' : 'bg-accent-brand/60',
                )}
            />
            {children}
        </p>
    );
}

/**
 * Le mot mis en avant dans un titre.
 *
 * ⚠️ L'or est ici une encre, par décision explicite, et il faut savoir ce que
 * ça coûte : le #FFC300 du logo est mesuré à 1,53:1 sur la page claire et
 * 1,62:1 sur la bande crème. Un mot écrit dedans est décoratif — il se voit,
 * il ne se lit pas, et aucune taille ni aucune graisse ne rattrape ce rapport.
 * C'est pour ça qu'aucun mot mis en or n'est jamais le seul à porter le sens de
 * son titre : le titre reste entier sans lui.
 *
 * Le jeton employé est `accent-brand-ink`, qui existe pour exactement ça et
 * porte le même avertissement dans la feuille de style. La sortie de secours,
 * si la lisibilité l'emporte un jour sur l'exactitude de la teinte, tient en
 * une ligne là-bas : descendre ce jeton jusqu'à 4,5:1 sans toucher à un seul
 * composant.
 *
 * Les bandes colorées — la bleue, l'or, la sombre — n'en prennent pas : leur
 * titre tient déjà sa couleur de son fond.
 */
export function Accent({
    tone,
    children,
}: {
    tone: AccentTone;
    children: ReactNode;
}) {
    return (
        <span
            className={cn(
                tone === 'blue' ? 'text-primary' : 'text-accent-brand-ink',
            )}
        >
            {children}
        </span>
    );
}

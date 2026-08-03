import { ChevronDown, ChevronUp, Quote, Star } from 'lucide-react';
import { useState } from 'react';

import { cn } from '@/lib/utils';

export type Review = {
    rating: number;
    comment: string;
    author: string;
    place: string | null;
};

const MAX_RATING = 5;

/**
 * La note, en étoiles.
 *
 * Le nombre est aussi écrit pour les lecteurs d'écran : une rangée d'icônes
 * n'énonce rien, et « quatre étoiles sur cinq » est la seule forme qui se lise
 * à voix haute.
 */
function Rating({ rating }: { rating: number }) {
    return (
        <p className="flex items-center gap-0.5">
            <span className="sr-only">
                {rating} étoiles sur {MAX_RATING}
            </span>

            {Array.from({ length: MAX_RATING }, (_, index) => (
                <Star
                    key={index}
                    aria-hidden
                    className={cn(
                        'size-4',
                        index < rating
                            ? 'fill-accent-brand text-accent-brand'
                            : 'text-border',
                    )}
                />
            ))}
        </p>
    );
}

/**
 * Les avis, un à la fois, parcourus de haut en bas.
 *
 * Un seul témoignage occupe la place, en grand, plutôt qu'une rangée de cartes
 * alignées : trois avis côte à côte se lisent comme un tableau et aucun n'est
 * lu. Seul, celui-ci a le poids d'une parole.
 *
 * Le sens du déplacement fait le sens de l'animation — on descend, l'avis
 * suivant monte depuis le bas ; on remonte, il descend depuis le haut. Sans
 * cette correspondance le mouvement dit le contraire du geste.
 *
 * Le changement remonte l'élément par sa `key` : React le remplace au lieu de
 * le mettre à jour, ce qui rejoue l'animation d'entrée sans qu'aucun état ait
 * à la piloter.
 *
 * Rien ne défile tout seul. Un carrousel qui avance sans qu'on le lui demande
 * emporte la phrase qu'on était en train de lire.
 */
export function ReviewCarousel({ reviews }: { reviews: Review[] }) {
    const [index, setIndex] = useState(0);
    const [downwards, setDownwards] = useState(true);

    const move = (step: number) => {
        setDownwards(step > 0);
        setIndex(
            (current) => (current + step + reviews.length) % reviews.length,
        );
    };

    const review = reviews[index];

    return (
        // La carte est ce qui a changé : l'avis était posé à même la bande, et
        // une parole posée sur un fond n'a pas de bord — donc pas de poids. Sur
        // une surface à elle, avec beaucoup d'air autour du texte, elle se lit
        // comme une citation encadrée plutôt que comme un paragraphe de plus.
        <div className="flex items-stretch gap-5 sm:gap-8">
            <div className="min-w-0 flex-1 rounded-3xl border bg-card p-8 shadow-sm sm:p-12">
                {/* `aria-live` sur le conteneur et non sur le contenu : la zone
                    doit exister avant le changement pour qu'il soit annoncé. */}
                <blockquote aria-live="polite" className="min-h-56 sm:min-h-52">
                    <div
                        key={index}
                        className={cn(
                            downwards ? 'animate-rise' : 'animate-drop',
                        )}
                    >
                        <Quote
                            aria-hidden
                            className="size-9 fill-accent-brand text-accent-brand"
                        />

                        <p className="mt-6 font-display text-subtitle font-extrabold sm:text-3xl">
                            {review.comment}
                        </p>

                        <footer className="mt-8 flex flex-wrap items-center gap-x-4 gap-y-2 border-t pt-6">
                            <Rating rating={review.rating} />

                            <p className="text-sm font-semibold">
                                {review.author}
                                {review.place && (
                                    <span className="font-normal text-muted-foreground">
                                        {' · '}
                                        {review.place}
                                    </span>
                                )}
                            </p>
                        </footer>
                    </div>
                </blockquote>
            </div>

            {/* Les flèches à la verticale, et la position entre les deux : le
                parcours est vertical, les commandes doivent l'être aussi. */}
            {reviews.length > 1 && (
                <div className="flex shrink-0 flex-col items-center justify-center gap-3">
                    <button
                        type="button"
                        onClick={() => move(-1)}
                        aria-label="Avis précédent"
                        className="flex size-10 items-center justify-center rounded-full border bg-card text-muted-foreground shadow-sm transition-colors hover:border-primary hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        <ChevronUp className="size-5" />
                    </button>

                    <p
                        aria-hidden
                        className="font-display text-xs font-extrabold tabular-nums"
                    >
                        {index + 1}
                        <span className="text-muted-foreground">
                            /{reviews.length}
                        </span>
                    </p>

                    <button
                        type="button"
                        onClick={() => move(1)}
                        aria-label="Avis suivant"
                        className="flex size-10 items-center justify-center rounded-full border bg-card text-muted-foreground shadow-sm transition-colors hover:border-primary hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        <ChevronDown className="size-5" />
                    </button>
                </div>
            )}
        </div>
    );
}

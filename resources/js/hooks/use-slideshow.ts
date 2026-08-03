import { useEffect, useState } from 'react';

/**
 * Si des images ont le droit de se relayer toutes seules.
 *
 * Lu une fois au chargement du module, comme dans `use-count-up`, et pour la
 * même raison : la réponse doit valoir dès le premier rendu, pas être une
 * correction apportée après. Qui a demandé moins d'animation garde les puces,
 * qui ne bougent que sur un clic.
 */
const CAN_AUTOPLAY =
    typeof matchMedia !== 'undefined' &&
    !matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Un jeu d'images qui se relaient, et de quoi reprendre la main dessus.
 *
 * Le défilement s'arrête au survol et dès qu'un élément à l'intérieur prend le
 * focus : quelqu'un qui lit ou qui tabule ne doit pas voir l'image changer sous
 * ses yeux. Les gestionnaires sont rendus tels quels pour être étalés sur le
 * conteneur, plutôt que le conteneur ne soit imposé ici — les deux appelants
 * n'ont pas du tout le même cadre.
 */
export function useSlideshow(count: number, intervalMs: number) {
    const [current, setCurrent] = useState(0);
    const [paused, setPaused] = useState(false);

    const running = CAN_AUTOPLAY && count > 1 && !paused;

    useEffect(() => {
        if (!running) {
            return;
        }

        const timer = setInterval(
            () => setCurrent((index) => (index + 1) % count),
            intervalMs,
        );

        return () => clearInterval(timer);
    }, [running, count, intervalMs]);

    return {
        current,
        setCurrent,
        holdHandlers: {
            onMouseEnter: () => setPaused(true),
            onMouseLeave: () => setPaused(false),
            onFocusCapture: () => setPaused(true),
            onBlurCapture: () => setPaused(false),
        },
    };
}

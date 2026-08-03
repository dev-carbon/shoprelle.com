import type { ComponentPropsWithoutRef, ElementType } from 'react';

import { useInView } from '@/hooks/use-in-view';
import { cn } from '@/lib/utils';

/**
 * Where the content comes in from.
 *
 * `bottom` is the default and the workhorse. The two sides are for columns —
 * give each half of a split its own side and the section assembles itself
 * instead of scrolling past. `fade` is for anything that should resolve rather
 * than arrive: a headline that slides has been made to perform, and a map that
 * slides looks like it was dropped.
 *
 * `blur` et `scale` sont les deux entrées ajoutées par la refonte, et elles ont
 * chacune un seul emploi. `blur` est pour les grands titres : ils se mettent au
 * point au lieu d'arriver, ce qui est la seule façon de donner de la présence à
 * un mouvement sans le rendre bavard. `scale` est pour les images et les grands
 * panneaux — une photo qui glisse a l'air posée de travers, une photo qui
 * s'ouvre a l'air d'être à sa place.
 */
const ENTRANCE = {
    bottom: 'animate-rise',
    left: 'animate-rise-left',
    right: 'animate-rise-right',
    fade: 'animate-fade',
    blur: 'animate-rise-blur',
    scale: 'animate-scale-in',
} as const;

export type Entrance = keyof typeof ENTRANCE;

export function Reveal<T extends ElementType = 'div'>({
    as,
    from = 'bottom',
    delay = 0,
    className,
    ...props
}: { as?: T; from?: Entrance; delay?: number } & Omit<
    ComponentPropsWithoutRef<T>,
    'as' | 'from' | 'delay'
>) {
    const { ref, inView } = useInView<HTMLDivElement>();
    const Component = (as ?? 'div') as ElementType;

    return (
        <Component
            ref={ref}
            data-reveal={inView ? 'in' : 'pending'}
            style={inView ? { animationDelay: `${delay}ms` } : undefined}
            className={cn(inView && ENTRANCE[from], className)}
            {...props}
        />
    );
}

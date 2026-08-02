import type { ComponentPropsWithoutRef, ElementType } from 'react';

import { useInView } from '@/hooks/use-in-view';
import { cn } from '@/lib/utils';

/**
 * Content that arrives when it is reached, rather than being there already.
 *
 * `delay` is what turns a list into a sequence: give each child the index it
 * sits at and they land one after another, which reads as a story being told
 * instead of a block being switched on.
 *
 * Nothing here hides anything by default — the element is only made invisible
 * once the observer has confirmed it can be watched — so a page without
 * JavaScript, or a visitor who asked for less motion, sees the whole thing.
 */
export function Reveal<T extends ElementType = 'div'>({
    as,
    delay = 0,
    className,
    ...props
}: { as?: T; delay?: number } & Omit<
    ComponentPropsWithoutRef<T>,
    'as' | 'delay'
>) {
    const { ref, inView } = useInView<HTMLDivElement>();
    const Component = (as ?? 'div') as ElementType;

    return (
        <Component
            ref={ref}
            style={inView ? { animationDelay: `${delay}ms` } : undefined}
            className={cn(inView && 'animate-rise', className)}
            {...props}
        />
    );
}

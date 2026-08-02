import type { ComponentPropsWithoutRef, ElementType } from 'react';

import { CAN_OBSERVE, useInView } from '@/hooks/use-in-view';
import { cn } from '@/lib/utils';

/**
 * Whether an entrance can be played at all.
 *
 * Read once at module scope, for the same reason `use-in-view` reads its own:
 * the answer has to be part of the *first* render. Deciding it later is what
 * caused the flicker this component used to have — see below.
 *
 * Reduced motion opts out of the whole mechanism rather than just of the
 * movement. An element that is invisible until scrolled to is still a reveal,
 * however still it holds; somebody who asked for less of it should get the page
 * as written, all of it, immediately.
 */
const CAN_REVEAL =
    CAN_OBSERVE &&
    typeof matchMedia !== 'undefined' &&
    !matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Content that arrives when it is reached, rather than being there already.
 *
 * `delay` is what turns a list into a sequence: give each child the index it
 * sits at and they land one after another, which reads as a story being told
 * instead of a block being switched on. Keep those offsets small — a stagger is
 * a rhythm, and past a couple of hundred milliseconds it becomes a queue the
 * visitor is made to wait in.
 *
 * ── Why the element is hidden up front ──────────────────────────────────────
 *
 * It did not used to be. Nothing was hidden until the observer had confirmed
 * the element could be watched, on the reasoning that a page which cannot
 * observe should never end up blank. But an observer reports through a
 * callback, and a callback runs *after* the first paint — so anything already
 * on screen when the page loaded was painted at full opacity, then had an
 * animation added that begins at zero. It appeared, vanished, and faded back
 * in. One frame, on every element in the opening screen at once, which reads
 * exactly like a page that is broken.
 *
 * So the decision is made during render instead: `CAN_REVEAL` is known
 * synchronously, and an element that is going to be revealed starts hidden. The
 * original safeguard survives intact — where there is no observer, or motion is
 * declined, nothing is hidden and nothing animates.
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

    if (!CAN_REVEAL) {
        return <Component ref={ref} className={className} {...props} />;
    }

    return (
        <Component
            ref={ref}
            style={inView ? { animationDelay: `${delay}ms` } : undefined}
            className={cn(inView ? 'animate-rise' : 'opacity-0', className)}
            {...props}
        />
    );
}

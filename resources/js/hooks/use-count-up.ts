import { useEffect } from 'react';

import { useInView } from '@/hooks/use-in-view';

/**
 * Whether a number is allowed to count rather than simply be there.
 *
 * Two conditions, read once at module scope for the same reason `use-in-view`
 * reads its own: the answer has to be part of the first paint, not a correction
 * made after it.
 *
 * Reduced motion disqualifies a counter outright. It is not decoration that can
 * be toned down — a figure changing sixty times a second is close to the most
 * agitating thing a page can do to somebody who has asked for less of it.
 */
const CAN_COUNT =
    typeof IntersectionObserver !== 'undefined' &&
    typeof matchMedia !== 'undefined' &&
    !matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * A figure that counts up to itself the first time it is looked at.
 *
 * The count is written straight to the node rather than held in state, which is
 * the point of the hook rather than an optimisation of it:
 *
 *  - React renders the *final* value and never anything else. That is what the
 *    server writes, what survives with no JavaScript, and what a screen reader
 *    announces — none of which should ever be a number the business did not
 *    claim. There is no hydration mismatch to paper over, because both sides
 *    render the same thing.
 *  - Nothing re-renders. A counter held in state costs a render of its subtree
 *    on every frame it moves; three of them running for a second and a half is
 *    some two hundred and fifty renders to show text that a single assignment
 *    puts on the screen.
 *
 * The value is wound back to zero as soon as the page is interactive — far
 * above this section, which sits well down the page — so the visitor never sees
 * it fall. Eased out rather than linear: a counter at constant speed reads as a
 * progress bar, one that decelerates into its value reads as an arrival.
 */
export function useCountUp<T extends HTMLElement>(
    value: number,
    format: (value: number) => string,
    duration = 1400,
) {
    const { ref, inView } = useInView<T>();

    useEffect(() => {
        const node = ref.current;

        if (!CAN_COUNT || !node) {
            return;
        }

        node.textContent = format(0);

        if (!inView) {
            return;
        }

        let frame = 0;
        const start = performance.now();

        const step = (now: number) => {
            const progress = Math.min(1, (now - start) / duration);

            node.textContent = format(
                Math.round(value * (1 - (1 - progress) ** 3)),
            );

            if (progress < 1) {
                frame = requestAnimationFrame(step);
            }
        };

        frame = requestAnimationFrame(step);

        return () => cancelAnimationFrame(frame);
    }, [ref, inView, value, format, duration]);

    return ref;
}

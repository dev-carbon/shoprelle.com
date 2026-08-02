import { useEffect, useRef, useState } from 'react';

/**
 * No observer means no reveal, not a hidden page: anything that cannot be
 * watched starts out visible. Read once, at module scope, so the decision is
 * part of the initial state rather than a correction made after the first
 * paint — which is what would make the content flash.
 */
export const CAN_OBSERVE = typeof IntersectionObserver !== 'undefined';

/**
 * Whether an element has ever come into view.
 *
 * Deliberately one-way: once true it stays true and the observer disconnects.
 * A reveal that plays again on the way back up draws attention to content the
 * visitor has already read, which is the opposite of what it is for.
 */
export function useInView<T extends Element>(rootMargin = '0px 0px -12% 0px') {
    const ref = useRef<T>(null);
    const [inView, setInView] = useState(!CAN_OBSERVE);

    useEffect(() => {
        const node = ref.current;

        if (!node || inView) {
            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setInView(true);
                    observer.disconnect();
                }
            },
            { rootMargin },
        );

        observer.observe(node);

        return () => observer.disconnect();
    }, [inView, rootMargin]);

    return { ref, inView };
}

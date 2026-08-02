import { useEffect, useRef, useState } from 'react';

/**
 * Whether this browser can watch an element at all.
 *
 * Read at module scope, but deliberately *not* used to seed the state below.
 * The page is server-rendered, and the server has no `IntersectionObserver`:
 * letting this decide the first render would make the server and the client
 * disagree on their very first output, which React reports as a hydration
 * mismatch and refuses to patch up.
 *
 * A page that cannot observe is handled without JavaScript instead — see the
 * `reveal-ready` class in the layout and the rule it drives in the stylesheet.
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
    const [inView, setInView] = useState(false);

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

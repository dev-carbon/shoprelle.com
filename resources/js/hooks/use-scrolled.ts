import { useEffect, useState } from 'react';

/**
 * Whether the page has been scrolled past a threshold.
 *
 * Read on a frame rather than on the scroll event, which fires far faster than
 * the screen refreshes. The state only ever flips at the boundary, so React
 * re-renders twice over a whole page rather than on every pixel.
 */
export function useScrolled(threshold = 24) {
    const [scrolled, setScrolled] = useState(false);

    useEffect(() => {
        let frame = 0;

        const measure = () => {
            frame = 0;
            setScrolled(window.scrollY > threshold);
        };

        const onScroll = () => {
            frame ||= requestAnimationFrame(measure);
        };

        measure();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => {
            window.removeEventListener('scroll', onScroll);

            if (frame) {
                cancelAnimationFrame(frame);
            }
        };
    }, [threshold]);

    return scrolled;
}

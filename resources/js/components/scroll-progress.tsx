import { useEffect, useState } from 'react';

/**
 * How far down the page the visitor is, as a hairline under the header.
 *
 * Read on a frame rather than on every scroll event: the handler fires far
 * faster than the screen refreshes, and measuring the document in it is what
 * makes a progress bar the thing that stutters the page it measures.
 */
export function ScrollProgress() {
    const [progress, setProgress] = useState(0);

    useEffect(() => {
        let frame = 0;

        const measure = () => {
            frame = 0;

            const scrollable =
                document.documentElement.scrollHeight - window.innerHeight;

            setProgress(
                scrollable <= 0
                    ? 0
                    : Math.min(1, window.scrollY / scrollable) * 100,
            );
        };

        const onScroll = () => {
            frame ||= requestAnimationFrame(measure);
        };

        measure();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });

        return () => {
            window.removeEventListener('scroll', onScroll);
            window.removeEventListener('resize', onScroll);

            if (frame) {
                cancelAnimationFrame(frame);
            }
        };
    }, []);

    return (
        <div
            aria-hidden
            className="absolute inset-x-0 top-0 z-10 h-1 overflow-hidden"
        >
            <div
                className="h-full origin-left bg-primary transition-[width] duration-150 ease-out"
                style={{ width: `${progress}%` }}
            />
        </div>
    );
}

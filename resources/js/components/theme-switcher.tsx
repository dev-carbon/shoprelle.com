import { Moon, Sun } from 'lucide-react';

import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

/**
 * Theme switcher for page headers: one button toggling clair ↔ sombre.
 *
 * The decision is taken on the *resolved* theme rather than the stored one, so
 * the visitor who has never chosen — everyone starts on `système` — still gets
 * the opposite of what is on screen from the first click, instead of a click
 * that appears to do nothing. `système` itself stays available in Réglages.
 *
 * The icon shows where the click leads, not where one already is: a sun on a
 * dark page, a moon on a light one. A control that pictures the state you can
 * see is a control that says nothing — and it contradicted its own label, which
 * has always announced the destination.
 */
export function ThemeSwitcher({ className }: { className?: string }) {
    const { resolvedAppearance, updateAppearance } = useAppearance();

    const isDark = resolvedAppearance === 'dark';
    const next = isDark ? 'light' : 'dark';
    const Icon = isDark ? Sun : Moon;

    return (
        <button
            type="button"
            onClick={() => updateAppearance(next)}
            title={`Passer en thème ${isDark ? 'clair' : 'sombre'}`}
            aria-label={`Passer en thème ${isDark ? 'clair' : 'sombre'}`}
            aria-pressed={isDark}
            className={cn(
                'inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50',
                className,
            )}
        >
            <Icon className="size-4" />
        </button>
    );
}

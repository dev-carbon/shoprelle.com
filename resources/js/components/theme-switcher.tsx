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
 */
export function ThemeSwitcher({ className }: { className?: string }) {
    const { resolvedAppearance, updateAppearance } = useAppearance();

    const isDark = resolvedAppearance === 'dark';
    const next = isDark ? 'light' : 'dark';
    const Icon = isDark ? Moon : Sun;

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

import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';
import { ThemeSwitcher } from '@/components/theme-switcher';
import { home } from '@/routes';

/**
 * The frame of the customer's own area.
 *
 * Deliberately the same header as the assistant: a customer arrives here from
 * the conversation, and the two screens are one place to them.
 */
export function CustomerLayout({
    children,
    action,
}: {
    children: ReactNode;
    action?: ReactNode;
}) {
    return (
        <div className="relative flex min-h-svh flex-col bg-background">
            {/* La même trame que la vitrine, pour que l'espace client soit le
                même site : un semis de points, masqué en dégradé pour qu'il ne
                s'arrête jamais net sur un bord. */}
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-dots [mask-image:radial-gradient(80%_45%_at_50%_0%,black,transparent)] opacity-50"
            />

            <header className="relative shrink-0 border-b bg-background">
                <div className="mx-auto flex w-full max-w-3xl items-center justify-between gap-4 px-6 py-5 sm:px-8">
                    <Link
                        href={home()}
                        className="flex items-center gap-2.5 font-display font-extrabold tracking-tight"
                    >
                        <span className="flex size-9 items-center justify-center rounded-xl bg-accent-brand">
                            <AppLogoIcon className="size-4 text-accent-brand-foreground" />
                        </span>
                        Shoprelle
                    </Link>

                    <div className="flex items-center gap-1">
                        <ThemeSwitcher />
                        {action}
                    </div>
                </div>
            </header>

            <main className="relative mx-auto w-full max-w-3xl flex-1 px-6 py-14 sm:px-8 lg:py-20">
                {children}
            </main>

            <footer className="relative shrink-0 border-t bg-background">
                <p className="mx-auto w-full max-w-3xl px-6 py-7 text-center text-xs text-muted-foreground sm:px-8">
                    Vous trouvez le produit, nous nous occupons du reste.
                </p>
            </footer>
        </div>
    );
}

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
        <div className="flex min-h-svh flex-col bg-background">
            <header className="shrink-0 border-b bg-background">
                <div className="mx-auto flex w-full max-w-3xl items-center justify-between gap-4 px-5 py-4 sm:px-6">
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

            <main className="mx-auto w-full max-w-3xl flex-1 px-5 py-8 sm:px-6">
                {children}
            </main>

            <footer className="shrink-0 border-t bg-background">
                <p className="mx-auto w-full max-w-3xl px-5 py-5 text-center text-xs text-muted-foreground sm:px-6">
                    Vous trouvez le produit, nous nous occupons du reste.
                </p>
            </footer>
        </div>
    );
}

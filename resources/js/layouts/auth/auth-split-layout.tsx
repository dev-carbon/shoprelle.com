import { Link } from '@inertiajs/react';
import { PackageCheck, Scale, ShieldCheck } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import AppLogoIcon from '@/components/app-logo-icon';
import { ThemeSwitcher } from '@/components/theme-switcher';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

const HIGHLIGHTS: { icon: LucideIcon; text: string }[] = [
    { icon: PackageCheck, text: 'Chaque demande suivie de bout en bout' },
    { icon: Scale, text: 'Devis détaillé, transport calculé au poids' },
    { icon: ShieldCheck, text: 'Aucun achat sans accord du client' },
];

/**
 * The back-office sign-in screen.
 *
 * The brand panel carries the same promise as the landing page, so the team
 * signs into the product they are selling rather than a generic form. It is
 * decorative and collapses entirely below `lg`, where the form is all that
 * matters on a phone.
 */
export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative grid min-h-dvh lg:grid-cols-2">
            <aside className="relative hidden flex-col justify-between overflow-hidden bg-primary p-10 text-primary-foreground lg:flex">
                <Link
                    href={home()}
                    className="relative z-10 flex w-fit items-center gap-2.5 text-lg font-semibold"
                >
                    <span className="flex size-8 items-center justify-center rounded-lg bg-primary-foreground/15">
                        <AppLogoIcon className="size-4" />
                    </span>
                    Shoprelle
                </Link>

                <div className="relative z-10">
                    <p className="max-w-md font-display text-title font-black text-balance">
                        Vos envies, enfin livrées chez vous.
                    </p>
                    <p className="mt-3 max-w-sm text-primary-foreground/75">
                        L'espace équipe où chaque demande devient une commande
                        suivie, du lien au colis.
                    </p>

                    <ul className="mt-8 space-y-3">
                        {HIGHLIGHTS.map((highlight) => (
                            <li
                                key={highlight.text}
                                className="flex items-center gap-3 text-sm text-primary-foreground/85"
                            >
                                <highlight.icon className="size-4 shrink-0" />
                                {highlight.text}
                            </li>
                        ))}
                    </ul>
                </div>

                <p className="relative z-10 text-sm text-primary-foreground/60">
                    Shoprelle — votre concierge d'achat, du lien au colis.
                </p>
            </aside>

            <main className="relative flex items-center justify-center px-6 py-12">
                <ThemeSwitcher className="absolute top-4 right-4" />

                <div className="w-full max-w-sm space-y-6">
                    <Link
                        href={home()}
                        className="flex items-center justify-center gap-2.5 font-semibold lg:hidden"
                    >
                        <span className="flex size-8 items-center justify-center rounded-lg bg-accent-brand">
                            <AppLogoIcon className="size-4 text-accent-brand-foreground" />
                        </span>
                        Shoprelle
                    </Link>

                    <div className="flex animate-rise flex-col gap-2 text-center">
                        <h1 className="text-subtitle font-semibold">{title}</h1>
                        {description && (
                            <p className="text-sm text-balance text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>

                    <div
                        className="animate-rise"
                        style={{ animationDelay: '60ms' }}
                    >
                        {children}
                    </div>
                </div>
            </main>
        </div>
    );
}

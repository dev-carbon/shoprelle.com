import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import { mentions, privacy } from '@/routes/legal';

/**
 * L'habillage commun des deux pages légales.
 *
 * Une page de texte, pas une vitrine : un en-tête qui ramène à l'accueil, une
 * colonne de lecture, et la navigation entre les deux pages en pied. La
 * direction artistique du site est là (Archivo en titres, les mêmes jetons de
 * couleur), mais rien ne cherche à convaincre — on est ici pour vérifier.
 */
export function LegalPage({
    title,
    updatedAt,
    children,
}: {
    title: string;
    /** La date de dernière mise à jour affichée, au format lisible. */
    updatedAt: string;
    children: ReactNode;
}) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <Head title={title} />

            <header className="border-b">
                <div className="mx-auto flex w-full max-w-3xl items-center justify-between px-6 py-5">
                    <Link
                        href={home()}
                        className="flex items-center gap-2.5 font-display text-lg font-extrabold tracking-tight"
                    >
                        <span className="flex size-9 items-center justify-center rounded-xl bg-accent-brand">
                            <AppLogoIcon className="size-4 text-accent-brand-foreground" />
                        </span>
                        Shoprelle
                    </Link>

                    <Link
                        href={home()}
                        className="text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        Retour à l'accueil
                    </Link>
                </div>
            </header>

            <main className="mx-auto w-full max-w-3xl px-6 py-14">
                <h1 className="font-display text-3xl font-black tracking-tight">
                    {title}
                </h1>
                <p className="mt-3 text-sm text-muted-foreground">
                    Dernière mise à jour : {updatedAt}
                </p>

                <div className="mt-10 space-y-10">{children}</div>
            </main>

            <footer className="border-t">
                <div className="mx-auto flex w-full max-w-3xl flex-wrap items-center justify-between gap-3 px-6 py-7 text-sm text-muted-foreground">
                    <p>© {new Date().getFullYear()} Shoprelle</p>
                    <nav className="flex gap-5" aria-label="Pages légales">
                        <Link
                            href={mentions()}
                            className="transition-colors hover:text-foreground"
                        >
                            Mentions légales
                        </Link>
                        <Link
                            href={privacy()}
                            className="transition-colors hover:text-foreground"
                        >
                            Confidentialité
                        </Link>
                    </nav>
                </div>
            </footer>
        </div>
    );
}

/** Une section de la page : un titre, des paragraphes. */
export function LegalSection({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <section>
            <h2 className="font-display text-xl font-extrabold tracking-tight">
                {title}
            </h2>
            <div className="mt-4 space-y-4 leading-relaxed text-muted-foreground">
                {children}
            </div>
        </section>
    );
}

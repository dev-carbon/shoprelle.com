import { Link } from '@inertiajs/react';
import { Menu, UserRound } from 'lucide-react';

import AppLogoIcon from '@/components/app-logo-icon';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useTranslations } from '@/hooks/use-translations';
import { index as orders } from '@/routes/orders';

/**
 * La navigation de la vitrine, pour les écrans où la barre ne la porte pas.
 *
 * Sous `lg`, les ancres du header disparaissaient et ne se retrouvaient qu'au
 * pied de page — c'est-à-dire nulle part pour quelqu'un qui vient d'arriver.
 * Sur un service dont la clientèle est d'abord sur téléphone, la page la plus
 * travaillée du site était aussi la seule sans navigation.
 *
 * Un panneau, pas un plein écran : la page reste visible derrière, donc on sait
 * toujours où l'on est. Chaque lien est enveloppé de `SheetClose` — une ancre
 * ne recharge rien, le panneau ne se fermerait donc jamais tout seul.
 *
 * « Mes demandes » y figure en toutes lettres, avec son icône : dans la barre,
 * l'icône seule suffit parce qu'elle est à côté du reste ; ici, un menu est
 * précisément l'endroit où les choses se disent par leur nom.
 */
export function MobileNav({
    items,
}: {
    items: { href: string; label: string }[];
}) {
    const t = useTranslations();

    return (
        <Sheet>
            <SheetTrigger
                aria-label={t('Ouvrir le menu')}
                className="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 lg:hidden"
            >
                <Menu className="size-5" />
            </SheetTrigger>

            <SheetContent side="right" className="w-80 gap-0">
                <SheetHeader className="border-b p-6">
                    <SheetTitle className="flex items-center gap-2.5 font-display text-lg font-extrabold tracking-tight">
                        <span className="flex size-8 items-center justify-center rounded-lg bg-accent-brand">
                            <AppLogoIcon className="size-4 text-accent-brand-foreground" />
                        </span>
                        Shoprelle
                    </SheetTitle>
                </SheetHeader>

                <nav
                    aria-label={t('Sections du site')}
                    className="flex flex-col p-4"
                >
                    {items.map((item) => (
                        <SheetClose asChild key={item.href}>
                            <a
                                href={item.href}
                                className="rounded-xl px-3 py-3.5 font-display text-lg font-extrabold transition-colors hover:bg-accent"
                            >
                                {t(item.label)}
                            </a>
                        </SheetClose>
                    ))}
                </nav>

                <div className="mt-auto border-t p-4">
                    <SheetClose asChild>
                        <Link
                            href={orders()}
                            className="flex items-center gap-3 rounded-xl px-3 py-3.5 font-semibold transition-colors hover:bg-accent"
                        >
                            <UserRound className="size-4 text-muted-foreground" />
                            Mes demandes
                        </Link>
                    </SheetClose>
                </div>
            </SheetContent>
        </Sheet>
    );
}

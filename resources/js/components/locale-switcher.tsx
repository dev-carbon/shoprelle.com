import { router, usePage } from '@inertiajs/react';
import { Languages } from 'lucide-react';

import { cn } from '@/lib/utils';
import { update as localeUpdate } from '@/routes/locale';

/**
 * Le sélecteur de langue : un bouton unique, dans l'idiome du sélecteur de
 * thème — même taille, mêmes couleurs, et il affiche la destination, pas
 * l'état. « EN » sur un site en français, « FR » sur un site en anglais : un
 * bouton qui montre ce qu'on voit déjà ne dit rien.
 *
 * La langue est une préférence de session posée côté serveur — voir
 * `LocaleController` — donc changer de langue est une requête, pas un état
 * local : la page revient avec son dictionnaire et l'attribut `lang` du
 * document suit. `preserveScroll`, parce qu'on ne navigue pas.
 */
export function LocaleSwitcher({ className }: { className?: string }) {
    const { locale } = usePage().props;

    const next = locale === 'fr' ? 'en' : 'fr';
    const label = next === 'en' ? 'Switch to English' : 'Passer en français';

    return (
        <button
            type="button"
            lang={next}
            title={label}
            aria-label={label}
            onClick={() =>
                router.post(
                    localeUpdate(),
                    { locale: next },
                    { preserveScroll: true },
                )
            }
            className={cn(
                'inline-flex h-9 items-center justify-center gap-1.5 rounded-md px-2.5 text-xs font-bold text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50',
                className,
            )}
        >
            <Languages className="size-4" />
            {next.toUpperCase()}
        </button>
    );
}

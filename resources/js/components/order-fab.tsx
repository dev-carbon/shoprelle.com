import { ShoppingCart } from 'lucide-react';

import { OrderMenu } from '@/components/order-menu';
import { Button } from '@/components/ui/button';
import { useScrolled } from '@/hooks/use-scrolled';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';

/**
 * Le raccourci pour commander, qui suit le visiteur.
 *
 * La page est longue et son champ principal est tout en haut : passé le premier
 * écran, plus rien ne permet de commander sans remonter. Ce bouton répond à ça,
 * et il attend d'avoir été dépassé pour apparaître — offert d'emblée, il ne
 * ferait que doubler le champ encore à l'écran.
 *
 * Monté en permanence et non conditionné au défilement : un composant qui
 * apparaît et disparaît rejouerait son entrée à chaque passage du seuil.
 *
 * Téléphone seulement. Au-dessus de `sm`, le bouton du header reste à l'écran
 * quoi qu'il arrive — le flottant n'y répondait donc à rien, et deux boutons
 * « Commander » visibles en même temps dans deux coins différents, c'est une
 * hésitation, pas une commodité. En dessous, c'est l'inverse : la barre est trop
 * étroite pour porter une action, et le flottant est le seul raccourci qui
 * reste.
 */
export function OrderFab({
    chatHref,
    telegramUrl,
    whatsappUrl,
}: {
    chatHref: string;
    telegramUrl: string | null;
    whatsappUrl: string | null;
}) {
    const t = useTranslations();

    const visible = useScrolled(520);

    return (
        <div
            className={cn(
                'fixed right-4 bottom-4 z-40 transition-[opacity,transform] duration-300 motion-reduce:transition-none sm:hidden',
                visible
                    ? 'translate-y-0 opacity-100'
                    : 'pointer-events-none translate-y-4 opacity-0',
            )}
        >
            <OrderMenu
                chatHref={chatHref}
                telegramUrl={telegramUrl}
                whatsappUrl={whatsappUrl}
                trigger={
                    <Button
                        size="lg"
                        // Retiré du parcours au clavier tant qu'il est masqué :
                        // un bouton invisible mais atteignable à la tabulation
                        // est un piège.
                        tabIndex={visible ? undefined : -1}
                        aria-hidden={!visible}
                        className="h-12 rounded-2xl px-5 shadow-lg shadow-primary/30 transition-shadow hover:shadow-xl hover:shadow-primary/40"
                    >
                        <ShoppingCart className="size-4" />
                        {t('Commander')}
                    </Button>
                }
            />
        </div>
    );
}

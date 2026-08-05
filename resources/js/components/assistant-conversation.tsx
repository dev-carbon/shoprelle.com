import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';

/**
 * Les trois premières répliques du vrai parcours de l'assistant — le lien
 * collé, la couleur demandée — partagées entre la carte « Chat web » de la
 * section assistant et l'étape « Détails » du parcours du hero
 * (`link-to-parcel`). Une seule conversation pour toute la page : deux
 * versions qui divergeraient finiraient par raconter deux produits.
 *
 * Décorative partout où elle apparaît : le produit et la couleur sont des
 * exemples, donc le conteneur qui l'affiche porte `aria-hidden` et le texte
 * alentour dit déjà tout.
 */
const REPLIES = [
    {
        author: 'assistant',
        text: 'Envoyez le lien du produit qui vous fait envie.',
    },
    { author: 'customer', text: 'https://www.shein.com/…' },
    { author: 'assistant', text: 'Parfait ! Quelle couleur souhaitez-vous ?' },
] as const;

export function AssistantConversation({
    bubbleClassName,
}: {
    /** Ajustements d'échelle propres à l'endroit qui l'affiche. */
    bubbleClassName?: string;
}) {
    const t = useTranslations();

    return (
        <>
            {REPLIES.map((reply) => (
                <p
                    key={reply.text}
                    className={cn(
                        'w-fit max-w-[85%] rounded-2xl px-3.5 py-2 text-xs',
                        reply.author === 'assistant'
                            ? 'rounded-bl-md border bg-card'
                            : 'ml-auto rounded-br-md bg-primary text-primary-foreground',
                        bubbleClassName,
                    )}
                >
                    {t(reply.text)}
                </p>
            ))}
        </>
    );
}

import { ChevronsUp } from 'lucide-react';

import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';

/**
 * Le retour en haut de page, posé dans le footer juste au-dessus du grand
 * wordmark — l'endroit exact où l'on finit la page, c'est-à-dire le seul
 * moment où remonter se demande.
 *
 * Discret par construction : le blanc cassé du site (`muted`) en opacité
 * réduite, un double chevron, et une pulsation douce derrière — coupée pour
 * qui préfère les animations réduites. Le survol lui rend sa pleine présence.
 */
export function ScrollToTop({ className }: { className?: string }) {
    const t = useTranslations();

    return (
        <div className={cn('relative flex justify-center', className)}>
            <span
                aria-hidden
                className="absolute size-11 animate-ping rounded-full bg-muted-foreground/15 motion-reduce:hidden"
            />
            <button
                type="button"
                aria-label={t('Revenir en haut')}
                title={t('Revenir en haut')}
                onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
                className="relative flex size-11 items-center justify-center rounded-full border bg-muted text-muted-foreground opacity-70 transition-[opacity,transform] outline-none hover:-translate-y-0.5 hover:opacity-100 focus-visible:opacity-100 focus-visible:ring-[3px] focus-visible:ring-ring/50 motion-reduce:transition-none"
            >
                <ChevronsUp className="size-5" />
            </button>
        </div>
    );
}

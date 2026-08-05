import { ArrowUpRight } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Reveal } from '@/components/reveal';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';

/**
 * ── La sélection de produits ────────────────────────────────────────────────
 *
 * Ce que la page ne disait nulle part : ce qu'on peut commander. Le service est
 * ouvert — n'importe quel lien de n'importe quelle plateforme — et une promesse
 * aussi large ne donne aucune idée. Quelques produits en donnent une.
 *
 * ── Ce que cette section n'est pas
 *
 * Pas un catalogue. Rien ne se met au panier, rien n'a de stock, et le prix
 * affiché est indicatif — il est relevé à la main sur la plateforme et le dit.
 * Cliquer ouvre l'assistant avec le lien déjà collé : le parcours reste celui
 * du site, et le produit n'est qu'une façon d'y entrer.
 *
 * ── Le filtre
 *
 * Deux axes, la catégorie et la plateforme, et ils se combinent. Ils sont
 * calculés sur les produits réellement présents plutôt que sur la liste des cas
 * possibles : un filtre « Beauté » qui ne rend jamais rien est un bouton qui
 * ment. Tout le tri se fait dans le navigateur — la sélection tient en quelques
 * dizaines de produits, et un aller-retour au serveur pour cacher trois cartes
 * serait payé par tout le monde.
 */

export type ShowcaseProduct = {
    name: string;
    imageUrl: string;
    url: string;
    marketplace: string;
    marketplaceLabel: string;
    category: string;
    categoryLabel: string;
    /** Indicatif, relevé à la main. Absent tant que personne ne l'a saisi. */
    price: string | null;
    currency: string | null;
};

/** Une valeur de filtre et son libellé, comptée sur ce qui est affiché. */
type Facet = { value: string; label: string };

/** Le prix, à la française : une espace fine tous les trois chiffres. */
const formatPrice = (price: string): string =>
    String(Math.round(Number(price))).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

/**
 * Les valeurs distinctes d'un axe, dans l'ordre où elles apparaissent.
 *
 * L'ordre des produits est celui que le back-office a choisi ; les filtres le
 * suivent plutôt que de réordonner alphabétiquement, pour que le premier
 * bouton soit celui du premier produit.
 */
function facetsOf(
    products: ShowcaseProduct[],
    value: (product: ShowcaseProduct) => string,
    label: (product: ShowcaseProduct) => string,
): Facet[] {
    const seen = new Map<string, string>();

    for (const product of products) {
        if (!seen.has(value(product))) {
            seen.set(value(product), label(product));
        }
    }

    return [...seen].map(([key, name]) => ({ value: key, label: name }));
}

/** Un bouton de filtre. */
function Chip({
    active,
    onClick,
    children,
}: {
    active: boolean;
    onClick: () => void;
    children: string;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={active}
            className={cn(
                'rounded-full border px-4 py-2 text-sm font-semibold transition-colors outline-none focus-visible:ring-2 focus-visible:ring-ring/60',
                active
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'bg-card text-muted-foreground hover:border-primary/40 hover:text-foreground',
            )}
        >
            {children}
        </button>
    );
}

export function ProductShowcase({
    products,
    className,
}: {
    products: ShowcaseProduct[];
    className?: string;
}) {
    const t = useTranslations();

    const [category, setCategory] = useState<string | null>(null);
    const [marketplace, setMarketplace] = useState<string | null>(null);

    const categories = useMemo(
        () =>
            facetsOf(
                products,
                (product) => product.category,
                (product) => product.categoryLabel,
            ),
        [products],
    );

    const marketplaces = useMemo(
        () =>
            facetsOf(
                products,
                (product) => product.marketplace,
                (product) => product.marketplaceLabel,
            ),
        [products],
    );

    const shown = useMemo(
        () =>
            products.filter(
                (product) =>
                    (category === null || product.category === category) &&
                    (marketplace === null ||
                        product.marketplace === marketplace),
            ),
        [products, category, marketplace],
    );

    return (
        <div className={className}>
            {/* Les deux axes sur deux lignes, et non côte à côte : mélangés,
                « Mode » et « Shein » se lisent comme une seule liste dont on ne
                sait plus lequel exclut lequel. */}
            <div className="flex flex-col gap-4">
                {categories.length > 1 && (
                    <div
                        className="flex flex-wrap gap-2.5"
                        role="group"
                        aria-label={t('Filtrer par catégorie')}
                    >
                        <Chip
                            active={category === null}
                            onClick={() => setCategory(null)}
                        >
                            {t('Tout')}
                        </Chip>
                        {categories.map((facet) => (
                            <Chip
                                key={facet.value}
                                active={category === facet.value}
                                onClick={() =>
                                    setCategory(
                                        category === facet.value
                                            ? null
                                            : facet.value,
                                    )
                                }
                            >
                                {facet.label}
                            </Chip>
                        ))}
                    </div>
                )}

                {marketplaces.length > 1 && (
                    <div
                        className="flex flex-wrap gap-2.5"
                        role="group"
                        aria-label={t('Filtrer par plateforme')}
                    >
                        <Chip
                            active={marketplace === null}
                            onClick={() => setMarketplace(null)}
                        >
                            {t('Toutes les plateformes')}
                        </Chip>
                        {marketplaces.map((facet) => (
                            <Chip
                                key={facet.value}
                                active={marketplace === facet.value}
                                onClick={() =>
                                    setMarketplace(
                                        marketplace === facet.value
                                            ? null
                                            : facet.value,
                                    )
                                }
                            >
                                {facet.label}
                            </Chip>
                        ))}
                    </div>
                )}
            </div>

            {/* Annoncé aux lecteurs d'écran : le filtre change ce qui est à
                l'écran sans que rien ne soit dit à qui ne le voit pas. */}
            <p aria-live="polite" className="sr-only">
                {shown.length} produit{shown.length > 1 ? 's' : ''} affiché
                {shown.length > 1 ? 's' : ''}.
            </p>

            <ul className="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                {shown.map((product, index) => (
                    <Reveal
                        as="li"
                        from="scale"
                        key={product.url}
                        delay={Math.min(index, 7) * 60}
                    >
                        <a
                            href={product.url}
                            target="_blank"
                            rel="noopener noreferrer nofollow"
                            className="group flex h-full flex-col overflow-hidden rounded-3xl border bg-card transition-colors hover:border-primary/40"
                        >
                            <img
                                src={product.imageUrl}
                                alt={product.name}
                                loading="lazy"
                                decoding="async"
                                className="aspect-square w-full object-cover transition-transform duration-700 ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100"
                            />

                            <div className="flex flex-1 flex-col p-5">
                                <p className="font-display text-eyebrow font-extrabold text-muted-foreground uppercase">
                                    {product.marketplaceLabel}
                                </p>

                                <p className="mt-2 font-display font-extrabold">
                                    {product.name}
                                </p>

                                <div className="mt-auto flex items-end justify-between gap-3 pt-5">
                                    {product.price ? (
                                        <p className="font-display text-lg font-black tabular-nums">
                                            {formatPrice(product.price)}
                                            <span className="ml-1 text-xs font-bold text-muted-foreground">
                                                {product.currency}
                                            </span>
                                        </p>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            {t('Prix sur devis')}
                                        </p>
                                    )}

                                    <span
                                        aria-hidden
                                        className="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground transition-colors group-hover:bg-primary group-hover:text-primary-foreground"
                                    >
                                        <ArrowUpRight className="size-4" />
                                    </span>
                                </div>
                            </div>
                        </a>
                    </Reveal>
                ))}
            </ul>
        </div>
    );
}

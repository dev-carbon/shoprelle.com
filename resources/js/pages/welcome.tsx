import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    Check,
    ChevronDown,
    Headset,
    Link as LinkIcon,
    Mail,
    MessagesSquare,
    Plane,
    Receipt,
    Send,
    ShieldCheck,
    ShoppingBag,
    ShoppingCart,
    X,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { lazy, Suspense } from 'react';
import type { ComponentType, ReactNode } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';
import { BrandWordmark } from '@/components/brand-wordmark';
import { LinkToParcel } from '@/components/link-to-parcel';
import {
    MARKETPLACE_COLORS,
    MARKETPLACE_LOGOS,
} from '@/components/marketplace-logos';
import { MarketplaceMarquee } from '@/components/marketplace-marquee';
import { OrderFab } from '@/components/order-fab';
import { OrderMenu } from '@/components/order-menu';
import { Reveal } from '@/components/reveal';
import { ScrollProgress } from '@/components/scroll-progress';
import {
    SOCIAL_ICONS,
    WHATSAPP_GREEN,
    WhatsAppIcon,
} from '@/components/social-icons';
import { ThemeSwitcher } from '@/components/theme-switcher';
import { Button } from '@/components/ui/button';
import { WorldBackdrop } from '@/components/world-backdrop';
import { useCountUp } from '@/hooks/use-count-up';
import { useScrolled } from '@/hooks/use-scrolled';
import delivered from '@/images/colis-livre.webp';
import { flagFor } from '@/lib/destinations';
import { cn } from '@/lib/utils';
import { dashboard, login } from '@/routes';
import { link, show as chat } from '@/routes/chat';

/**
 * The map is the single heaviest thing on this page — the projection library
 * and the continent's outlines together outweigh everything else combined — and
 * it sits two thirds of the way down. Split out, it streams in beside the rest
 * instead of holding up the hero.
 */
const DeliveryMap = lazy(() =>
    import('@/components/delivery-map').then((module) => ({
        default: module.DeliveryMap,
    })),
);

const MARKETPLACES = [
    'Shein',
    'Temu',
    'Amazon',
    'AliExpress',
    'Zara',
    'ASOS',
    'Zalando',
    'Bershka',
    'H&M',
    'Nike',
    'Adidas',
    'Sephora',
    'Decathlon',
];

/**
 * The in-page anchors offered in the header and repeated in the footer, which
 * is where a phone gets to them: the header nav is hidden below `lg`.
 */
const NAVIGATION: { href: string; label: string }[] = [
    { href: '#livraison', label: 'Livraison' },
    { href: '#comment-ca-marche', label: 'Comment ça marche' },
    { href: '#assistant', label: "L'assistant" },
    { href: '#a-propos', label: 'À propos' },
    { href: '#contact', label: 'Contact' },
];

/**
 * The two halves of the story section.
 *
 * Nothing on the right is a new promise: each line restates something the page
 * already commits to elsewhere, which is what keeps a persuasive block from
 * quietly becoming the place where the service over-claims.
 */
const OBSTACLES: string[] = [
    "Le site ne livre pas jusqu'à chez vous.",
    "Votre carte est refusée à l'étranger.",
    'Les frais réels arrivent après la commande.',
    'Personne à qui parler quand ça bloque.',
];

const ANSWERS: string[] = [
    'Nous achetons depuis la France et expédions chez vous.',
    'Vous payez une seule fois, en monnaie locale.',
    'Le devis détaille tout avant le moindre paiement.',
    'Une personne lit chaque demande et vous répond.',
];

const STEPS: { icon: LucideIcon; title: string; description: string }[] = [
    {
        icon: MessagesSquare,
        title: 'Vous partagez le lien',
        description:
            'Votre concierge vous guide : plateforme, lien, couleur, taille, quantité. Aucun compte à créer.',
    },
    {
        icon: ShoppingBag,
        title: 'Nous achetons pour vous',
        description:
            'Nous vérifions la demande, vous envoyons un devis clair, puis nous achetons le produit en France.',
    },
    {
        icon: Plane,
        title: 'Nous expédions chez vous',
        description:
            'Vos colis sont regroupés puis expédiés vers votre ville. Vous suivez tout avec votre référence.',
    },
];

const PROMISES: { icon: LucideIcon; title: string; description: string }[] = [
    {
        icon: ShieldCheck,
        title: 'Rien sans votre accord',
        description:
            "Nous n'achetons qu'après votre validation du devis. Vous voyez le prix du produit, le transport et le total avant d'engager le moindre franc — et vous pouvez dire non.",
    },
    {
        icon: BadgeCheck,
        title: 'Suivi de commande',
        description:
            'Votre référence suffit pour connaître l’état de votre demande, sans compte ni mot de passe.',
    },
    {
        icon: Receipt,
        title: 'Tarifs transparents',
        description:
            'Le prix du produit, plus le transport calculé au poids réel. Tout figure sur le devis.',
    },
    {
        icon: Headset,
        title: 'Assistance humaine',
        description:
            'Une personne lit chaque demande. Quand votre question sort du cadre, quelqu’un vous répond.',
    },
    {
        icon: Plane,
        title: 'Livraison internationale',
        description:
            'Plusieurs produits, plusieurs sites, un seul envoi vers votre ville.',
    },
];

/**
 * The reassurance beneath the map.
 *
 * Same rule as the story section: not one of these is a new promise. Each line
 * restates something the page already commits to elsewhere — which is what
 * keeps the most persuasive block on the page from quietly becoming the place
 * where the service over-claims.
 */
const ASSURANCES: { icon: LucideIcon; title: string; description: string }[] = [
    {
        icon: ShoppingBag,
        title: 'Acheté depuis la France',
        description:
            'Nous commandons sur place, avec nos moyens de paiement, puis nous expédions.',
    },
    {
        icon: Receipt,
        title: 'Devis avant paiement',
        description:
            'Le produit et le transport au poids réel, détaillés avant le moindre franc.',
    },
    {
        icon: BadgeCheck,
        title: 'Suivi par référence',
        description:
            'Votre référence suffit pour savoir où en est votre colis, sans compte.',
    },
];

const QUESTIONS: { question: string; answer: string }[] = [
    {
        question: 'Sur quels sites puis-je commander ?',
        answer: "Sur tous ceux qui livrent en France : Shein, Temu, Amazon, AliExpress, Zara et bien d'autres. Si votre site n'est pas dans la liste, envoyez quand même le lien.",
    },
    {
        question: 'Comment sont calculés les frais ?',
        answer: 'Le prix du produit, plus le transport calculé au poids. Tout figure sur le devis que vous recevez avant de payer quoi que ce soit.',
    },
    {
        question: 'Dois-je créer un compte ?',
        answer: "Non. Vous discutez avec l'assistant, vous recevez une référence, et c'est elle qui vous sert à suivre votre demande.",
    },
    {
        question: 'Et si le produit ne convient pas ?',
        answer: 'Écrivez-nous avant la commande : nous vérifions la disponibilité, la taille et la couleur avec le vendeur pour éviter les mauvaises surprises.',
    },
];

/**
 * A destination as the page receives it. The estimate is attached by the
 * controller from `config/shoprelle.delivery_times` and is null for any country
 * nobody has measured — a delay is a promise, and the map invents none.
 */
type Destination = {
    code: string;
    name: string;
    deliveryTime: string | null;
};

type SocialNetwork = 'instagram' | 'facebook' | 'tiktok';

/**
 * The figures beside the map. The two nullable ones are claims about the
 * business rather than facts about the configuration, so an install that has
 * not been given them leaves those counters out entirely — see the controller.
 */
type NetworkStats = {
    countries: number;
    upcoming: number;
    parcelsShipped: number | null;
    satisfactionPercent: number | null;
};

type Props = {
    contact: { email: string; responseTime: string };
    telegramUrl: string | null;
    whatsappUrl: string | null;
    countries: Destination[];
    /** Announced, not open — the assistant still refuses these. */
    upcomingCountries: Destination[];
    stats: NetworkStats;
    social: Partial<Record<SocialNetwork, string>>;
};

/**
 * A figure grouped the French way: a narrow no-break space every three digits.
 *
 * Written out rather than handed to `toLocaleString`, which picks its separator
 * from whatever ICU data the engine was built with — and a number that comes
 * out `1,240` on one browser and `1 240` on the next is a number the page has
 * lost control of.
 */
const formatFigure = (value: number) =>
    String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

/**
 * One figure in the network row, counting up to itself as it is reached.
 *
 * The number is what the eye lands on, so it is set at heading weight and in
 * tabular figures: proportional digits change the width of the number as it
 * counts, and a counter that shuffles the label beside it reads as broken.
 */
function Stat({
    value,
    suffix,
    label,
    hint,
}: {
    value: number;
    suffix?: string;
    label: string;
    hint?: string;
}) {
    // Only the digits count, so only the digits get the ref: the hook writes
    // straight to that node, and a suffix living inside it would be wiped on
    // the first frame.
    const ref = useCountUp<HTMLSpanElement>(value, formatFigure);

    return (
        <div className="text-center">
            <p className="font-display text-title font-black tabular-nums">
                <span ref={ref}>{formatFigure(value)}</span>
                {suffix && (
                    <span className="ml-0.5 align-baseline text-[0.6em] text-primary">
                        {suffix}
                    </span>
                )}
            </p>

            <p className="mt-2 font-display text-sm font-extrabold">{label}</p>

            {hint && (
                <p className="mt-1 text-sm text-muted-foreground">{hint}</p>
            )}
        </div>
    );
}

/**
 * One key to the map: a swatch and what it means.
 */
function Legend({ swatch, children }: { swatch: string; children: string }) {
    return (
        <li className="flex items-center gap-2">
            <span aria-hidden className={cn('size-2.5 rounded-full', swatch)} />
            {children}
        </li>
    );
}

/**
 * One way to reach the assistant.
 *
 * Rendered as a link when the channel is live and as a plain tile when it is
 * not: a channel nobody is listening to should still be announced, but it must
 * not look clickable.
 */
function ChannelCard({
    icon: Icon,
    name,
    description,
    badge,
    brand,
    href,
    external,
}: {
    /* Assez large pour une Lucide comme pour un glyphe maison. */
    icon: ComponentType<{ className?: string }>;
    name: string;
    description: string;
    badge: string;
    /**
     * La couleur propre de la marque, pour la pastille de l'icône.
     *
     * Même règle que les tuiles marketplace : une marque se dessine dans sa
     * couleur, pas dans celle du site. Quand elle est absente, la pastille
     * prend le bleu de Shoprelle — les canaux que Shoprelle opère lui-même.
     */
    brand?: string;
    href?: string;
    external?: boolean;
}) {
    const body = (
        <>
            <div className="flex items-center gap-3">
                <span
                    style={brand ? { backgroundColor: brand } : undefined}
                    className={cn(
                        'flex size-11 shrink-0 items-center justify-center rounded-xl',
                        brand
                            ? 'text-white'
                            : 'bg-primary text-primary-foreground',
                    )}
                >
                    <Icon className="size-5" />
                </span>
                <div className="min-w-0">
                    <p className="font-display text-lg font-extrabold">
                        {name}
                    </p>
                    <p
                        className={cn(
                            'text-xs font-medium',
                            href ? 'text-success' : 'text-muted-foreground',
                        )}
                    >
                        {badge}
                    </p>
                </div>
            </div>

            <p className="mt-5 text-sm text-muted-foreground">{description}</p>

            {href && (
                <p className="mt-auto inline-flex items-center gap-1.5 pt-6 text-sm font-semibold text-primary">
                    Ouvrir
                    <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                </p>
            )}
        </>
    );

    // `h-full` and a column layout, so two cards with descriptions of
    // different lengths still line up and both actions sit on the same line.
    const shell =
        'flex h-full flex-col rounded-2xl border bg-card p-6 shadow-sm transition-[transform,box-shadow,border-color] duration-200 sm:p-7';

    if (!href) {
        return <div className={cn(shell, 'opacity-70')}>{body}</div>;
    }

    if (external) {
        return (
            <a
                href={href}
                target="_blank"
                rel="noopener noreferrer"
                className={cn(
                    shell,
                    'group block hover:-translate-y-1 hover:border-primary/40 hover:shadow-md',
                )}
            >
                {body}
            </a>
        );
    }

    return (
        <Link
            href={href}
            className={cn(
                shell,
                'group block hover:-translate-y-1 hover:border-primary/40 hover:shadow-md',
            )}
        >
            {body}
        </Link>
    );
}

/**
 * One column of the footer.
 */
function FooterColumn({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <div>
            <p className="font-display text-sm font-extrabold">{title}</p>
            <ul className="mt-4 space-y-3 text-sm text-muted-foreground">
                {children}
            </ul>
        </div>
    );
}

/**
 * The social networks, in a fixed order, keeping only the ones a link exists
 * for. An unset profile is dropped rather than linked to nowhere.
 */
const SOCIAL_NETWORKS: { key: SocialNetwork; label: string }[] = [
    { key: 'instagram', label: 'Instagram' },
    { key: 'facebook', label: 'Facebook' },
    { key: 'tiktok', label: 'TikTok' },
];

export default function Welcome({
    contact,
    telegramUrl,
    whatsappUrl,
    countries,
    upcomingCountries,
    stats,
    social,
}: Props) {
    const { auth } = usePage().props;
    const scrolled = useScrolled();

    const socials = SOCIAL_NETWORKS.flatMap((network) => {
        const href = social[network.key];

        return href ? [{ ...network, href }] : [];
    });

    /**
     * The counters, keeping only the ones there is a real number for.
     *
     * The destinations are always counted, because they are counted off the
     * config the assistant itself reads. The other two are left out until an
     * install is given them, so an empty row is the worst this can look — never
     * a figure nobody earned.
     */
    const figures = [
        {
            value: stats.countries,
            label: 'Pays desservis',
            hint:
                stats.upcoming > 0
                    ? `+ ${stats.upcoming} destinations annoncées`
                    : undefined,
        },
        ...(stats.parcelsShipped
            ? [
                  {
                      value: stats.parcelsShipped,
                      label: 'Colis expédiés',
                      hint: "Depuis l'ouverture du service.",
                  },
              ]
            : []),
        ...(stats.satisfactionPercent
            ? [
                  {
                      value: stats.satisfactionPercent,
                      suffix: '%',
                      label: 'Satisfaction client',
                      hint: 'Sur les demandes livrées.',
                  },
              ]
            : []),
    ];

    return (
        <div className="flex min-h-svh flex-col bg-background">
            {/* The slogan is the title: it is what a search result and a
                browser tab have room for, and it says the whole service. */}
            <Head title="Un lien. Une commande. Une livraison." />

            {/* The header tightens and lifts once the page moves: at the top
                it is part of the hero, and after that it is a bar. The border
                is only earned on scroll, so the hero starts unfenced. */}
            <header
                className={cn(
                    'sticky top-0 z-10 transition-[background-color,box-shadow,border-color] duration-300',
                    scrolled
                        ? 'border-b bg-background/85 shadow-sm backdrop-blur'
                        : 'border-b border-transparent bg-background',
                )}
            >
                <ScrollProgress />
                <div
                    className={cn(
                        'mx-auto flex w-full max-w-7xl items-center justify-between gap-6 px-4 transition-[padding] duration-300',
                        scrolled ? 'py-2.5' : 'py-4',
                    )}
                >
                    <a
                        href="#top"
                        className="flex items-center gap-2.5 font-display text-lg font-extrabold tracking-tight"
                    >
                        <span
                            className={cn(
                                'flex items-center justify-center rounded-lg bg-accent-brand transition-[width,height] duration-300',
                                scrolled ? 'size-7' : 'size-8',
                            )}
                        >
                            <AppLogoIcon className="size-4 text-accent-brand-foreground" />
                        </span>
                        Shoprelle
                    </a>

                    <nav
                        aria-label="Sections du site"
                        className="hidden items-center gap-7 text-sm font-medium lg:flex"
                    >
                        {NAVIGATION.map((item) => (
                            <a
                                key={item.href}
                                href={item.href}
                                className="text-muted-foreground transition-colors hover:text-foreground"
                            >
                                {item.label}
                            </a>
                        ))}
                    </nav>

                    {/* The staff entrance lives in the footer only: the header
                        belongs to the visitor, and a door marked for somebody
                        else is one more thing for them to read past. */}
                    {/* Le bouton n'est plus discret et n'est plus réservé au
                        grand écran. C'est la seule action que le header
                        propose, et sur un téléphone c'était précisément là
                        qu'il disparaissait. Il porte l'ombre portée du bleu de
                        marque : sur une barre presque blanche, une teinte
                        pleine sans relief se lit comme une étiquette, pas comme
                        un bouton. */}
                    <div className="flex items-center gap-2">
                        <ThemeSwitcher />

                        {/* Le même menu que le bouton flottant : le
                            header n'impose plus le chat web. Le libellé se
                            raccourcit sous `sm`, où la barre est étroite. */}
                        <OrderMenu
                            chatHref={chat().url}
                            telegramUrl={telegramUrl}
                            whatsappUrl={whatsappUrl}
                            trigger={
                                <Button className="shadow-md shadow-primary/25 transition-shadow hover:shadow-lg hover:shadow-primary/30">
                                    <span className="hidden sm:inline">
                                        Nouvelle commande
                                    </span>
                                    <span className="sm:hidden">Commander</span>
                                    <ShoppingCart className="size-4" />
                                </Button>
                            }
                        />
                    </div>
                </div>
            </header>

            <main className="flex-1">
                {/* Hero — a soft radial wash gives the section depth without
                    adding anything the eye has to decode, and the ghosted world
                    behind it says "everywhere" before a word has been read.
                    Both are masked away well above the headline: the hero's job
                    is the sentence and the field under it, and texture behind
                    either would only make them harder to read. */}
                <section id="top" className="relative overflow-hidden border-b">
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_60%_at_50%_0%,var(--color-primary)_0%,transparent_70%)] opacity-[0.07]"
                    />

                    <WorldBackdrop
                        codes={countries.map((country) => country.code)}
                    />

                    <div className="relative mx-auto w-full max-w-3xl px-4 pt-28 pb-20 text-center lg:pt-36 lg:pb-24">
                        <h1
                            className="mx-auto max-w-3xl animate-rise font-display text-hero font-black text-balance"
                            style={{ animationDelay: '40ms' }}
                        >
                            Vos envies, enfin livrées chez vous.
                        </h1>

                        <p
                            className="mx-auto mt-7 max-w-xl animate-rise text-lg text-balance text-muted-foreground"
                            style={{ animationDelay: '150ms' }}
                        >
                            Un lien suffit pour commander sur vos plateformes
                            préférées.
                        </p>

                        {/* Not a mock-up of a field: it is the field. Pasting a
                            link here opens the assistant with the platform
                            already read off the host and the product already
                            attached, so the first thing asked is the colour. A
                            hero that only *looks* like the product is a poster;
                            this one is the first step of it. */}
                        <Form
                            {...link.form()}
                            className="mx-auto mt-12 flex w-full max-w-2xl animate-rise flex-col gap-3 sm:flex-row"
                            style={{ animationDelay: '210ms' }}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="flex-1 text-left">
                                        <label
                                            htmlFor="hero-url"
                                            className="sr-only"
                                        >
                                            Lien du produit
                                        </label>
                                        {/* Plus haut et plus posé qu'un champ
                                            de formulaire ordinaire : c'est
                                            l'objet du hero, pas un réglage. */}
                                        <div className="flex items-center gap-2.5 rounded-2xl border bg-card px-5 shadow-lg shadow-foreground/[0.06] transition-shadow focus-within:border-primary focus-within:ring-[3px] focus-within:ring-ring/40">
                                            <LinkIcon className="size-5 shrink-0 text-muted-foreground" />
                                            <input
                                                id="hero-url"
                                                name="url"
                                                type="url"
                                                inputMode="url"
                                                maxLength={2048}
                                                autoComplete="off"
                                                placeholder="https://www.shein.com/…"
                                                className="h-14 w-full min-w-0 bg-transparent text-base outline-none placeholder:text-muted-foreground"
                                            />
                                        </div>

                                        {errors.url && (
                                            <p className="mt-2 text-sm text-destructive">
                                                {errors.url}
                                            </p>
                                        )}
                                    </div>

                                    <Button
                                        type="submit"
                                        size="lg"
                                        disabled={processing}
                                        className="h-14 shrink-0 rounded-2xl px-8 text-base shadow-lg shadow-primary/25 transition-shadow hover:shadow-xl hover:shadow-primary/30"
                                    >
                                        Commander
                                        <ArrowRight className="size-4" />
                                    </Button>
                                </>
                            )}
                        </Form>

                        {/* Une ligne grise disait les trois choses d'un coup et
                            ne s'en faisait lire aucune. Séparées, cochées, elles
                            se parcourent — et chacune reprend un engagement que
                            la page tient plus bas, aucune n'en ajoute. */}
                        <ul
                            className="mx-auto mt-8 flex animate-rise flex-wrap items-center justify-center gap-x-7 gap-y-3 text-sm font-medium"
                            style={{ animationDelay: '260ms' }}
                        >
                            {[
                                'Gratuit, sans inscription',
                                'Devis avant paiement',
                                `Réponse ${contact.responseTime}`,
                            ].map((promise) => (
                                <li
                                    key={promise}
                                    className="flex items-center gap-2"
                                >
                                    <Check className="size-4 shrink-0 text-success" />
                                    {promise}
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* Wider than the copy above it: four cards in a row need
                        the room, and the timeline is the part of the hero doing
                        the explaining. */}
                    <div className="relative mx-auto w-full max-w-7xl px-4 pt-4">
                        <LinkToParcel
                            className="animate-rise"
                            style={{ animationDelay: '300ms' }}
                        />
                    </div>

                    {/* The platforms close the hero, with nothing said about
                        them. They used to have a section and a paragraph of
                        their own further down; but the marks are the argument —
                        a visitor either recognises Shein and Amazon or does not,
                        and no sentence next to them changes that. Here they
                        answer "does this work with what I already use?" while
                        the promise above is still on screen. */}
                    <div
                        className="relative mx-auto w-full max-w-7xl animate-rise px-4 pt-16 pb-20 lg:pb-24"
                        style={{ animationDelay: '380ms' }}
                    >
                        <MarketplaceMarquee
                            marketplaces={MARKETPLACES}
                            logos={MARKETPLACE_LOGOS}
                            colors={MARKETPLACE_COLORS}
                        />
                    </div>
                </section>

                {/* Straight after the hero, because "do you even deliver to
                    me?" is the question that disqualifies every other one. A
                    visitor who cannot be served should find that out in three
                    seconds, not after scrolling a sales page.

                    The map is not an illustration of the answer, it *is* the
                    answer: the hub in France, an arc to every destination, and
                    a parcel on each arc. Everything else in the section — the
                    counters, the names, the reassurance — reads that same
                    drawing back in words, for the visitor and for the screen
                    reader that cannot see a shape. */}
                <section
                    id="livraison"
                    className="relative overflow-hidden border-b py-24 lg:py-32"
                >
                    {/* The same radial wash as the hero, at half its strength.
                        It is the whole of the depth here: the section's subject
                        is a network of thin blue lines, and anything heavier
                        behind them is something they have to fight. */}
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_55%_at_50%_0%,var(--color-primary)_0%,transparent_70%)] opacity-[0.06]"
                    />

                    {/* La promesse et son aboutissement, côte à côte.

                        Tout ce qui suit dans cette section est un schéma : des
                        arcs, des chiffres, des pastilles. Une seule image de ce
                        que tout cela produit — un colis dans les mains de
                        quelqu'un, devant chez lui — dit ce qu'un réseau tracé ne
                        dira jamais, et elle ouvre la section plutôt que de la
                        clore.

                        Chargée en `lazy`, dimensions déclarées : elle est bien
                        au-delà du premier écran, et la place lui est réservée
                        d'avance pour que rien ne saute à son arrivée. */}
                    <div className="relative mx-auto grid w-full max-w-7xl items-center gap-10 px-4 lg:grid-cols-[minmax(0,6fr)_minmax(0,5fr)] lg:gap-16">
                        <Reveal
                            from="left"
                            className="text-center lg:text-left"
                        >
                            <h2 className="font-display text-title font-black text-balance">
                                Vos envies voyagent jusqu'à vous.
                            </h2>
                            <p className="mt-5 max-w-xl text-lg text-muted-foreground">
                                Shoprelle vous ouvre l'accès aux plus grandes
                                plateformes d'achat et organise l'acheminement
                                de vos commandes jusqu'à votre destination.
                            </p>
                        </Reveal>

                        <Reveal from="right">
                            <img
                                src={delivered}
                                alt="Une cliente reçoit son colis Shoprelle devant chez elle."
                                width={1200}
                                height={655}
                                loading="lazy"
                                decoding="async"
                                className="aspect-[5/4] w-full rounded-2xl border object-cover shadow-lg shadow-foreground/[0.07]"
                            />
                        </Reveal>
                    </div>

                    {/* Full bleed, and the only thing on the page that is: the
                        map is the answer this section exists to give, and a
                        gutter around it turns the world into an illustration of
                        somewhere else. Capped all the same — past about eighteen
                        hundred pixels it stops being a band and starts being a
                        wall. */}
                    <Reveal
                        from="fade"
                        delay={100}
                        className="relative mx-auto mt-14 w-full max-w-[1800px] lg:mt-16"
                    >
                        <Suspense
                            fallback={
                                <div className="aspect-[957/348] w-full animate-pulse bg-muted-foreground/10" />
                            }
                        >
                            <DeliveryMap
                                countries={countries}
                                upcomingCountries={upcomingCountries}
                            />
                        </Suspense>
                    </Reveal>

                    <div className="relative mx-auto w-full max-w-7xl px-4">
                        <ul className="mt-8 flex flex-wrap items-center justify-center gap-x-7 gap-y-2 text-xs font-semibold">
                            <Legend swatch="bg-accent-brand">
                                Hub · France
                            </Legend>
                            <Legend swatch="bg-primary">
                                Livraison disponible
                            </Legend>
                            <Legend swatch="bg-primary/50">
                                Bientôt desservi
                            </Legend>
                        </ul>

                        {/* The counters, on a rule of their own: they are the
                            claim the map makes, stated as numbers, and sitting
                            them under the drawing would make them read as its
                            caption. */}
                        <div className="mx-auto mt-16 grid max-w-4xl gap-10 border-t pt-12 sm:grid-cols-3 sm:gap-6">
                            {figures.map((figure, index) => (
                                <Reveal
                                    key={figure.label}
                                    delay={index * 80}
                                    className="sm:not-first:border-l sm:not-first:border-border"
                                >
                                    <Stat {...figure} />
                                </Reveal>
                            ))}
                        </div>

                        {/* The destinations come from the same config the
                            assistant offers, so the page cannot promise a
                            country the conversation would then refuse. They are
                            named as well as drawn: the markers carry their name,
                            but a list is what a visitor scans and what a search
                            engine reads. */}
                        <div className="mx-auto mt-16 flex w-full max-w-3xl flex-col items-center gap-6 text-center">
                            <div>
                                <p className="flex items-center justify-center gap-2 text-sm font-semibold">
                                    <span className="size-2 rounded-full bg-primary" />
                                    Nous livrons aujourd'hui
                                </p>
                                <ul className="mt-3 flex flex-wrap justify-center gap-2">
                                    {countries.map((country, index) => (
                                        <Reveal
                                            as="li"
                                            from="fade"
                                            key={country.code}
                                            delay={index * 55}
                                            className="flex items-center gap-2 rounded-full border bg-card px-4 py-1.5 text-sm font-semibold shadow-sm"
                                        >
                                            <span aria-hidden>
                                                {flagFor(country.code)}
                                            </span>
                                            {country.name}
                                        </Reveal>
                                    ))}
                                </ul>
                            </div>

                            {upcomingCountries.length > 0 && (
                                <div className="border-t pt-6">
                                    <p className="text-sm text-muted-foreground">
                                        Bientôt
                                    </p>
                                    {/* Outlined rather than filled, matching
                                        the paler countries on the map: these
                                        are announced, not open, and the
                                        assistant turns them down until the
                                        config says otherwise. */}
                                    <ul className="mt-3 flex flex-wrap justify-center gap-2">
                                        {upcomingCountries.map(
                                            (country, index) => (
                                                <Reveal
                                                    as="li"
                                                    from="fade"
                                                    key={country.code}
                                                    delay={index * 55}
                                                    className="flex items-center gap-2 rounded-full border border-dashed px-4 py-1.5 text-sm font-medium text-muted-foreground"
                                                >
                                                    <span aria-hidden>
                                                        {flagFor(country.code)}
                                                    </span>
                                                    {country.name}
                                                </Reveal>
                                            ),
                                        )}
                                    </ul>
                                </div>
                            )}
                        </div>

                        {/* Two columns, and the same split the rest of the page
                            uses: the heading holds the narrow side and the
                            content takes the width. Stacked one per line rather
                            than side by side — three columns forced each
                            description into four ragged lines, and a promise
                            that has to be deciphered is not reassuring. Given a
                            full line each they read at a glance. */}
                        <div className="mt-16 grid gap-10 border-t pt-14 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-14">
                            <Reveal
                                from="left"
                                className="lg:sticky lg:top-28 lg:self-start"
                            >
                                <h3 className="font-display text-subtitle font-extrabold text-balance">
                                    Le même parcours, quelle que soit la
                                    destination.
                                </h3>
                                <p className="mt-3 max-w-sm text-muted-foreground">
                                    Chaque commande suit les mêmes étapes, du
                                    panier français jusqu'à votre ville.
                                </p>
                            </Reveal>

                            <ul className="flex flex-col">
                                {ASSURANCES.map((assurance, index) => (
                                    <Reveal
                                        as="li"
                                        from="right"
                                        key={assurance.title}
                                        delay={index * 80}
                                        className="flex gap-5 border-b py-5 first:pt-0 last:border-b-0 last:pb-0"
                                    >
                                        <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-accent-brand text-accent-brand-foreground">
                                            <assurance.icon className="size-5" />
                                        </span>

                                        <div className="pt-1">
                                            <p className="font-display font-extrabold">
                                                {assurance.title}
                                            </p>
                                            <p className="mt-1 text-muted-foreground">
                                                {assurance.description}
                                            </p>
                                        </div>
                                    </Reveal>
                                ))}
                            </ul>
                        </div>
                    </div>
                </section>

                <section
                    id="comment-ca-marche"
                    className="border-b section-brand py-24 lg:py-32"
                >
                    {/* Same two-column split as the marketplace section: the
                        heading holds the narrow side and the content takes the
                        width, which is what keeps a wide page from stretching
                        its paragraphs past reading length. */}
                    <div className="mx-auto grid w-full max-w-7xl gap-10 px-4 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-14">
                        <div className="lg:sticky lg:top-28 lg:self-start">
                            <h2 className="font-display text-title font-black text-balance">
                                Trois étapes, et c'est tout
                            </h2>
                            <p className="mt-4 max-w-sm text-lg text-muted-foreground">
                                Pas de formulaire interminable, pas de compte :
                                une conversation.
                            </p>
                        </div>

                        {/* A rail rather than three cards: the steps happen in
                            an order, and a timeline says so where a grid does
                            not. */}
                        <ol>
                            {STEPS.map((step, index) => (
                                <Reveal
                                    as="li"
                                    from="right"
                                    key={step.title}
                                    delay={index * 110}
                                    className="group grid grid-cols-[auto_1fr] gap-x-5 sm:gap-x-8"
                                >
                                    <div className="flex flex-col items-center">
                                        <span className="flex size-11 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                            <step.icon className="size-5" />
                                        </span>

                                        {/* Connector, stopped at the last node. */}
                                        <span
                                            aria-hidden
                                            className="w-px flex-1 bg-primary/30 group-last:hidden"
                                        />
                                    </div>

                                    <div className="pt-1.5 pb-12 group-last:pb-0">
                                        <span className="inline-flex items-center rounded-md bg-accent-brand px-2.5 py-1 text-xs font-bold tracking-wide text-accent-brand-foreground tabular-nums">
                                            Étape 0{index + 1}
                                        </span>

                                        <h3 className="mt-4 font-display text-subtitle font-extrabold text-balance">
                                            {step.title}
                                        </h3>
                                        <p className="mt-2 max-w-xl text-muted-foreground">
                                            {step.description}
                                        </p>
                                    </div>
                                </Reveal>
                            ))}
                        </ol>
                    </div>
                </section>

                {/* The assistant is the product, so it gets a section of its own
                    rather than a line in the steps: the visitor's real question
                    is where the conversation happens, and the answer is
                    "wherever you already are". */}
                <section id="assistant" className="border-b py-24 lg:py-32">
                    <div className="mx-auto grid w-full max-w-7xl items-center gap-10 px-4 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-14">
                        <div>
                            <h2 className="font-display text-title font-black text-balance">
                                Commandez simplement en discutant
                            </h2>
                            <p className="mt-4 max-w-sm text-lg text-muted-foreground">
                                Plus besoin de remplir des formulaires
                                compliqués. Envoyez un lien à notre assistant et
                                laissez-vous guider étape par étape.
                            </p>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <Reveal from="right" className="h-full">
                                <ChannelCard
                                    icon={MessagesSquare}
                                    name="Chat web"
                                    badge="Disponible maintenant"
                                    description="Ici même, dans votre navigateur. Rien à installer, aucun compte à créer : la conversation commence tout de suite."
                                    href={chat().url}
                                />
                            </Reveal>

                            <Reveal from="right" delay={140} className="h-full">
                                <ChannelCard
                                    icon={Send}
                                    name="Telegram"
                                    badge={
                                        telegramUrl
                                            ? 'Disponible maintenant'
                                            : 'Bientôt disponible'
                                    }
                                    description="Le même assistant, dans votre messagerie. Vous gardez l'historique de vos demandes dans une conversation que vous n'avez pas à rouvrir."
                                    href={telegramUrl ?? undefined}
                                    external
                                />
                            </Reveal>

                            {/* WhatsApp n'est pas l'assistant, et la carte ne
                                doit jamais le laisser croire : derrière ce
                                lien il y a une personne, pas le robot. C'est
                                la même promesse que le reste de la page — « une
                                personne lit chaque demande » — mais elle se
                                tient dans un fil que le client garde. */}
                            <Reveal from="right" delay={280} className="h-full">
                                <ChannelCard
                                    icon={WhatsAppIcon}
                                    brand={WHATSAPP_GREEN}
                                    name="WhatsApp"
                                    badge={
                                        whatsappUrl
                                            ? 'Disponible maintenant'
                                            : 'Bientôt disponible'
                                    }
                                    description="Écrivez-nous sur WhatsApp : une personne vous répond et vous accompagne jusqu'à la commande."
                                    href={whatsappUrl ?? undefined}
                                    external
                                />
                            </Reveal>
                        </div>
                    </div>
                </section>

                {/* The story, and the only place on the page that names the
                    problem rather than the service. It sits after the mechanism
                    on purpose: a visitor who has seen how it works is the one
                    who wants to know why it had to exist. */}
                <section
                    id="a-propos"
                    className="border-b bg-surface-alt py-24 lg:py-32"
                >
                    <div className="mx-auto grid w-full max-w-7xl gap-12 px-4 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-16">
                        <Reveal
                            from="left"
                            className="lg:sticky lg:top-28 lg:self-start"
                        >
                            <h2 className="font-display text-title font-black text-balance">
                                Pourquoi Shoprelle existe ?
                            </h2>
                            <p className="mt-6 text-lg text-muted-foreground">
                                De nombreux produits sont difficiles à obtenir
                                dans certaines régions du monde. Shoprelle
                                simplifie l'accès aux grandes plateformes
                                internationales en prenant en charge l'achat et
                                la livraison pour vous.
                            </p>
                        </Reveal>

                        {/* Two columns rather than another list of promises:
                            the section next door already says what we do well,
                            and what is missing from the page is what goes wrong
                            without it. Every line on the right is something
                            claimed elsewhere on this page, not a new promise. */}
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Reveal
                                from="right"
                                className="rounded-2xl border border-dashed p-6 sm:p-7"
                            >
                                <p className="font-display text-sm font-extrabold text-muted-foreground">
                                    Sans Shoprelle
                                </p>
                                <ul className="mt-5 space-y-3.5">
                                    {OBSTACLES.map((obstacle) => (
                                        <li
                                            key={obstacle}
                                            className="flex gap-2.5 text-sm text-muted-foreground"
                                        >
                                            <X className="mt-0.5 size-4 shrink-0" />
                                            {obstacle}
                                        </li>
                                    ))}
                                </ul>
                            </Reveal>

                            <Reveal
                                from="right"
                                delay={100}
                                className="rounded-2xl border bg-card p-6 shadow-sm sm:p-7"
                            >
                                <p className="font-display text-sm font-extrabold">
                                    Avec Shoprelle
                                </p>
                                <ul className="mt-5 space-y-3.5">
                                    {ANSWERS.map((answer) => (
                                        <li
                                            key={answer}
                                            className="flex gap-2.5 text-sm"
                                        >
                                            <Check className="mt-0.5 size-4 shrink-0 text-success" />
                                            {answer}
                                        </li>
                                    ))}
                                </ul>
                            </Reveal>
                        </div>
                    </div>
                </section>

                <section className="border-b py-24 lg:py-32">
                    <div className="mx-auto w-full max-w-7xl px-4">
                        <div className="max-w-2xl">
                            <h2 className="font-display text-title font-black text-balance">
                                Une solution simple, transparente et fiable
                            </h2>
                        </div>

                        {/* Five cards in a three-column grid: the first spans
                            two, which is what makes both rows come out full
                            instead of leaving a hole under the last one. */}
                        <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {PROMISES.map((promise, index) => (
                                <Reveal
                                    key={promise.title}
                                    delay={index * 80}
                                    className={cn(
                                        'rounded-2xl border bg-card p-7 shadow-sm transition-[transform,box-shadow] duration-200 hover:-translate-y-1 hover:shadow-md sm:p-8',
                                        index === 0 && 'sm:col-span-2',
                                    )}
                                >
                                    <span className="flex size-10 items-center justify-center rounded-xl bg-accent-brand text-accent-brand-foreground">
                                        <promise.icon className="size-5" />
                                    </span>

                                    <h3
                                        className={cn(
                                            'mt-6 font-display font-extrabold text-balance',
                                            index === 0
                                                ? 'text-subtitle'
                                                : 'text-lg',
                                        )}
                                    >
                                        {promise.title}
                                    </h3>
                                    <p className="mt-3 max-w-md text-muted-foreground">
                                        {promise.description}
                                    </p>
                                </Reveal>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="border-b py-24 lg:py-32">
                    <div className="mx-auto grid w-full max-w-7xl gap-10 px-4 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-14">
                        <div className="lg:sticky lg:top-28 lg:self-start">
                            <h2 className="font-display text-title font-black text-balance">
                                Ce qu'on nous demande le plus
                            </h2>
                        </div>

                        {/* Native <details> rather than a scripted accordion: it
                            is keyboard operable and findable by the browser's own
                            in-page search without any JavaScript. Sharing one
                            `name` is what closes the others when one opens. */}
                        <div>
                            {QUESTIONS.map((item, index) => (
                                <Reveal
                                    as="details"
                                    key={item.question}
                                    delay={index * 80}
                                    name="questions-frequentes"
                                    className="group border-b"
                                >
                                    <summary className="flex cursor-pointer list-none items-center justify-between gap-4 py-5 font-semibold transition-colors hover:text-primary focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring [&::-webkit-details-marker]:hidden">
                                        {item.question}

                                        <ChevronDown
                                            aria-hidden
                                            className="size-4 shrink-0 text-muted-foreground transition-transform duration-200 group-open:rotate-180"
                                        />
                                    </summary>

                                    <p className="max-w-2xl pt-1 pb-8 text-muted-foreground">
                                        {item.answer}
                                    </p>
                                </Reveal>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Contact is informational on purpose: a form would open a
                    second inbox to watch, and anything about an existing
                    request is better answered by the assistant, which can look
                    it up from its reference. */}
                <section
                    id="contact"
                    className="border-b bg-surface-alt py-24 lg:py-32"
                >
                    <div className="mx-auto grid w-full max-w-7xl gap-12 px-4 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-14">
                        <Reveal from="left">
                            <h2 className="font-display text-title font-black text-balance">
                                Nous contacter
                            </h2>
                            <p className="mt-4 max-w-md text-muted-foreground">
                                Une question avant de commander, un partenariat,
                                ou simplement une hésitation : écrivez-nous, on
                                répond {contact.responseTime}.
                            </p>
                        </Reveal>

                        <Reveal
                            from="right"
                            delay={140}
                            className="flex flex-col gap-6"
                        >
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Par email
                                </p>
                                <a
                                    href={`mailto:${contact.email}`}
                                    className="mt-1 inline-flex items-center gap-2 font-display text-subtitle font-extrabold text-primary underline-offset-4 transition-colors hover:underline"
                                >
                                    <Mail className="size-5 shrink-0" />
                                    {contact.email}
                                </a>
                            </div>

                            <div className="border-t pt-6">
                                <p className="text-sm text-muted-foreground">
                                    Vous avez déjà une demande en cours ?
                                    L'assistant la retrouve avec votre référence
                                    et vous donne son statut tout de suite.
                                </p>
                                <Link
                                    href={chat()}
                                    className="mt-3 inline-flex items-center gap-1.5 font-semibold text-primary underline-offset-4 hover:underline"
                                >
                                    Suivre ma demande
                                    <ArrowRight className="size-4" />
                                </Link>
                            </div>
                        </Reveal>
                    </div>
                </section>

                {/* The last thing on the page is the only thing on its screen:
                    one sentence, one button, and the brand blue behind both. */}
                <section className="section-accent">
                    <div className="mx-auto w-full max-w-5xl px-4 py-28 text-center lg:py-36">
                        <Reveal
                            as="h2"
                            from="fade"
                            className="font-display text-display font-black text-balance"
                        >
                            Le produit que vous cherchez est à un lien de
                            distance.
                        </Reveal>

                        <Button
                            size="lg"
                            asChild
                            className="mt-12 h-14 px-9 text-base"
                        >
                            <Link href={chat()}>
                                Envoyer un lien
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    </div>
                </section>
            </main>

            <footer className="border-t">
                <div className="mx-auto grid w-full max-w-7xl gap-10 px-4 py-16 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_repeat(3,minmax(0,1fr))] lg:gap-12">
                    <div>
                        <span className="flex items-center gap-2.5 font-display text-lg font-extrabold tracking-tight">
                            <span className="flex size-8 items-center justify-center rounded-lg bg-accent-brand">
                                <AppLogoIcon className="size-4 text-accent-brand-foreground" />
                            </span>
                            Shoprelle
                        </span>

                        <p className="mt-5 font-display text-subtitle font-extrabold text-balance">
                            Un lien. Une commande. Une livraison.
                        </p>

                        <p className="mt-4 max-w-sm text-sm text-muted-foreground">
                            Shoprelle simplifie vos achats en ligne. Envoyez un
                            lien depuis vos plateformes préférées, nous nous
                            chargeons de l'achat, du suivi et de la livraison
                            jusqu'à votre destination.
                        </p>

                        {socials.length > 0 && (
                            <ul className="mt-6 flex items-center gap-2">
                                {socials.map((network) => {
                                    const Icon = SOCIAL_ICONS[network.key];

                                    return (
                                        <li key={network.key}>
                                            <a
                                                href={network.href}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                aria-label={network.label}
                                                className="flex size-10 items-center justify-center rounded-full border text-muted-foreground transition-colors hover:border-primary/40 hover:text-primary"
                                            >
                                                <Icon className="size-4" />
                                            </a>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </div>

                    {/* The header nav disappears below `lg`; this column is
                        where a phone gets the same anchors. */}
                    <FooterColumn title="Le site">
                        {NAVIGATION.map((item) => (
                            <li key={item.href}>
                                <a
                                    href={item.href}
                                    className="transition-colors hover:text-foreground"
                                >
                                    {item.label}
                                </a>
                            </li>
                        ))}
                    </FooterColumn>

                    <FooterColumn title="Commander">
                        <li>
                            <Link
                                href={chat()}
                                className="transition-colors hover:text-foreground"
                            >
                                Créer une demande
                            </Link>
                        </li>
                        <li>
                            <Link
                                href={chat()}
                                className="transition-colors hover:text-foreground"
                            >
                                Suivre ma demande
                            </Link>
                        </li>
                        {telegramUrl && (
                            <li>
                                <a
                                    href={telegramUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="transition-colors hover:text-foreground"
                                >
                                    Sur Telegram
                                </a>
                            </li>
                        )}
                        {whatsappUrl && (
                            <li>
                                <a
                                    href={whatsappUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="transition-colors hover:text-foreground"
                                >
                                    Sur WhatsApp
                                </a>
                            </li>
                        )}
                    </FooterColumn>

                    <FooterColumn title="Contact">
                        <li>
                            <a
                                href={`mailto:${contact.email}`}
                                className="transition-colors hover:text-foreground"
                            >
                                {contact.email}
                            </a>
                        </li>
                        <li>
                            <Link
                                href={auth.user ? dashboard() : login()}
                                className="transition-colors hover:text-foreground"
                            >
                                Espace équipe
                            </Link>
                        </li>
                    </FooterColumn>
                </div>

                <div className="mx-auto w-full max-w-7xl border-t px-4 py-6">
                    <p className="text-sm text-muted-foreground">
                        © {new Date().getFullYear()} Shoprelle. Tous droits
                        réservés.
                    </p>
                </div>

                <BrandWordmark />
            </footer>

            <OrderFab
                chatHref={chat().url}
                telegramUrl={telegramUrl}
                whatsappUrl={whatsappUrl}
            />
        </div>
    );
}

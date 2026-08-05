import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    Check,
    ChevronDown,
    Headset,
    Link as LinkIcon,
    Mail,
    Megaphone,
    MessagesSquare,
    Plane,
    Receipt,
    Send,
    ShieldCheck,
    ShoppingBag,
    ShoppingCart,
    UserRound,
    X,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { lazy, Suspense, useRef, useState } from 'react';
import type { ComponentType, ReactNode } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';
import { AssistantConversation } from '@/components/assistant-conversation';
import { BrandWordmark } from '@/components/brand-wordmark';
import { HeroShowcase } from '@/components/hero-showcase';
import { LinkToParcel } from '@/components/link-to-parcel';
import { LocaleSwitcher } from '@/components/locale-switcher';
import {
    MARKETPLACE_COLORS,
    MARKETPLACE_LOGOS,
} from '@/components/marketplace-logos';
import { MarketplaceMarquee } from '@/components/marketplace-marquee';
import { MobileNav } from '@/components/mobile-nav';
import { OrderFab } from '@/components/order-fab';
import { OrderMenu } from '@/components/order-menu';
import { PAYMENT_LOGOS } from '@/components/payment-logos';
import { ConversationShot, ProcessPhotos } from '@/components/process-photos';
import { ProductShowcase } from '@/components/product-showcase';
import type { ShowcaseProduct } from '@/components/product-showcase';
import { Reveal } from '@/components/reveal';
import { ReviewCarousel } from '@/components/review-carousel';
import type { Review } from '@/components/review-carousel';
import { ScrollProgress } from '@/components/scroll-progress';
import { ScrollToTop } from '@/components/scroll-to-top';
import { Accent, Eyebrow } from '@/components/section-heading';
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
import { useTranslations } from '@/hooks/use-translations';
import { flagFor } from '@/lib/destinations';
import { SHOW_PROCESS_PHOTOS } from '@/lib/photos';
import { cn } from '@/lib/utils';
import { dashboard, login } from '@/routes';
import { link, show as chat } from '@/routes/chat';
import {
    mentions as legalMentions,
    privacy as legalPrivacy,
} from '@/routes/legal';
import { index as orders } from '@/routes/orders';

/**
 * The map is the single heaviest thing on this page — the projection library
 * and the continent's outlines together outweigh everything else combined — and
 * it sits two thirds of the way down. Split out, it streams in beside the rest
 * instead of holding up the hero.
 */
const AfricaMap = lazy(() =>
    import('@/components/africa-map').then((module) => ({
        default: module.AfricaMap,
    })),
);

/**
 * ── La gouttière, en un seul endroit ────────────────────────────────────────
 *
 * Toutes les sections lisent la même largeur et les mêmes marges latérales. Ce
 * sont les marges qui ont le plus bougé dans cette refonte : 16 px sur un
 * téléphone et 40 sur un grand écran, contre 16 partout auparavant. Une mise en
 * page aérée se joue d'abord au bord de l'écran, où l'œil mesure sans y penser
 * la place que le contenu s'accorde.
 */
const SHELL = 'mx-auto w-full max-w-[90rem] px-6 sm:px-10 lg:px-14 2xl:px-20';

/**
 * Le rythme vertical des sections, lui aussi unique.
 *
 * Nettement plus large qu'avant, et c'est délibéré : ce qui sépare deux idées
 * sur une page, c'est le vide entre elles. Le pas est le même partout pour que
 * le défilement ait une cadence — une section serrée suivie d'une section large
 * se lit comme une erreur de gabarit.
 */
const BAND = 'py-36 lg:py-56';

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
    'Boulanger',
    'Fnac',
    'Darty',
    'Foot Locker',
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
    /** Le message du bandeau, déjà localisé, ou null quand il est désactivé. */
    promoBanner: string | null;
    contact: { email: string; responseTime: string };
    telegramUrl: string | null;
    whatsappUrl: string | null;
    countries: Destination[];
    /** Announced, not open — the assistant still refuses these. */
    upcomingCountries: Destination[];
    stats: NetworkStats;
    reviews: Review[];
    social: Partial<Record<SocialNetwork, string>>;
    /** Les moyens de paiement acceptés, sans leurs coordonnées. */
    paymentMethods: { name: string; colour: string }[];
    /** La sélection tenue depuis le back-office. Vide tant qu'elle l'est. */
    products: ShowcaseProduct[];
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
    String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

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
        <div>
            <p className="font-display text-title font-black tabular-nums">
                <span ref={ref}>{formatFigure(value)}</span>
                {suffix && (
                    <span className="ml-0.5 align-baseline text-[0.6em] text-primary">
                        {suffix}
                    </span>
                )}
            </p>

            <p className="mt-3 font-display text-sm font-extrabold">{label}</p>

            {hint && (
                <p className="mt-1.5 text-sm text-muted-foreground">{hint}</p>
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
    featured,
    children,
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
    /**
     * La carte majeure du bento : plus large, plus haute, et la seule à
     * pouvoir porter un aperçu (`children`). Un seul canal y a droit — celui
     * qui est toujours disponible, sans rien installer.
     */
    featured?: boolean;
    children?: ReactNode;
}) {
    const t = useTranslations();

    const body = (
        <>
            <div className="flex items-center gap-4">
                <span
                    style={brand ? { backgroundColor: brand } : undefined}
                    className={cn(
                        'flex size-12 shrink-0 items-center justify-center rounded-2xl',
                        brand
                            ? 'text-white'
                            : 'bg-primary text-primary-foreground',
                    )}
                >
                    <Icon className="size-5" />
                </span>
                <div className="min-w-0">
                    <p
                        className={cn(
                            'font-display font-extrabold',
                            featured ? 'text-xl' : 'text-lg',
                        )}
                    >
                        {name}
                    </p>
                    <p
                        className={cn(
                            'mt-0.5 text-xs font-semibold',
                            href ? 'text-success' : 'text-muted-foreground',
                        )}
                    >
                        {badge}
                    </p>
                </div>
            </div>

            <p className="mt-6 text-body text-muted-foreground">
                {description}
            </p>

            {children}

            {href && (
                <p className="mt-auto inline-flex items-center gap-1.5 pt-8 text-sm font-semibold text-primary">
                    {t('Ouvrir')}
                    <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                </p>
            )}
        </>
    );

    // `h-full` and a column layout, so two cards with descriptions of
    // different lengths still line up and both actions sit on the same line.
    const shell = cn(
        'flex h-full flex-col rounded-3xl border bg-card shadow-sm',
        featured ? 'p-8 sm:p-10' : 'p-7 sm:p-8',
    );

    if (!href) {
        return <div className={cn(shell, 'opacity-70')}>{body}</div>;
    }

    if (external) {
        return (
            <a
                href={href}
                target="_blank"
                rel="noopener noreferrer"
                className={cn(shell, 'group block card-lift')}
            >
                {body}
            </a>
        );
    }

    return (
        <Link href={href} className={cn(shell, 'group block card-lift')}>
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
            <p className="font-display text-eyebrow font-extrabold uppercase">
                {title}
            </p>
            <ul className="mt-5 space-y-3.5 text-sm text-muted-foreground">
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
    promoBanner,
    contact,
    telegramUrl,
    whatsappUrl,
    countries,
    upcomingCountries,
    stats,
    reviews,
    social,
    paymentMethods,
    products,
}: Props) {
    const { auth } = usePage().props;
    const scrolled = useScrolled();
    const t = useTranslations();

    /**
     * La marque que le parcours met en scène, et le moyen d'y aller.
     *
     * Cliquer une tuile du bandeau ne quitte pas la page — envoyer le visiteur
     * chez la marque au milieu du hero, c'est le perdre. Le clic répond à la
     * question que la tuile vient de poser (« ça marche avec celle-là ? ») en
     * amenant au parcours, recommencé à sa première étape avec cette marque
     * dans le champ. Le remontage par la `key` s'occupe du recommencement.
     */
    const [demoMarketplace, setDemoMarketplace] = useState('Temu');
    const parcoursCard = useRef<HTMLDivElement>(null);

    const showParcoursFor = (marketplace: string) => {
        setDemoMarketplace(marketplace);

        // Le défilement vise la carte du parcours, pas le titre de sa
        // section : atterrir sur le titre laissait la démonstration sous la
        // ligne de flottaison des écrans courts — un « léger défilement » et
        // rien à voir. Et il part deux images après le clic, une fois le
        // parcours remonté : lancé avant, l'ancrage de défilement du
        // navigateur peut l'écourter quand le sous-arbre se remplace.
        requestAnimationFrame(() =>
            requestAnimationFrame(() => {
                parcoursCard.current?.scrollIntoView({
                    behavior: window.matchMedia(
                        '(prefers-reduced-motion: reduce)',
                    ).matches
                        ? 'auto'
                        : 'smooth',
                    block: 'start',
                });
            }),
        );
    };

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
            label: t('Pays desservis'),
            hint:
                stats.upcoming > 0
                    ? `+ ${stats.upcoming} ${t('destinations annoncées')}`
                    : undefined,
        },
        ...(stats.parcelsShipped
            ? [
                  {
                      value: stats.parcelsShipped,
                      label: t('Colis expédiés'),
                      hint: t("Depuis l'ouverture du service."),
                  },
              ]
            : []),
        ...(stats.satisfactionPercent
            ? [
                  {
                      value: stats.satisfactionPercent,
                      suffix: '%',
                      label: t('Satisfaction client'),
                      hint: t('Sur les demandes livrées.'),
                  },
              ]
            : []),
    ];

    return (
        <div className="flex min-h-svh flex-col bg-background">
            {/* The slogan is the title: it is what a search result and a
                browser tab have room for, and it says the whole service. */}
            <Head title={t('Un lien. Une commande. Une livraison.')} />

            {/* Le bandeau de promotion, tout en haut : le message arrive déjà
                dans la langue de la session, contrôlé depuis le back-office
                (écran « Bandeau »). Absent, il n'occupe pas un pixel.

                Il défile, dans l'idiome du bandeau des marketplaces : la piste
                contient deux copies de la même liste et l'animation la décale
                d'exactement la moitié, donc la boucle est invisible. Le
                message est répété pour que la liste dépasse toujours la
                largeur de l'écran. Survoler suspend ; en mouvement réduit,
                l'animation est annulée par la feuille de style et la première
                copie reste lisible, immobile. Les lecteurs d'écran reçoivent
                le message une fois, en clair. */}
            {promoBanner && (
                <div className="group bg-accent-brand text-accent-brand-foreground">
                    <p className="sr-only">{promoBanner}</p>

                    <div aria-hidden className="overflow-hidden py-2.5">
                        <div className="flex w-max animate-marquee items-center group-hover:[animation-play-state:paused]">
                            {[0, 1].map((copy) => (
                                <ul
                                    key={copy}
                                    className="flex shrink-0 items-center"
                                >
                                    {Array.from({ length: 6 }, (_, index) => (
                                        <li
                                            key={index}
                                            className="flex items-center gap-2.5 pr-12 text-sm font-semibold whitespace-nowrap"
                                        >
                                            <Megaphone className="size-4 shrink-0" />
                                            {promoBanner}
                                        </li>
                                    ))}
                                </ul>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            {/* The header tightens and lifts once the page moves: at the top
                it is part of the hero, and after that it is a bar. The border
                is only earned on scroll, so the hero starts unfenced. */}
            <header
                className={cn(
                    'sticky top-0 z-30 transition-[background-color,box-shadow,border-color] duration-300',
                    scrolled
                        ? 'border-b bg-background/80 shadow-sm backdrop-blur-xl'
                        : 'border-b border-transparent bg-background',
                )}
            >
                <ScrollProgress />
                <div
                    className={cn(
                        SHELL,
                        'flex items-center justify-between gap-6 transition-[padding] duration-300',
                        scrolled ? 'py-3' : 'py-5',
                    )}
                >
                    {/* Le logo et les ancres forment un seul groupe, calé à
                        gauche ; tout ce qui est réglage ou action vit à
                        droite. Deux rives, pas trois colonnes. */}
                    <div className="flex min-w-0 items-center gap-8">
                        <a
                            href="#top"
                            className="flex shrink-0 items-center gap-2.5 font-display text-lg font-extrabold tracking-tight"
                        >
                            <span
                                className={cn(
                                    'flex items-center justify-center rounded-xl bg-accent-brand transition-[width,height] duration-300',
                                    scrolled ? 'size-8' : 'size-9',
                                )}
                            >
                                <AppLogoIcon className="size-4 text-accent-brand-foreground" />
                            </span>
                            Shoprelle
                        </a>

                        {/* Les liens ont maintenant une zone à eux : au survol,
                            une pastille apparaît sous le mot au lieu de le
                            teinter. Sur une barre aussi dépouillée, changer la
                            couleur d'un mot est le retour le plus discret qu'on
                            puisse donner — et au bout de cinq liens il ne se
                            voit plus. */}
                        <nav
                            aria-label={t('Sections du site')}
                            className="hidden items-center gap-1 lg:flex"
                        >
                            {NAVIGATION.map((item) => (
                                <a
                                    key={item.href}
                                    href={item.href}
                                    className="rounded-full px-3.5 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                >
                                    {t(item.label)}
                                </a>
                            ))}
                        </nav>
                    </div>

                    {/* The staff entrance lives in the footer only: the header
                        belongs to the visitor, and a door marked for somebody
                        else is one more thing for them to read past. */}
                    <div className="flex items-center gap-2">
                        <LocaleSwitcher />
                        <ThemeSwitcher />

                        {/* ── La porte des clients qui reviennent ────────────
                            Elle est dans la barre et pas seulement au pied de
                            page : quelqu'un qui vient relire son devis n'a
                            aucune raison de faire défiler toute une page de
                            vente pour retrouver l'entrée.

                            Une icône plutôt que « Mes demandes » écrit en
                            toutes lettres. Trois raisons, dans l'ordre où elles
                            comptent :

                              — c'est un espace personnel, et la silhouette est
                                ce que tout le monde cherche pour en trouver un.
                                Le mot, lui, se lisait comme un lien de plus au
                                milieu des cinq du menu.
                              — elle tient sur un téléphone, où le libellé était
                                purement et simplement caché. L'espace client y
                                était donc introuvable depuis la barre.
                              — elle se range avec le sélecteur de thème, à côté
                                du bouton d'action : les deux commandes de la
                                personne d'un côté, ce qu'on lui propose de
                                faire de l'autre.

                            Le nom reste dit — `aria-label` et `title` — parce
                            qu'une icône seule n'a jamais annoncé où elle mène. */}
                        <Link
                            href={orders()}
                            title={t('Mes demandes')}
                            aria-label={t('Mes demandes')}
                            className="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <UserRound className="size-4" />
                        </Link>

                        {/* Le même menu que le bouton flottant : le header
                            n'impose plus le chat web.

                            Masqué sur téléphone, où le bouton flottant prend le
                            relais. Les deux se partagent l'écran au lieu de se
                            doubler : sur un grand écran la barre est toujours
                            là, donc le flottant n'a rien à y faire ; sur un
                            téléphone la barre est trop étroite pour porter une
                            action à côté du reste, donc c'est l'inverse. */}
                        <OrderMenu
                            chatHref={chat().url}
                            telegramUrl={telegramUrl}
                            whatsappUrl={whatsappUrl}
                            trigger={
                                // Le rayon par défaut des boutons, comme ses
                                // voisins de barre : ici il appartient à la
                                // navbar avant d'appartenir à la famille des
                                // grands « Commander », et une pilule au milieu
                                // de coins discrets se lisait comme un intrus.
                                <Button className="hidden h-10 px-5 shadow-md shadow-primary/25 transition-shadow hover:shadow-lg hover:shadow-primary/30 sm:inline-flex">
                                    <ShoppingCart className="size-4" />
                                    {t('Commander')}
                                </Button>
                            }
                        />

                        {/* Le menu, là où la barre ne porte plus les ancres.
                            Dernier de la rangée : c'est la place où tous les
                            sites le mettent, et un menu se trouve avant de se
                            lire. */}
                        <MobileNav items={NAVIGATION} />
                    </div>
                </div>
            </header>

            <main className="flex-1">
                {/* ── Hero ────────────────────────────────────────────────────
                    Deux colonnes, et c'est le changement de fond de cette
                    refonte. La promesse et le champ qui la déclenche tiennent
                    la colonne large ; la photo tient l'autre.

                    Un hero centré demande au visiteur de croire une phrase.
                    Celui-ci lui montre quelqu'un qui a déjà reçu son colis, à
                    hauteur d'œil, avant même qu'il ait fait défiler quoi que ce
                    soit — et il n'y a rien qu'un paragraphe puisse faire à la
                    place de ça. */}
                <section
                    id="top"
                    className="relative overflow-hidden border-b pt-20 pb-28 lg:pt-28 lg:pb-40"
                >
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0 animate-drift bg-[radial-gradient(55%_60%_at_50%_0%,var(--color-primary)_0%,transparent_70%)] opacity-[0.08]"
                    />

                    <WorldBackdrop
                        codes={countries.map((country) => country.code)}
                    />

                    <div
                        className={cn(
                            SHELL,
                            'relative grid items-center gap-20 lg:grid-cols-[minmax(0,6fr)_minmax(0,5fr)] lg:gap-24',
                        )}
                    >
                        <div className="text-center lg:text-left">
                            {/* Pas de sur-titre au-dessus du titre du hero :
                                c'est la première chose de la page, et rien n'a
                                à l'annoncer.

                                Se met au point plutôt que d'arriver : à cette
                                taille, un titre qui glisse fait un mouvement de
                                quinze centimètres à l'écran. */}
                            <h1
                                className="animate-rise-blur font-display text-hero font-black"
                                style={{ animationDelay: '60ms' }}
                            >
                                {t('Vos envies, enfin livrées chez vous.')}
                            </h1>

                            <p
                                className="mx-auto mt-9 max-w-lg animate-rise text-lead text-muted-foreground lg:mx-0"
                                style={{ animationDelay: '160ms' }}
                            >
                                {t(
                                    "Un lien suffit pour commander sur vos plateformes préférées. Nous achetons depuis la France et livrons jusqu'à votre ville.",
                                )}
                            </p>

                            {/* Not a mock-up of a field: it is the field.
                                Pasting a link here opens the assistant with the
                                platform already read off the host and the
                                product already attached, so the first thing
                                asked is the colour. A hero that only *looks*
                                like the product is a poster; this one is the
                                first step of it. */}
                            <Form
                                {...link.form()}
                                className="mt-12 flex w-full animate-rise flex-col gap-4 sm:flex-row"
                                style={{ animationDelay: '220ms' }}
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="flex-1 text-left">
                                            <label
                                                htmlFor="hero-url"
                                                className="sr-only"
                                            >
                                                {t('Lien du produit')}
                                            </label>
                                            {/* Plus haut et plus posé qu'un
                                                champ de formulaire ordinaire :
                                                c'est l'objet du hero, pas un
                                                réglage. */}
                                            <div className="flex items-center gap-3 rounded-2xl border bg-card px-5 shadow-lg shadow-foreground/[0.06] transition-shadow focus-within:border-primary focus-within:ring-[3px] focus-within:ring-ring/40">
                                                <LinkIcon className="size-5 shrink-0 text-muted-foreground" />
                                                <input
                                                    id="hero-url"
                                                    name="url"
                                                    type="url"
                                                    inputMode="url"
                                                    maxLength={2048}
                                                    autoComplete="off"
                                                    placeholder="https://www.shein.com/…"
                                                    className="h-16 w-full min-w-0 bg-transparent text-base outline-none placeholder:text-muted-foreground"
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
                                            className="h-16 shrink-0 rounded-2xl px-8 text-base shadow-lg shadow-primary/25 transition-shadow hover:shadow-xl hover:shadow-primary/30"
                                        >
                                            {/* Le chariot, à gauche : la même
                                                icône à la même place sur tous
                                                les boutons « Commander » de la
                                                page. */}
                                            <ShoppingCart className="size-4" />
                                            {t('Commander')}
                                        </Button>
                                    </>
                                )}
                            </Form>

                            {/* Une ligne grise disait les trois choses d'un coup
                                et ne s'en faisait lire aucune. Séparées,
                                cochées, elles se parcourent — et chacune reprend
                                un engagement que la page tient plus bas, aucune
                                n'en ajoute. */}
                            <ul
                                className="mt-10 flex animate-rise flex-wrap items-center justify-center gap-x-8 gap-y-4 text-sm font-medium lg:justify-start"
                                style={{ animationDelay: '280ms' }}
                            >
                                {[
                                    t('Gratuit, sans inscription'),
                                    t('Devis avant paiement'),
                                    `${t('Réponse')} ${t(contact.responseTime)}`,
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

                        <HeroShowcase
                            countries={stats.countries}
                            destinationCodes={countries
                                .slice(0, 3)
                                .map((country) => country.code)}
                            className="animate-scale-in"
                            style={{ animationDelay: '180ms' }}
                        />
                    </div>

                    {/* Les plateformes viennent maintenant avant le parcours, et
                        non après.

                        Rien n'est dit à leur sujet : les marques sont
                        l'argument, un visiteur reconnaît Shein et Amazon ou ne
                        les reconnaît pas, et aucune phrase à côté n'y change
                        quoi que ce soit. Elles répondent à « est-ce que ça
                        marche avec ce que j'utilise déjà ? » — question qui
                        vient avant « comment ça se passe ? », et à laquelle il
                        vaut mieux avoir répondu avant de raconter un parcours
                        que personne ne lira s'il ne le concerne pas. */}
                    <div
                        className={cn(SHELL, 'relative mt-28 animate-rise')}
                        style={{ animationDelay: '340ms' }}
                    >
                        <MarketplaceMarquee
                            marketplaces={MARKETPLACES}
                            logos={MARKETPLACE_LOGOS}
                            colors={MARKETPLACE_COLORS}
                            onSelect={showParcoursFor}
                        />
                    </div>

                    {/* Le parcours ferme le hero, et il a maintenant un titre.

                        Sans lui, on tombait sur un panneau muni de quatre
                        onglets sans savoir ce qu'on regardait — et un bloc qu'il
                        faut d'abord identifier est un bloc qu'on saute. Le titre
                        dit ce que le panneau va montrer, et le panneau le
                        montre. */}
                    <div className={cn(SHELL, 'relative mt-24 lg:mt-28')}>
                        <Reveal
                            from="blur"
                            className="mx-auto flex max-w-2xl flex-col items-center text-center"
                        >
                            <Eyebrow tone="gold">
                                {t('Du lien au colis')}
                            </Eyebrow>

                            <h2 className="mt-8 font-display text-title font-black">
                                {t('Ce qui se passe une fois le')}{' '}
                                <Accent tone="gold">{t('lien envoyé')}</Accent>
                            </h2>
                            <p className="mt-8 text-lead text-muted-foreground">
                                {t(
                                    "Quatre étapes, et vous n'en pilotez qu'une : la première. Le reste, c'est nous.",
                                )}
                            </p>
                        </Reveal>

                        {/* En `Reveal` et non en `animate-rise` posé d'emblée :
                            depuis que le bandeau des marques est passé devant,
                            ce panneau est sous la ligne de flottaison sur la
                            plupart des écrans, et une entrée jouée avant d'être
                            vue n'a jamais été jouée. */}
                        {/* Le porteur de la cible du défilement : `Reveal`
                            garde son ref pour lui. `scroll-mt-24` pour que la
                            carte se pose sous l'en-tête collant, pas
                            dessous. */}
                        <div ref={parcoursCard} className="scroll-mt-24">
                            <Reveal delay={80} className="mt-14">
                                <LinkToParcel
                                    key={demoMarketplace}
                                    marketplace={demoMarketplace}
                                />
                            </Reveal>
                        </div>

                        {/* ── La sortie du parcours ───────────────────────────
                            On venait de regarder les quatre étapes, on était au
                            plus près de vouloir essayer, et il n'y avait rien à
                            cliquer : il fallait remonter au champ ou aller
                            chercher la barre. Ce bouton ferme ce cul-de-sac.

                            Un seul, et pas les trois canaux de front. Les trois
                            ont déjà une section à eux plus bas, et la refaire
                            ici ne ferait que la déprécier — mais surtout, ce
                            bloc vient de passer quatorze secondes à dire « un
                            lien suffit, trois questions, pas un formulaire ».
                            Le terminer par « choisissez d'abord votre canal »,
                            c'est se contredire à l'instant précis où l'on vient
                            de convaincre.

                            Les trois canaux restent à un clic : c'est
                            `OrderMenu`, exactement l'objet que le header et le
                            bouton flottant déclenchent. Cohérent par
                            construction plutôt que par relecture. */}
                        <Reveal
                            delay={160}
                            className="mt-16 flex flex-col items-center text-center"
                        >
                            <p className="font-display text-subtitle font-extrabold">
                                {t('Prêt ? Envoyez votre premier lien.')}
                            </p>

                            <OrderMenu
                                chatHref={chat().url}
                                telegramUrl={telegramUrl}
                                whatsappUrl={whatsappUrl}
                                trigger={
                                    <Button
                                        size="lg"
                                        className="mt-7 h-14 rounded-2xl px-9 text-base shadow-lg shadow-primary/25 transition-shadow hover:shadow-xl hover:shadow-primary/30"
                                    >
                                        <ShoppingCart className="size-4" />
                                        {t('Commander maintenant')}
                                    </Button>
                                }
                            />

                            {/* Deux engagements que la page tient déjà, et
                                aucun de plus : celui du haut de page, et celui
                                que l'étape « Devis » vient de montrer. */}
                            <p className="mt-5 text-sm text-muted-foreground">
                                {t(
                                    "Gratuit, sans inscription — vous n'engagez rien avant d'avoir vu le devis.",
                                )}
                            </p>
                        </Reveal>
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
                    className={cn('relative overflow-hidden border-b', BAND)}
                >
                    {/* The same radial wash as the hero, at half its strength.
                        It is the whole of the depth here: the section's subject
                        is a network of thin blue lines, and anything heavier
                        behind them is something they have to fight. */}
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_55%_at_50%_0%,var(--color-primary)_0%,transparent_70%)] opacity-[0.06]"
                    />

                    {/* Le titre est centré et seul sur sa ligne. La photo qui
                        l'accompagnait est remontée dans le hero, et c'est la
                        carte qui illustre désormais cette section — ce qu'elle
                        faisait déjà bien mieux qu'une image posée à côté. */}
                    <Reveal
                        from="blur"
                        className={cn(
                            SHELL,
                            'relative flex max-w-3xl flex-col items-center text-center',
                        )}
                    >
                        <Eyebrow>{t('Le réseau')}</Eyebrow>

                        <h2 className="mt-8 font-display text-title font-black">
                            <Accent tone="blue">{t('Vos envies')}</Accent>{' '}
                            {t("voyagent jusqu'à vous.")}
                        </h2>

                        <p className="mt-8 text-lead text-muted-foreground">
                            {t(
                                "Shoprelle vous ouvre l'accès aux plus grandes plateformes d'achat et organise l'acheminement de vos commandes jusqu'à votre destination.",
                            )}
                        </p>
                    </Reveal>

                    {/* ── La carte et sa lecture, côte à côte ─────────────
                        La carte est un objet haut : posée seule au milieu de la
                        page, elle poussait la légende, les compteurs et la liste
                        des pays à la suite les uns des autres, et la section
                        prenait trois écrans pour dire une chose simple.

                        Elle tient donc la colonne de gauche, et tout ce qui la
                        lit en mots — la légende, les chiffres, les noms — tient
                        celle de droite, à la même hauteur. Rien n'a été retiré ;
                        ce qui était empilé est maintenant en regard.

                        Sous `lg`, les deux colonnes redeviennent une pile : à
                        cette largeur, une carte de la moitié de l'écran n'est
                        plus une carte. */}
                    <div
                        className={cn(
                            SHELL,
                            'relative mt-16 grid items-center gap-14 lg:mt-20 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)] lg:gap-20',
                        )}
                    >
                        {/* ── Le panneau de la carte ──────────────────────────
                            Le continent était posé à même la page, et une carte
                            en gris pâle sur un fond blanc ne se pose sur rien :
                            elle flotte. Un panneau lui donne un bord, donc une
                            place, et sépare la colonne du dessin de celle des
                            mots sans qu'un trait ait à le faire.

                            Deux couches et rien de plus : le fond du panneau
                            et la trame de points du site. Ni bordure, ni ombre
                            portée, ni halo — le panneau n'est pas une carte
                            posée sur la page, c'est un aplat de couleur dans
                            lequel le continent est dessiné. Un trait autour en
                            aurait refait un objet. La carte passe en `relative`
                            par-dessus. */}
                        <Reveal from="left" delay={100}>
                            <div className="relative isolate overflow-hidden rounded-[2rem] bg-muted/40 p-6 sm:p-8">
                                <div
                                    aria-hidden
                                    className="pointer-events-none absolute inset-0 bg-dots opacity-60"
                                />

                                <Suspense
                                    fallback={
                                        <div className="mx-auto aspect-[234/319] w-full max-w-md animate-pulse rounded-3xl bg-muted-foreground/10" />
                                    }
                                >
                                    <AfricaMap
                                        countries={countries}
                                        upcomingCountries={upcomingCountries}
                                        className="relative mx-auto w-full max-w-md"
                                    />
                                </Suspense>
                            </div>
                        </Reveal>

                        <Reveal
                            from="right"
                            delay={140}
                            className="flex flex-col gap-10"
                        >
                            {/* Le hub a quitté la légende avec la France : ce
                                que cette carte dit est où l'on livre, et d'où
                                part le colis est écrit en toutes lettres
                                ailleurs. */}
                            <ul className="flex flex-wrap items-center gap-x-8 gap-y-2 text-xs font-semibold">
                                <Legend swatch="bg-primary">
                                    {t('Livraison disponible')}
                                </Legend>
                                <Legend swatch="bg-primary/35">
                                    {t('Bientôt desservi')}
                                </Legend>
                                <Legend swatch="bg-muted-foreground/20">
                                    {t('Pas encore desservi')}
                                </Legend>
                            </ul>

                            {/* Les compteurs : la même chose que la carte, dite
                                en nombres. */}
                            {/* Autant de colonnes que de chiffres, jusqu'à
                                trois : deux d'entre eux n'existent que le jour
                                où il y a des colis expédiés et des demandes
                                livrées à compter, et une grille de trois
                                colonnes pour un seul chiffre laisse deux vides
                                que l'œil lit comme une donnée manquante. */}
                            <div
                                className={cn(
                                    'grid gap-10 border-t pt-10 sm:gap-8',
                                    figures.length > 1 && 'sm:grid-cols-3',
                                )}
                            >
                                {figures.map((figure) => (
                                    <div key={figure.label}>
                                        <Stat {...figure} />
                                    </div>
                                ))}
                            </div>

                            {/* Les destinations viennent de la configuration
                                que l'assistant applique, donc la page ne peut
                                pas promettre un pays que la conversation
                                refuserait ensuite. Elles sont nommées autant que
                                dessinées : les étiquettes portent leur nom sur
                                la carte, mais une liste est ce qu'un visiteur
                                parcourt et ce qu'un moteur de recherche lit. */}
                            <div className="flex flex-col gap-8 border-t pt-10">
                                <div>
                                    <p className="flex items-center gap-2 text-sm font-semibold">
                                        <span className="size-2 rounded-full bg-primary" />
                                        {t("Nous livrons aujourd'hui")}
                                    </p>
                                    <ul className="mt-4 flex flex-wrap gap-2.5">
                                        {countries.map((country) => (
                                            <li
                                                key={country.code}
                                                className="flex items-center gap-2 rounded-full border bg-card px-4 py-2 text-sm font-semibold shadow-sm"
                                            >
                                                {/* Le drapeau porte plus que sa
                                                    taille de texte : c'est lui
                                                    qu'on reconnaît avant de lire
                                                    le nom du pays. */}
                                                <span
                                                    aria-hidden
                                                    className="text-xl leading-none"
                                                >
                                                    {flagFor(country.code)}
                                                </span>
                                                {t(country.name)}
                                            </li>
                                        ))}
                                    </ul>
                                </div>

                                {upcomingCountries.length > 0 && (
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            {t('Bientôt')}
                                        </p>
                                        {/* En pointillés plutôt qu'en plein,
                                            comme les pays pâles de la carte :
                                            ils sont annoncés, pas ouverts, et
                                            l'assistant les refuse tant que la
                                            configuration ne dit pas autre
                                            chose. */}
                                        <ul className="mt-4 flex flex-wrap gap-2.5">
                                            {upcomingCountries.map(
                                                (country) => (
                                                    <li
                                                        key={country.code}
                                                        className="flex items-center gap-2 rounded-full border border-dashed px-4 py-2 text-sm font-medium text-muted-foreground"
                                                    >
                                                        <span
                                                            aria-hidden
                                                            className="text-xl leading-none"
                                                        >
                                                            {flagFor(
                                                                country.code,
                                                            )}
                                                        </span>
                                                        {t(country.name)}
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        </Reveal>
                    </div>

                    <div className={cn(SHELL, 'relative')}>
                        {/* Two columns, and the same split the rest of the page
                            uses: the heading holds the narrow side and the
                            content takes the width. Stacked one per line rather
                            than side by side — three columns forced each
                            description into four ragged lines, and a promise
                            that has to be deciphered is not reassuring. Given a
                            full line each they read at a glance. */}
                        <div className="mt-20 grid gap-12 border-t pt-20 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-20">
                            <Reveal
                                from="left"
                                className="lg:sticky lg:top-32 lg:self-start"
                            >
                                {/* Pas de sur-titre ici : ce bloc est la suite
                                    de la section qui le porte, pas un chapitre
                                    de plus. */}
                                <h3 className="font-display text-subtitle font-extrabold">
                                    {t(
                                        'Le même parcours, quelle que soit la destination.',
                                    )}
                                </h3>
                                <p className="mt-4 max-w-sm text-body text-muted-foreground">
                                    {t(
                                        "Chaque commande suit les mêmes étapes, du panier français jusqu'à votre ville.",
                                    )}
                                </p>
                            </Reveal>

                            <ul className="flex flex-col">
                                {ASSURANCES.map((assurance, index) => (
                                    <Reveal
                                        as="li"
                                        from="right"
                                        key={assurance.title}
                                        delay={index * 80}
                                        className="flex gap-6 border-b py-7 first:pt-0 last:border-b-0 last:pb-0"
                                    >
                                        <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-accent-brand text-accent-brand-foreground">
                                            <assurance.icon className="size-5" />
                                        </span>

                                        <div className="pt-1">
                                            <p className="font-display text-lg font-extrabold">
                                                {t(assurance.title)}
                                            </p>
                                            <p className="mt-2 text-body text-muted-foreground">
                                                {t(assurance.description)}
                                            </p>
                                        </div>
                                    </Reveal>
                                ))}
                            </ul>
                        </div>
                    </div>
                </section>

                {/* ── Ce qu'on commande ──────────────────────────────────
                    Après « oui, on livre chez vous » et avant « voilà comment
                    ça se passe » : le visiteur vient d'apprendre que le service
                    l'atteint, et la question suivante est ce qu'il peut en
                    faire. Le service accepte n'importe quel lien de n'importe
                    quelle plateforme — une promesse aussi large ne donne aucune
                    idée, et quelques produits en donnent une.

                    La section disparaît tant qu'aucun produit n'est mis en
                    avant depuis le back-office. Une vitrine vide dit exactement
                    le contraire de ce qu'elle vient dire. */}
                {products.length > 0 && (
                    <section
                        className={cn(
                            'relative overflow-hidden border-b',
                            BAND,
                        )}
                    >
                        {/* Les hachures : la même trame que la section de
                            l'assistant, l'autre endroit où l'on choisit. */}
                        <div
                            aria-hidden
                            className="pointer-events-none absolute inset-0 bg-diagonals [mask-image:linear-gradient(to_bottom,black,transparent_70%)] opacity-40"
                        />

                        <div className={cn(SHELL, 'relative')}>
                            <Reveal from="blur" className="max-w-2xl">
                                <Eyebrow tone="gold">
                                    {t('La sélection')}
                                </Eyebrow>

                                <h2 className="mt-8 font-display text-title font-black">
                                    {t("Ce qu'on nous commande")}{' '}
                                    <Accent tone="gold">
                                        {t('le plus souvent')}
                                    </Accent>
                                </h2>

                                <p className="mt-8 text-lead text-muted-foreground">
                                    {t(
                                        "Quelques exemples, avec leur prix relevé sur la plateforme. Vous n'êtes pas limité à cette liste : n'importe quel lien fait l'affaire.",
                                    )}
                                </p>
                            </Reveal>

                            <ProductShowcase
                                products={products}
                                className="mt-14"
                            />

                            {/* Dit une fois, en bas de la grille : les prix
                                sont relevés à la main et le transport n'y est
                                pas. Le seul montant qui engage est celui du
                                devis, et cette page n'a pas le droit de laisser
                                croire autre chose. */}
                            <p className="mt-12 text-sm text-muted-foreground">
                                {t(
                                    'Prix indicatifs relevés sur les plateformes, hors transport. Votre devis les recalcule au cours du jour.',
                                )}
                            </p>
                        </div>
                    </section>
                )}

                <section
                    id="comment-ca-marche"
                    className={cn('border-b section-brand', BAND)}
                >
                    {/* Le même partage en deux que les autres sections — le
                        titre sur la colonne étroite, le contenu sur la large —
                        mais retourné : ici le titre est à droite.

                        La frise passe donc à gauche, c'est-à-dire du côté d'où
                        l'on commence à lire, ce qui est sa place : elle est la
                        chose numérotée de la section. Le titre reste premier
                        dans le document — c'est lui qu'un lecteur d'écran doit
                        rencontrer d'abord — et ne change que de colonne, par
                        `order`. Sous `lg`, la pile revient à l'ordre du
                        document et le titre repasse devant. */}
                    <div
                        className={cn(
                            SHELL,
                            'grid gap-14 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:gap-20',
                        )}
                    >
                        {/* Cette colonne n'est plus collante. Elle l'était
                            quand elle ne portait que deux phrases en face d'une
                            frise haute ; avec la capture en dessous elle
                            dépasse la hauteur d'écran, et un bloc collant plus
                            haut que la fenêtre a un bas que l'on n'atteint
                            jamais. */}
                        <div className="lg:order-2">
                            <Eyebrow>{t('Comment ça marche')}</Eyebrow>

                            <h2 className="mt-8 font-display text-title font-black">
                                {t("Trois étapes, et c'est tout")}
                            </h2>
                            <p className="mt-8 max-w-sm text-lead text-muted-foreground">
                                {t(
                                    'Pas de formulaire interminable, pas de compte : une conversation.',
                                )}
                            </p>

                            {/* La conversation elle-même, en dessous.

                                C'était le seul endroit vide de la page : une
                                colonne portant deux phrases, en face d'une frise
                                qui tient toute la hauteur. Et c'était surtout la
                                section qui promet « une conversation » sans en
                                montrer une seule. Une capture d'écran répond aux
                                deux, et elle ne s'affiche que si le fichier
                                existe — voir `lib/photos.ts`. */}
                            <ConversationShot className="mt-12 lg:mx-0" />
                        </div>

                        {/* A rail rather than three cards: the steps happen in
                            an order, and a timeline says so where a grid does
                            not. */}
                        <ol className="lg:order-1">
                            {STEPS.map((step, index) => (
                                <Reveal
                                    as="li"
                                    from="right"
                                    key={step.title}
                                    delay={index * 110}
                                    className="group grid grid-cols-[auto_1fr] gap-x-6 sm:gap-x-9"
                                >
                                    <div className="flex flex-col items-center">
                                        <span className="flex size-12 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                            <step.icon className="size-5" />
                                        </span>

                                        {/* Connector, stopped at the last node. */}
                                        <span
                                            aria-hidden
                                            className="w-px flex-1 bg-primary/30 group-last:hidden"
                                        />
                                    </div>

                                    <div className="pt-1.5 pb-14 group-last:pb-0">
                                        <span className="inline-flex items-center rounded-lg bg-accent-brand px-2.5 py-1 text-xs font-bold tracking-wide text-accent-brand-foreground tabular-nums">
                                            {t('Étape')} 0{index + 1}
                                        </span>

                                        <h3 className="mt-5 font-display text-subtitle font-extrabold">
                                            {t(step.title)}
                                        </h3>
                                        <p className="mt-3 max-w-xl text-body text-muted-foreground">
                                            {t(step.description)}
                                        </p>
                                    </div>
                                </Reveal>
                            ))}
                        </ol>
                    </div>
                </section>

                {/* Les mêmes trois étapes, mais photographiées.

                    Elle est ici et pas ailleurs : la section juste au-dessus
                    vient de dire ce qui se passe, celle-ci montre que ça se
                    passe. Séparées par une autre section, les deux ne se
                    seraient jamais rencontrées.

                    Toute la bande disparaît tant qu'aucune photo n'est
                    déposée — voir `lib/photos.ts`. */}
                {SHOW_PROCESS_PHOTOS && (
                    <section
                        className={cn(
                            'relative overflow-hidden border-b',
                            BAND,
                        )}
                    >
                        {/* Le semis de points : la trame d'imprimeur, derrière
                            la seule bande de la page qui est faite de
                            photographies. */}
                        <div
                            aria-hidden
                            className="pointer-events-none absolute inset-0 bg-dots [mask-image:radial-gradient(75%_55%_at_50%_0%,black,transparent)] opacity-50"
                        />

                        <div
                            className={cn(
                                SHELL,
                                'relative flex flex-col items-center',
                            )}
                        >
                            <Reveal
                                from="blur"
                                className="flex max-w-2xl flex-col items-center text-center"
                            >
                                <Eyebrow tone="gold">{t('En vrai')}</Eyebrow>

                                <h2 className="mt-8 font-display text-title font-black">
                                    {t('Ce que ça donne,')}{' '}
                                    <Accent tone="gold">
                                        {t('concrètement')}
                                    </Accent>
                                </h2>
                                <p className="mt-8 text-lead text-muted-foreground">
                                    {t(
                                        "Les trois mêmes étapes, photographiées. Rien de mis en scène : c'est le travail tel qu'il se fait, de la commande passée en France au colis remis chez vous.",
                                    )}
                                </p>
                            </Reveal>

                            <ProcessPhotos className="mt-16 w-full" />
                        </div>
                    </section>
                )}

                {/* The assistant is the product, so it gets a section of its own
                    rather than a line in the steps: the visitor's real question
                    is where the conversation happens, and the answer is
                    "wherever you already are".

                    Titre centré, puis un bento : le chat web en carte majeure,
                    les deux relais empilés à côté. Les canaux ne sont pas des
                    égaux — le chat web est le seul toujours disponible — et la
                    grille le dit par la place, pas par une phrase. */}
                <section
                    id="assistant"
                    className={cn('relative overflow-hidden border-b', BAND)}
                >
                    {/* Les hachures : la texture d'une bulle de message,
                        derrière la section qui raconte une conversation. */}
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0 bg-diagonals [mask-image:linear-gradient(to_bottom,black,transparent_65%)] opacity-40"
                    />

                    <div
                        className={cn(
                            SHELL,
                            'relative flex flex-col items-center',
                        )}
                    >
                        <Reveal
                            from="blur"
                            className="flex max-w-2xl flex-col items-center text-center"
                        >
                            <Eyebrow>{t("L'assistant")}</Eyebrow>

                            <h2 className="mt-8 font-display text-title font-black">
                                {t('Commandez simplement')}{' '}
                                <Accent tone="blue">{t('en discutant')}</Accent>
                            </h2>
                            <p className="mt-8 text-lead text-muted-foreground">
                                {t(
                                    'Plus besoin de remplir des formulaires compliqués. Envoyez un lien à notre assistant et laissez-vous guider étape par étape.',
                                )}
                            </p>
                        </Reveal>

                        {/* ── Un bento, pas trois cartes de front ─────────────
                            Les trois canaux ne sont pas égaux : le chat web est
                            toujours là, sans rien installer — c'est le produit.
                            Les deux autres n'existent que si quelqu'un les a
                            branchés. La grille le dit par la place qu'elle
                            donne, comme celle des engagements plus bas : la
                            carte majeure à gauche, les deux relais empilés à
                            droite, à la même hauteur totale. */}
                        <div className="mt-16 grid w-full gap-6 lg:grid-cols-[7fr_5fr]">
                            <Reveal className="h-full">
                                <ChannelCard
                                    icon={MessagesSquare}
                                    name={t('Chat web')}
                                    badge={t('Disponible maintenant')}
                                    description={t(
                                        'Ici même, dans votre navigateur. Rien à installer, aucun compte à créer : la conversation commence tout de suite.',
                                    )}
                                    href={chat().url}
                                    featured
                                >
                                    {/* L'aperçu : les trois premières répliques
                                        du vrai parcours — le lien collé, la
                                        couleur demandée — pas une conversation
                                        inventée. Décoratif, donc muet pour un
                                        lecteur d'écran : le texte de la carte
                                        dit déjà tout. */}
                                    <div
                                        aria-hidden
                                        className="mt-8 space-y-2.5 rounded-2xl bg-muted/50 bg-dots-chat p-4 sm:p-5"
                                    >
                                        <AssistantConversation />
                                    </div>
                                </ChannelCard>
                            </Reveal>

                            <div className="grid gap-6">
                                <Reveal delay={120} className="h-full">
                                    <ChannelCard
                                        icon={Send}
                                        name="Telegram"
                                        badge={
                                            telegramUrl
                                                ? t('Disponible maintenant')
                                                : t('Bientôt disponible')
                                        }
                                        description={t(
                                            "Le même assistant, dans votre messagerie. Vous gardez l'historique de vos demandes dans une conversation que vous n'avez pas à rouvrir.",
                                        )}
                                        href={telegramUrl ?? undefined}
                                        external
                                    />
                                </Reveal>

                                {/* WhatsApp n'est pas l'assistant, et la carte
                                    ne doit jamais le laisser croire : derrière
                                    ce lien il y a une personne, pas le robot.
                                    C'est la même promesse que le reste de la
                                    page — « une personne lit chaque demande » —
                                    mais elle se tient dans un fil que le client
                                    garde. */}
                                <Reveal delay={240} className="h-full">
                                    <ChannelCard
                                        icon={WhatsAppIcon}
                                        brand={WHATSAPP_GREEN}
                                        name="WhatsApp"
                                        badge={
                                            whatsappUrl
                                                ? t('Disponible maintenant')
                                                : t('Bientôt disponible')
                                        }
                                        description={t(
                                            "Écrivez-nous sur WhatsApp : une personne vous répond et vous accompagne jusqu'à la commande.",
                                        )}
                                        href={whatsappUrl ?? undefined}
                                        external
                                    />
                                </Reveal>
                            </div>
                        </div>
                    </div>
                </section>

                {/* The story, and the only place on the page that names the
                    problem rather than the service. It sits after the mechanism
                    on purpose: a visitor who has seen how it works is the one
                    who wants to know why it had to exist. */}
                <section
                    id="a-propos"
                    className={cn(
                        'relative overflow-hidden border-b bg-surface-alt',
                        BAND,
                    )}
                >
                    {/* Les cercles concentriques, partant du bord où commence
                        le récit : quelque chose qui part d'un point et se
                        propage, derrière la section qui dit pourquoi le service
                        existe. */}
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0 bg-rings [mask-image:radial-gradient(60%_70%_at_0%_50%,black,transparent)] opacity-60"
                    />

                    <div
                        className={cn(
                            SHELL,
                            'relative grid gap-14 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-20',
                        )}
                    >
                        <Reveal
                            from="left"
                            className="lg:sticky lg:top-32 lg:self-start"
                        >
                            <Eyebrow tone="gold">{t('À propos')}</Eyebrow>

                            <h2 className="mt-8 font-display text-title font-black">
                                {t('Pourquoi')}{' '}
                                <Accent tone="gold">Shoprelle</Accent>{' '}
                                {t('existe ?')}
                            </h2>
                            <p className="mt-7 text-lead text-muted-foreground">
                                {t(
                                    "De nombreux produits sont difficiles à obtenir dans certaines régions du monde. Shoprelle simplifie l'accès aux grandes plateformes internationales en prenant en charge l'achat et la livraison pour vous.",
                                )}
                            </p>
                        </Reveal>

                        {/* Two columns rather than another list of promises:
                            the section next door already says what we do well,
                            and what is missing from the page is what goes wrong
                            without it. Every line on the right is something
                            claimed elsewhere on this page, not a new promise.

                            Asymétriques, et non à parts égales : le problème et
                            sa réponse ne pèsent pas pareil. La carte « Sans »
                            est étroite, en pointillés, presque un aparté ; la
                            carte « Avec » est large, pleine, ombrée — c'est
                            elle qu'on est venu lire, et la grille le dit avant
                            la première ligne. */}
                        <div className="grid gap-6 sm:grid-cols-[5fr_7fr]">
                            <Reveal
                                from="right"
                                className="rounded-3xl border border-dashed p-7 sm:p-8"
                            >
                                <p className="font-display text-eyebrow font-extrabold text-muted-foreground uppercase">
                                    {t('Sans Shoprelle')}
                                </p>
                                <ul className="mt-6 space-y-4">
                                    {OBSTACLES.map((obstacle) => (
                                        <li
                                            key={obstacle}
                                            className="flex gap-3 text-muted-foreground"
                                        >
                                            <X className="mt-1 size-4 shrink-0" />
                                            {t(obstacle)}
                                        </li>
                                    ))}
                                </ul>
                            </Reveal>

                            <Reveal
                                from="right"
                                delay={100}
                                className="rounded-3xl border bg-card p-7 shadow-md sm:p-9"
                            >
                                {/* L'or du chapitre, repris sur le libellé : le
                                    même ton que le sur-titre de la section. */}
                                <p className="font-display text-eyebrow font-extrabold text-accent-brand-ink uppercase">
                                    {t('Avec Shoprelle')}
                                </p>
                                <ul className="mt-6 space-y-4">
                                    {ANSWERS.map((answer) => (
                                        <li key={answer} className="flex gap-3">
                                            <Check className="mt-1 size-4 shrink-0 text-success" />
                                            {t(answer)}
                                        </li>
                                    ))}
                                </ul>
                            </Reveal>
                        </div>
                    </div>
                </section>

                {/* ── Les engagements, en grille bento ───────────────────────
                    Cinq cartes sur six colonnes : la première en occupe quatre,
                    les quatre autres deux chacune. Les deux rangées tombent
                    juste, et la carte large est celle qui porte l'engagement
                    qui compte — « rien sans votre accord ». Une grille où
                    toutes les cases font la même taille ne dit rien de ce qui
                    est important ; celle-ci le dit par la place qu'elle donne. */}
                <section
                    className={cn('relative overflow-hidden border-b', BAND)}
                >
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0 bg-grid [mask-image:radial-gradient(70%_60%_at_50%_0%,black,transparent)] opacity-40"
                    />

                    <div className={cn(SHELL, 'relative')}>
                        <Reveal from="blur" className="max-w-2xl">
                            <Eyebrow>{t('Nos engagements')}</Eyebrow>

                            <h2 className="mt-8 font-display text-title font-black">
                                {t('Une solution simple, transparente et')}{' '}
                                <Accent tone="blue">{t('fiable')}</Accent>
                            </h2>
                        </Reveal>

                        <div className="mt-20 grid gap-8 sm:grid-cols-2 lg:grid-cols-6">
                            {PROMISES.map((promise, index) => (
                                <Reveal
                                    key={promise.title}
                                    delay={index * 80}
                                    className={cn(
                                        'card-lift rounded-3xl border bg-card p-8 shadow-sm sm:p-9',
                                        index === 0
                                            ? 'sm:col-span-2 lg:col-span-4'
                                            : 'lg:col-span-2',
                                    )}
                                >
                                    {/* Le bleu, et non l'or : depuis que les
                                        titres portent l'or, cinq pastilles
                                        dorées de plus au même endroit faisaient
                                        de cette grille la seule chose qu'on
                                        voyait de la page. */}
                                    <span
                                        className={cn(
                                            'flex items-center justify-center rounded-2xl bg-primary text-primary-foreground',
                                            index === 0 ? 'size-14' : 'size-12',
                                        )}
                                    >
                                        <promise.icon
                                            className={cn(
                                                index === 0
                                                    ? 'size-7'
                                                    : 'size-5',
                                            )}
                                        />
                                    </span>

                                    <h3
                                        className={cn(
                                            'mt-8 font-display font-extrabold',
                                            index === 0
                                                ? 'text-subtitle'
                                                : 'text-lg',
                                        )}
                                    >
                                        {t(promise.title)}
                                    </h3>
                                    <p className="mt-6 max-w-md text-body text-muted-foreground">
                                        {t(promise.description)}
                                    </p>
                                </Reveal>
                            ))}
                        </div>

                        {/* Par quoi on paie, dit ici et pas au moment de payer.

                            « Est-ce que je pourrai régler avec mon MoMo ? » se
                            demande avant de commander, pas après : quelqu'un
                            qui n'a ni carte ni compte bancaire — c'est-à-dire
                            presque tout le monde ici — a besoin de le savoir
                            pour se sentir concerné par le reste de la page.

                            Les coordonnées, elles, n'y sont pas : elles sont
                            données au client qui a accepté son devis, sur sa
                            propre page. Un numéro de collecte affiché en clair
                            sur une page publique est une invitation à s'en
                            servir au nom du service.

                            Tous les moyens sont nommés, y compris ceux dont
                            aucun compte n'est encore renseigné : cette liste
                            informe, elle ne promet pas un numéro.

                            Les trois marques y sont maintenant elles-mêmes,
                            sur leur tuile blanche, plutôt qu'une pastille de
                            couleur suivie de leur nom : « MoMo » se reconnaît
                            d'un coup d'œil par ceux qui s'en servent tous les
                            jours, et c'est précisément à eux que cette ligne
                            s'adresse. Un moyen sans marque déposée ici garde
                            l'ancien affichage — voir `payment-logos.tsx`. */}
                        {/* Une bande à deux côtés, plus une bulle centrée : le
                            libellé flottait au milieu des logos comme une
                            étiquette égarée. À gauche ce qu'on affirme — et
                            rien qui ne soit déjà promis ailleurs sur la page —
                            à droite les marques qui le prouvent, chacune sur sa
                            tuile. C'est la grammaire du bandeau des
                            marketplaces, appliquée au paiement. */}
                        {paymentMethods.length > 0 && (
                            <Reveal
                                delay={160}
                                className="mt-10 flex flex-col items-start gap-6 rounded-3xl border bg-card p-7 shadow-sm sm:p-8 lg:flex-row lg:items-center lg:justify-between lg:gap-10"
                            >
                                <div>
                                    <p className="font-display text-lg font-extrabold">
                                        {t('Vous réglez par')}
                                    </p>
                                    <p className="mt-1.5 text-sm text-muted-foreground">
                                        {t(
                                            "En monnaie locale, une seule fois — et jamais avant d'avoir validé le devis.",
                                        )}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-x-4 gap-y-3">
                                    {paymentMethods.map((method) => {
                                        const logo = PAYMENT_LOGOS[method.name];

                                        if (!logo) {
                                            return (
                                                <span
                                                    key={method.name}
                                                    className="flex items-center gap-2 font-display text-sm font-extrabold"
                                                >
                                                    <span
                                                        aria-hidden
                                                        style={{
                                                            backgroundColor:
                                                                method.colour,
                                                        }}
                                                        className="size-4 rounded-md"
                                                    />
                                                    {method.name}
                                                </span>
                                            );
                                        }

                                        return (
                                            <span
                                                key={method.name}
                                                className="flex h-14 items-center rounded-xl border border-black/[0.07] bg-surface-tile px-4 shadow-sm"
                                            >
                                                {/* La hauteur est donnée, la
                                                largeur suit les proportions de
                                                l'artwork : trois marques de
                                                gabarits différents alignées sur
                                                leur hauteur se lisent comme une
                                                série. */}
                                                <img
                                                    src={logo.src}
                                                    alt={method.name}
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        aspectRatio: logo.ratio,
                                                    }}
                                                    className="h-8 w-auto"
                                                />
                                            </span>
                                        );
                                    })}
                                </div>
                            </Reveal>
                        )}
                    </div>
                </section>

                {/* Les avis n'apparaissent que s'il y en a. Une section de
                    témoignages vide, ou remplie d'exemples, dit exactement le
                    contraire de ce qu'elle vient dire. */}
                {reviews.length > 0 && (
                    <section className={cn('border-b bg-surface-alt', BAND)}>
                        <div
                            className={cn(
                                SHELL,
                                'grid gap-14 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-20',
                            )}
                        >
                            <Reveal from="left">
                                <Eyebrow tone="gold">
                                    {t("Ils l'ont fait")}
                                </Eyebrow>

                                <h2 className="mt-8 font-display text-title font-black">
                                    {t('Ils ont')}{' '}
                                    <Accent tone="gold">{t('commandé')}</Accent>{' '}
                                    {t('avec nous')}
                                </h2>
                                <p className="mt-8 max-w-sm text-lead text-muted-foreground">
                                    {t(
                                        "Des avis laissés à la fin d'une conversation, par des clients qui ont reçu leur colis.",
                                    )}
                                </p>
                            </Reveal>

                            <Reveal from="right" delay={120}>
                                <ReviewCarousel reviews={reviews} />
                            </Reveal>
                        </div>
                    </section>
                )}

                <section className={cn('border-b', BAND)}>
                    <div
                        className={cn(
                            SHELL,
                            'grid gap-14 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-20',
                        )}
                    >
                        <div className="lg:sticky lg:top-32 lg:self-start">
                            <Eyebrow tone="gold">
                                {t('Questions fréquentes')}
                            </Eyebrow>

                            <h2 className="mt-8 font-display text-title font-black">
                                {t("Ce qu'on nous demande")}{' '}
                                <Accent tone="gold">{t('le plus')}</Accent>
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
                                    {/* Le survol est en or, pas en bleu : la
                                        section est un chapitre or — sur-titre
                                        et mot du titre — et un bleu au survol
                                        y parlait une autre langue. Même coût
                                        assumé que les mots `Accent` : cet or
                                        se voit plus qu'il ne se lit sur fond
                                        clair, et la question a déjà été lue
                                        avant d'être survolée. */}
                                    <summary className="flex cursor-pointer list-none items-center justify-between gap-6 py-7 font-display text-lg font-extrabold transition-colors hover:text-accent-brand-ink focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring [&::-webkit-details-marker]:hidden">
                                        {t(item.question)}

                                        <ChevronDown
                                            aria-hidden
                                            className="size-5 shrink-0 text-muted-foreground transition-[transform,color] duration-300 group-open:rotate-180 group-hover:text-accent-brand-ink"
                                        />
                                    </summary>

                                    <p className="max-w-2xl pb-9 text-body text-muted-foreground">
                                        {t(item.answer)}
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
                {/* Sur le fond de la page, sans bande : la section d'avant est
                    crème et celle d'après est l'or plein de l'appel final. Une
                    troisième couleur entre les deux ferait trois bandes à la
                    suite, et plus aucune ne se distinguerait. */}
                <section id="contact" className={cn('border-b', BAND)}>
                    <div
                        className={cn(
                            SHELL,
                            'grid gap-14 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-20',
                        )}
                    >
                        <Reveal from="left">
                            <Eyebrow>{t('Contact')}</Eyebrow>

                            <h2 className="mt-8 font-display text-title font-black">
                                {t('Nous')}{' '}
                                <Accent tone="blue">{t('contacter')}</Accent>
                            </h2>
                            <p className="mt-8 max-w-md text-body text-muted-foreground">
                                {t(
                                    'Une question avant de commander, un partenariat, ou simplement une hésitation : écrivez-nous, on répond',
                                )}{' '}
                                {t(contact.responseTime)}.
                            </p>
                        </Reveal>

                        {/* Deux tuiles au lieu d'une liste de liens : les deux
                            portes ne mènent pas au même endroit — écrire pour
                            une question neuve, ou retrouver une demande qui
                            existe déjà — et deux cartes égales disent « choisis
                            ta porte » là où une pile disait « lis tout ». La
                            même coquille que les cartes des canaux, pour que la
                            page n'ait qu'une seule espèce de carte cliquable. */}
                        <Reveal
                            from="right"
                            delay={140}
                            className="grid gap-6 sm:grid-cols-2"
                        >
                            <a
                                href={`mailto:${contact.email}`}
                                className="group flex card-lift flex-col rounded-3xl border bg-card p-7 shadow-sm sm:p-8"
                            >
                                <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
                                    <Mail className="size-5" />
                                </span>

                                <p className="mt-6 font-display text-lg font-extrabold">
                                    {t('Par email')}
                                </p>
                                {/* `break-all` : une adresse ne se coupe pas
                                    sur une espace, et une tuile de téléphone
                                    est plus étroite qu'une adresse longue. */}
                                <p className="mt-2 text-body break-all text-muted-foreground">
                                    {contact.email}
                                </p>

                                <p className="mt-auto inline-flex items-center gap-1.5 pt-8 text-sm font-semibold text-primary">
                                    {t('Écrire')}
                                    <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                                </p>
                            </a>

                            <Link
                                href={chat()}
                                className="group flex card-lift flex-col rounded-3xl border bg-card p-7 shadow-sm sm:p-8"
                            >
                                <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
                                    <MessagesSquare className="size-5" />
                                </span>

                                <p className="mt-6 font-display text-lg font-extrabold">
                                    {t('Une demande en cours ?')}
                                </p>
                                <p className="mt-2 text-body text-muted-foreground">
                                    {t(
                                        "L'assistant la retrouve avec votre référence et vous donne son statut tout de suite.",
                                    )}
                                </p>

                                <p className="mt-auto inline-flex items-center gap-1.5 pt-8 text-sm font-semibold text-primary">
                                    {t('Suivre ma demande')}
                                    <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                                </p>
                            </Link>
                        </Reveal>
                    </div>
                </section>

                {/* The last thing on the page is the only thing on its screen:
                    one sentence, one button, and the brand gold behind both. */}
                <section className="section-accent">
                    <div
                        className={cn(
                            SHELL,
                            'max-w-5xl py-32 text-center lg:py-44',
                        )}
                    >
                        <Reveal
                            as="h2"
                            from="blur"
                            className="font-display text-display font-black"
                        >
                            {t(
                                'Le produit que vous cherchez est à un lien de distance.',
                            )}
                        </Reveal>

                        <Button
                            size="lg"
                            asChild
                            className="mt-14 h-16 rounded-2xl px-10 text-base shadow-xl shadow-black/10"
                        >
                            <Link href={chat()}>
                                {t('Envoyer un lien')}
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    </div>
                </section>
            </main>

            <footer className="border-t">
                <div
                    className={cn(
                        SHELL,
                        'grid gap-12 py-20 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_repeat(3,minmax(0,1fr))] lg:gap-16',
                    )}
                >
                    <div>
                        <span className="flex items-center gap-2.5 font-display text-lg font-extrabold tracking-tight">
                            <span className="flex size-9 items-center justify-center rounded-xl bg-accent-brand">
                                <AppLogoIcon className="size-4 text-accent-brand-foreground" />
                            </span>
                            Shoprelle
                        </span>

                        <p className="mt-6 font-display text-subtitle font-extrabold">
                            {t('Un lien. Une commande. Une livraison.')}
                        </p>

                        <p className="mt-5 max-w-sm text-muted-foreground">
                            {t(
                                "Shoprelle simplifie vos achats en ligne. Envoyez un lien depuis vos plateformes préférées, nous nous chargeons de l'achat, du suivi et de la livraison jusqu'à votre destination.",
                            )}
                        </p>

                        {socials.length > 0 && (
                            <ul className="mt-8 flex items-center gap-2.5">
                                {socials.map((network) => {
                                    const Icon = SOCIAL_ICONS[network.key];

                                    return (
                                        <li key={network.key}>
                                            <a
                                                href={network.href}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                aria-label={network.label}
                                                className="flex size-11 items-center justify-center rounded-full border text-muted-foreground transition-colors hover:border-primary/40 hover:bg-accent hover:text-primary"
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
                    <FooterColumn title={t('Le site')}>
                        {NAVIGATION.map((item) => (
                            <li key={item.href}>
                                <a
                                    href={item.href}
                                    className="transition-colors hover:text-foreground"
                                >
                                    {t(item.label)}
                                </a>
                            </li>
                        ))}
                    </FooterColumn>

                    <FooterColumn title={t('Commander')}>
                        <li>
                            <Link
                                href={chat()}
                                className="transition-colors hover:text-foreground"
                            >
                                {t('Créer une demande')}
                            </Link>
                        </li>
                        <li>
                            <Link
                                href={chat()}
                                className="transition-colors hover:text-foreground"
                            >
                                {t('Suivre ma demande')}
                            </Link>
                        </li>
                        {/* Deux portes, et elles n'ouvrent pas sur la même
                            chose : le suivi ci-dessus demande une référence et
                            répond sur une demande ; celle-ci demande le code
                            d'accès et rend tout l'historique, devis compris. */}
                        <li>
                            <Link
                                href={orders()}
                                className="transition-colors hover:text-foreground"
                            >
                                {t('Mes demandes et devis')}
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
                                    {t('Sur Telegram')}
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
                                    {t('Sur WhatsApp')}
                                </a>
                            </li>
                        )}
                    </FooterColumn>

                    <FooterColumn title={t('Contact')}>
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
                                {t('Espace équipe')}
                            </Link>
                        </li>
                    </FooterColumn>
                </div>

                <div
                    className={cn(
                        SHELL,
                        'flex flex-wrap items-center justify-between gap-x-6 gap-y-4 border-t py-7',
                    )}
                >
                    <p className="text-sm text-muted-foreground">
                        © {new Date().getFullYear()} Shoprelle.{' '}
                        {t('Tous droits réservés.')}
                    </p>

                    <div className="flex flex-wrap items-center gap-x-6 gap-y-4">
                        <nav
                            className="flex gap-5 text-sm text-muted-foreground"
                            aria-label={t('Pages légales')}
                        >
                            <Link
                                href={legalMentions()}
                                className="transition-colors hover:text-foreground"
                            >
                                {t('Mentions légales')}
                            </Link>
                            <Link
                                href={legalPrivacy()}
                                className="transition-colors hover:text-foreground"
                            >
                                {t('Confidentialité')}
                            </Link>
                        </nav>

                        {/* Tout à droite de la barre, comme demandé : la
                            langue est un réglage du site, et le pied de page
                            est l'endroit où on va la chercher. */}
                        <LocaleSwitcher />
                    </div>
                </div>

                {/* Le retour en haut, juste au-dessus du wordmark : on vient
                    de finir la page, c'est ici que remonter se propose. Les
                    marges se compensent — remonter le bouton ne fait pas
                    bouger le wordmark sous lui. */}
                <ScrollToTop className="-mt-2 mb-3" />

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

import { Form, Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    CheckCircle2,
    ExternalLink,
    History,
    MessageSquare,
    ReceiptText,
    Wallet,
    X,
} from 'lucide-react';
import { useState } from 'react';

import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { CustomerLayout } from '@/layouts/customer-layout';
import { cn, formatAmount, formatDate } from '@/lib/utils';
import { contact } from '@/routes/chat';
import { index } from '@/routes/orders';
import { accept, decline } from '@/routes/orders/quote';

type Item = {
    id: number;
    name: string;
    marketplace_label: string;
    product_url: string;
    quantity: number;
    color: string | null;
    size: string | null;
    /** Null while the request is still being priced. */
    quoted_amount: string | null;
    /** Les captures que le client a jointes à ce produit. */
    attachments: { id: number; url: string }[];
};

type Props = {
    request: {
        reference: string;
        status: string;
        status_label: string;
        status_color: string;
        /** Le devis attend une réponse : accepter ou refuser. */
        awaits_decision: boolean;
        created_at: string | null;
        destination: string;
        /** Chaque changement de statut, du plus ancien au plus récent. */
        timeline: {
            id: number;
            label: string;
            color: string;
            at: string | null;
        }[];
        items: Item[];
        /** Non nul seulement entre l'acceptation et le règlement complet. */
        payment_instructions: {
            methods: { name: string; account: string; colour: string }[];
            account_name: string | null;
            amount: string | null;
            currency: string | null;
        } | null;
        quote: {
            items_amount: string;
            shipping_amount: string;
            total_amount: string;
            currency: string;
            notes: string | null;
            sent_at: string | null;
        } | null;
        payments: {
            currency: string;
            total_paid: string;
            balance: string | null;
            is_settled: boolean;
        } | null;
    };
};

export default function OrderShow({ request }: Props) {
    const { quote, payments } = request;

    return (
        <CustomerLayout wide>
            <Head title={`Demande ${request.reference}`} />

            <div className="animate-rise space-y-10">
                <div>
                    <Link
                        href={index()}
                        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Mes demandes
                    </Link>

                    <div className="mt-6 flex flex-wrap items-center gap-3">
                        {/* La référence reste en mono — c'est un code, pas un
                            mot — mais à la taille d'un titre de page. */}
                        <h1 className="font-mono text-2xl font-bold tracking-tight sm:text-3xl">
                            {request.reference}
                        </h1>
                        <StatusBadge
                            label={request.status_label}
                            color={request.status_color}
                        />
                    </div>
                    <p className="mt-3 text-sm text-muted-foreground">
                        Passée le {formatDate(request.created_at)} · Livraison à{' '}
                        {request.destination}
                    </p>

                    {/* ── Poser une question, là où la question se pose ───────
                        Quelqu'un qui hésite devant son devis n'avait rien à
                        cliquer : il fallait quitter la page, retrouver
                        l'assistant, et retaper sa référence. Le lien l'ouvre
                        déjà sur « nous écrire », la référence en tête du
                        message. */}
                    <Link
                        href={contact(request.reference)}
                        className="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-primary underline-offset-4 hover:underline"
                    >
                        <MessageSquare className="size-4" />
                        Poser une question sur cette demande
                    </Link>
                </div>

                {/* ── Où en est la demande, depuis le début ───────────────────
                    Le statut du moment ne dit pas le chemin parcouru, et c'est
                    le chemin qu'on vient regarder — surtout quand on n'a laissé
                    aucune adresse et que cette page est le seul endroit où la
                    demande parle.

                    L'ordre est celui du temps, le plus récent en bas : une file
                    d'événements se lit comme elle s'est écrite. La dernière
                    pastille est pleine, les précédentes creuses — c'est ce qui
                    distingue « on y est » de « on y est passé ». */}
                {/* ── Deux colonnes à partir de `lg` ──────────────────────────
                    Le devis et son règlement portent la colonne large — c'est
                    là qu'on agit — et le suivi les borde, épinglé : on lève les
                    yeux vers lui, on ne le fait pas défiler. Empilés, chaque
                    carte prenait tout l'écran et le devis n'arrivait qu'au
                    troisième défilement. Sous `lg`, la pile revient, suivi en
                    tête : c'est lui qu'on vient vérifier depuis un téléphone. */}
                <div className="grid items-start gap-8 lg:grid-cols-[7fr_5fr]">
                    {request.timeline.length > 0 && (
                        <div className="min-w-0 lg:sticky lg:top-8 lg:order-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <History className="size-4" />
                                        Suivi
                                    </CardTitle>
                                    <CardDescription>
                                        Chaque étape depuis l'envoi de votre
                                        demande.
                                    </CardDescription>
                                </CardHeader>

                                <CardContent>
                                    <ol className="space-y-0">
                                        {request.timeline.map(
                                            (entry, index) => {
                                                const isLast =
                                                    index ===
                                                    request.timeline.length - 1;

                                                return (
                                                    <li
                                                        key={entry.id}
                                                        className="grid grid-cols-[auto_1fr] gap-x-4"
                                                    >
                                                        <div className="flex flex-col items-center">
                                                            <span
                                                                className={cn(
                                                                    'mt-1 size-3 shrink-0 rounded-full border-2',
                                                                    isLast
                                                                        ? 'border-primary bg-primary'
                                                                        : 'border-border bg-background',
                                                                )}
                                                            />
                                                            {!isLast && (
                                                                <span
                                                                    aria-hidden
                                                                    className="w-px flex-1 bg-border"
                                                                />
                                                            )}
                                                        </div>

                                                        <div
                                                            className={cn(
                                                                'pb-6',
                                                                isLast &&
                                                                    'pb-0',
                                                            )}
                                                        >
                                                            <p
                                                                className={cn(
                                                                    'text-sm',
                                                                    isLast
                                                                        ? 'font-semibold'
                                                                        : 'font-medium text-muted-foreground',
                                                                )}
                                                            >
                                                                {entry.label}
                                                            </p>
                                                            <p className="mt-0.5 text-xs text-muted-foreground">
                                                                {formatDateTime(
                                                                    entry.at,
                                                                )}
                                                            </p>
                                                        </div>
                                                    </li>
                                                );
                                            },
                                        )}
                                    </ol>
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    <div className="min-w-0 space-y-8 lg:order-1">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <ReceiptText className="size-4" />
                                    {quote ? 'Votre devis' : 'Vos produits'}
                                </CardTitle>
                                <CardDescription>
                                    {quote
                                        ? `Devis établi le ${formatDate(quote.sent_at)}.`
                                        : 'Nous chiffrons votre demande, produit par produit.'}
                                </CardDescription>
                            </CardHeader>

                            <CardContent className="space-y-6">
                                <ul className="divide-y">
                                    {request.items.map((item) => (
                                        <li
                                            key={item.id}
                                            className="flex items-start gap-3 py-3 first:pt-0"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <a
                                                    href={item.product_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer nofollow"
                                                    className="inline-flex items-start gap-1.5 font-medium hover:underline"
                                                >
                                                    <span className="break-words">
                                                        {item.name}
                                                    </span>
                                                    <ExternalLink className="mt-1 size-3.5 shrink-0" />
                                                </a>
                                                <p className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                                                    <Badge
                                                        variant="secondary"
                                                        className="font-normal"
                                                    >
                                                        {item.marketplace_label}
                                                    </Badge>
                                                    <span>
                                                        ×{item.quantity}
                                                    </span>
                                                    {item.color && (
                                                        <span>
                                                            {item.color}
                                                        </span>
                                                    )}
                                                    {item.size && (
                                                        <span>
                                                            Taille {item.size}
                                                        </span>
                                                    )}
                                                </p>

                                                {/* Ses propres captures, rendues à leur
                                            auteur : c'est ainsi qu'on vérifie
                                            avoir joint la bonne photo au bon
                                            produit. Ouvertes dans un onglet
                                            plutôt que dans une visionneuse —
                                            le navigateur sait déjà agrandir une
                                            image, et une lightbox n'aurait rien
                                            ajouté qu'une pièce de plus. */}
                                                {item.attachments.length >
                                                    0 && (
                                                    <ul className="mt-3 flex flex-wrap gap-2">
                                                        {item.attachments.map(
                                                            (
                                                                attachment,
                                                                index,
                                                            ) => (
                                                                <li
                                                                    key={
                                                                        attachment.id
                                                                    }
                                                                >
                                                                    <a
                                                                        href={
                                                                            attachment.url
                                                                        }
                                                                        target="_blank"
                                                                        rel="noopener noreferrer"
                                                                        className="block overflow-hidden rounded-lg border transition-colors hover:border-primary/50"
                                                                    >
                                                                        <img
                                                                            src={
                                                                                attachment.url
                                                                            }
                                                                            alt={`Capture ${index + 1} jointe à ${item.name}`}
                                                                            loading="lazy"
                                                                            decoding="async"
                                                                            className="size-16 object-cover"
                                                                        />
                                                                    </a>
                                                                </li>
                                                            ),
                                                        )}
                                                    </ul>
                                                )}
                                            </div>

                                            <span className="shrink-0 text-sm font-medium">
                                                {item.quoted_amount && quote
                                                    ? `${formatAmount(item.quoted_amount)} ${quote.currency}`
                                                    : '—'}
                                            </span>
                                        </li>
                                    ))}
                                </ul>

                                {quote && (
                                    <dl className="space-y-1.5 rounded-xl bg-muted/50 p-4 text-sm">
                                        <Row
                                            label="Produits"
                                            value={`${formatAmount(quote.items_amount)} ${quote.currency}`}
                                        />
                                        <Row
                                            label="Livraison"
                                            value={`${formatAmount(quote.shipping_amount)} ${quote.currency}`}
                                        />
                                        <div className="flex items-baseline justify-between border-t pt-2 text-base">
                                            <dt className="font-medium">
                                                Total
                                            </dt>
                                            <dd className="font-bold">
                                                {formatAmount(
                                                    quote.total_amount,
                                                )}{' '}
                                                {quote.currency}
                                            </dd>
                                        </div>
                                    </dl>
                                )}

                                {quote?.notes && (
                                    <p className="rounded-xl border border-dashed p-3 text-sm">
                                        {quote.notes}
                                    </p>
                                )}

                                {request.awaits_decision && (
                                    <Decision reference={request.reference} />
                                )}
                            </CardContent>
                        </Card>

                        {request.payment_instructions && (
                            <PaymentInstructions
                                instructions={request.payment_instructions}
                                reference={request.reference}
                            />
                        )}

                        {payments && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Règlement</CardTitle>
                                </CardHeader>

                                <CardContent>
                                    {payments.is_settled ? (
                                        <p className="flex items-center gap-2 text-sm font-medium text-success">
                                            <CheckCircle2 className="size-4" />
                                            Votre demande est entièrement
                                            réglée.
                                        </p>
                                    ) : (
                                        <dl className="space-y-1.5 text-sm">
                                            <Row
                                                label="Déjà réglé"
                                                value={`${formatAmount(payments.total_paid)} ${payments.currency}`}
                                            />
                                            <Row
                                                label="Reste à régler"
                                                value={`${formatAmount(payments.balance)} ${payments.currency}`}
                                            />
                                        </dl>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </CustomerLayout>
    );
}

/**
 * Accepter, ou dire non.
 *
 * Les deux gestes sont donnés ensemble et de même poids visuel — refuser n'est
 * pas une sortie de secours discrète. La vitrine promet « vous pouvez dire
 * non » ; un bouton qu'on doit chercher serait une façon de le reprendre.
 *
 * Le refus demande une raison, facultative : c'est elle qui dit à l'équipe quoi
 * changer, et sans elle une demande repart en attente sans que personne ne
 * sache quoi refaire.
 */
function Decision({ reference }: { reference: string }) {
    const [declining, setDeclining] = useState(false);

    if (declining) {
        return (
            <Form
                {...decline.form(reference)}
                options={{ preserveScroll: true }}
                className="space-y-3 rounded-xl border p-5"
            >
                {({ processing }) => (
                    <>
                        <Label htmlFor="reason">
                            Qu'est-ce qui ne va pas ? (facultatif)
                        </Label>
                        <Textarea
                            id="reason"
                            name="reason"
                            rows={3}
                            placeholder="Le prix, un produit, la livraison…"
                        />

                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Confirmer le refus
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => setDeclining(false)}
                            >
                                Annuler
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        );
    }

    return (
        <div className="space-y-3 rounded-xl border p-5">
            <p className="text-sm">
                Ce devis vous convient ? Nous n'achetons rien avant votre
                accord.
            </p>

            <div className="flex flex-wrap gap-2">
                <Form {...accept.form(reference)}>
                    {({ processing }) => (
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            <Check className="size-4" />
                            J'accepte le devis
                        </Button>
                    )}
                </Form>

                <Button
                    type="button"
                    variant="outline"
                    onClick={() => setDeclining(true)}
                >
                    <X className="size-4" />
                    Je refuse
                </Button>
            </div>
        </div>
    );
}

/**
 * Où envoyer l'argent.
 *
 * N'apparaît qu'entre l'acceptation et le règlement : avant, il n'y a rien à
 * payer ; après, un numéro à l'écran n'invite qu'à un second virement.
 *
 * La référence est répétée ici, et c'est le seul détail qui compte vraiment à
 * cet écran : un transfert mobile money arrive sans nom de commande, et sans
 * elle personne ne sait à quelle demande le rattacher.
 */
function PaymentInstructions({
    instructions,
    reference,
}: {
    instructions: NonNullable<Props['request']['payment_instructions']>;
    reference: string;
}) {
    if (instructions.methods.length === 0) {
        return null;
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Wallet className="size-4" />
                    Régler ma demande
                </CardTitle>
                <CardDescription>
                    {instructions.amount &&
                        `${formatAmount(instructions.amount)} ${instructions.currency} à envoyer sur l'un de ces numéros.`}
                </CardDescription>
            </CardHeader>

            <CardContent className="space-y-6">
                <ul className="space-y-3">
                    {instructions.methods.map((method) => (
                        <li
                            key={method.name}
                            className="flex items-center gap-3 rounded-xl border p-3"
                        >
                            <span
                                aria-hidden
                                style={{ backgroundColor: method.colour }}
                                className="size-9 shrink-0 rounded-lg"
                            />
                            <div className="min-w-0">
                                <p className="text-sm font-medium">
                                    {method.name}
                                </p>
                                {/* Un numéro pour les portefeuilles mobiles,
                                    une adresse pour PayPal : affiché tel quel,
                                    et en chasse fixe parce qu'il sera recopié
                                    caractère par caractère. */}
                                <p className="font-mono text-base font-semibold break-all">
                                    {method.account}
                                </p>
                            </div>
                        </li>
                    ))}
                </ul>

                <dl className="space-y-1.5 rounded-xl bg-muted/50 p-4 text-sm">
                    {instructions.account_name && (
                        <Row
                            label="Au nom de"
                            value={instructions.account_name}
                        />
                    )}
                    <Row label="Référence à rappeler" value={reference} />
                </dl>

                <p className="text-xs text-muted-foreground">
                    Indiquez la référence dans le motif du transfert, ou
                    envoyez-nous la capture une fois le paiement fait. Nous
                    achetons dès réception.
                </p>
            </CardContent>
        </Card>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-baseline justify-between">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium">{value}</dd>
        </div>
    );
}

/** La date et l'heure : deux étapes d'un même jour ne doivent pas se confondre. */
function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

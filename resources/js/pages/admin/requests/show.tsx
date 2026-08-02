import { Form, Head, setLayoutProps } from '@inertiajs/react';
import {
    ExternalLink,
    ImageIcon,
    MessageSquarePlus,
    Paperclip,
    ReceiptText,
    Wallet,
} from 'lucide-react';

import InputError from '@/components/input-error';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { store as storeNote } from '@/routes/admin/notes';
import { index, show } from '@/routes/admin/requests';
import { store as storePayment } from '@/routes/admin/requests/payments';
import { store as storeQuote } from '@/routes/admin/requests/quote';
import { update as updateStatus } from '@/routes/admin/requests/status';
import type { Option, PurchaseRequestDetail, StatusOption } from '@/types';

type Props = {
    request: PurchaseRequestDetail;
    availableTransitions: StatusOption[];
    quoteCurrency: string;
    costCurrency: string;
    paymentMethods: Option[];
    canRecordPayment: boolean;
};

export default function RequestShow({
    request,
    availableTransitions,
    quoteCurrency,
    costCurrency,
    paymentMethods,
    canRecordPayment,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Demandes', href: index() },
            { title: request.reference, href: show(request.reference) },
        ],
    });

    return (
        <>
            <Head title={`Demande ${request.reference}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <header className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="font-mono text-xl font-semibold">
                                {request.reference}
                            </h1>
                            <StatusBadge
                                label={request.status_label}
                                color={request.status_color}
                            />
                            <Badge variant="outline">
                                Canal : {request.channel_label}
                            </Badge>
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Créée le {formatDateTime(request.created_at)} · Mise
                            à jour le {formatDateTime(request.updated_at)}
                        </p>
                    </div>
                </header>

                <div className="grid animate-rise gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <ProductsCard request={request} />
                        <HistoryCard request={request} />
                    </div>

                    <div className="space-y-4">
                        <CustomerCard request={request} />
                        <StatusCard
                            request={request}
                            transitions={availableTransitions}
                        />
                        <QuoteCard
                            request={request}
                            quoteCurrency={quoteCurrency}
                            costCurrency={costCurrency}
                        />
                        <PaymentsCard
                            request={request}
                            methods={paymentMethods}
                            canRecord={canRecordPayment}
                        />
                        <NotesCard request={request} />
                    </div>
                </div>
            </div>
        </>
    );
}

function ProductsCard({ request }: { request: PurchaseRequestDetail }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    Produits demandés ({request.items.length})
                </CardTitle>
                <CardDescription>
                    Informations saisies par le client. Ouvrez chaque lien pour
                    effectuer l'achat.
                </CardDescription>
            </CardHeader>

            <CardContent className="space-y-4">
                {request.items.map((item, index) => (
                    <div key={item.id} className="rounded-lg border p-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary">
                                {item.marketplace_label}
                            </Badge>
                            <span className="text-xs text-muted-foreground">
                                Produit n°{index + 1}
                            </span>
                        </div>

                        <a
                            href={item.product_url}
                            target="_blank"
                            rel="noopener noreferrer nofollow"
                            className="mt-2 flex items-start gap-1.5 text-sm text-primary hover:underline"
                        >
                            <span className="break-all">
                                {item.product_url}
                            </span>
                            <ExternalLink className="mt-0.5 size-3.5 shrink-0" />
                        </a>

                        <dl className="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm sm:grid-cols-4">
                            <Field
                                label="Quantité"
                                value={String(item.quantity)}
                            />
                            <Field label="Couleur" value={item.color} />
                            <Field label="Taille" value={item.size} />
                            <Field
                                label="Prix affiché"
                                value={
                                    item.declared_price
                                        ? `${item.declared_price} ${item.declared_currency ?? ''}`.trim()
                                        : null
                                }
                            />
                        </dl>

                        {item.comment && (
                            <p className="mt-3 rounded-md bg-muted/50 p-2 text-sm">
                                « {item.comment} »
                            </p>
                        )}

                        {item.attachments.length > 0 && (
                            <div className="mt-3 flex flex-wrap gap-2">
                                {item.attachments.map((attachment) => (
                                    <a
                                        key={attachment.id}
                                        href={attachment.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-1.5 rounded-md border px-2 py-1 text-xs hover:bg-accent"
                                    >
                                        <ImageIcon className="size-3.5" />
                                        {attachment.name}
                                    </a>
                                ))}
                            </div>
                        )}
                    </div>
                ))}

                {request.customer_comment && (
                    <div className="rounded-lg border border-dashed p-3">
                        <p className="text-xs font-medium text-muted-foreground">
                            Commentaire général du client
                        </p>
                        <p className="mt-1 text-sm">
                            {request.customer_comment}
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function CustomerCard({ request }: { request: PurchaseRequestDetail }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Client</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 text-sm">
                <Field label="Nom" value={request.customer.full_name} />
                <Field
                    label="Téléphone"
                    value={request.customer.phone}
                    href={`tel:${request.customer.phone}`}
                />
                <Field
                    label="Email"
                    value={request.customer.email}
                    href={
                        request.customer.email
                            ? `mailto:${request.customer.email}`
                            : undefined
                    }
                />
                <Field
                    label="Destination"
                    value={`${request.destination.city}, ${request.destination.country_label}`}
                />
            </CardContent>
        </Card>
    );
}

function StatusCard({
    request,
    transitions,
}: {
    request: PurchaseRequestDetail;
    transitions: StatusOption[];
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Changer le statut</CardTitle>
                <CardDescription>
                    Seules les transitions autorisées sont proposées.
                </CardDescription>
            </CardHeader>

            <CardContent>
                {transitions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Cette demande est clôturée : son statut ne peut plus
                        changer.
                    </p>
                ) : (
                    <Form
                        {...updateStatus.form(request.reference)}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="space-y-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="space-y-1.5">
                                    <Label htmlFor="status">
                                        Nouveau statut
                                    </Label>
                                    <Select name="status">
                                        <SelectTrigger id="status">
                                            <SelectValue placeholder="Choisir…" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {transitions.map((transition) => (
                                                <SelectItem
                                                    key={transition.value}
                                                    value={transition.value}
                                                >
                                                    {transition.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.status} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="comment">
                                        Commentaire (facultatif)
                                    </Label>
                                    <Textarea
                                        id="comment"
                                        name="comment"
                                        rows={2}
                                        placeholder="Visible dans l'historique interne"
                                    />
                                    <InputError message={errors.comment} />
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full"
                                >
                                    {processing && <Spinner />}
                                    Mettre à jour
                                </Button>
                            </>
                        )}
                    </Form>
                )}
            </CardContent>
        </Card>
    );
}

function QuoteCard({
    request,
    quoteCurrency,
    costCurrency,
}: {
    request: PurchaseRequestDetail;
    quoteCurrency: string;
    costCurrency: string;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <ReceiptText className="size-4" />
                    Devis
                </CardTitle>
                <CardDescription>
                    Produits et livraison sont chiffrés séparément.
                </CardDescription>
            </CardHeader>

            <CardContent className="space-y-4">
                {request.quote && (
                    <dl className="space-y-1.5 rounded-lg bg-muted/50 p-3 text-sm">
                        <Field
                            label="Produits"
                            value={`${request.quote.items_amount} ${request.quote.currency}`}
                        />
                        <Field
                            label="Livraison"
                            value={`${request.quote.shipping_amount} ${request.quote.currency}`}
                        />
                        <Field
                            label="Total"
                            value={`${request.quote.total_amount} ${request.quote.currency}`}
                        />
                        {request.quote.margin_amount !== null && (
                            <Field
                                label="Marge estimée"
                                value={`${request.quote.margin_amount} ${request.quote.currency}`}
                            />
                        )}
                        <Field
                            label="Envoyé le"
                            value={formatDateTime(request.quote.sent_at)}
                        />
                    </dl>
                )}

                <Form
                    {...storeQuote.form(request.reference)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    className="space-y-3"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid grid-cols-2 gap-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="items_amount">
                                        Produits
                                    </Label>
                                    <Input
                                        id="items_amount"
                                        name="items_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        required
                                        defaultValue={
                                            request.quote?.items_amount ?? ''
                                        }
                                    />
                                    <InputError message={errors.items_amount} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="shipping_amount">
                                        Livraison
                                    </Label>
                                    <Input
                                        id="shipping_amount"
                                        name="shipping_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        required
                                        defaultValue={
                                            request.quote?.shipping_amount ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.shipping_amount}
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="currency">Devise</Label>
                                <Input
                                    id="currency"
                                    name="currency"
                                    maxLength={3}
                                    required
                                    defaultValue={
                                        request.quote?.currency ?? quoteCurrency
                                    }
                                />
                                <InputError message={errors.currency} />
                            </div>

                            {/* Folded away because it is optional and never
                                leaves the back office: it exists so the margin
                                is still knowable once the rate has moved. */}
                            <details className="rounded-lg border px-3 py-2">
                                <summary className="cursor-pointer text-sm font-medium">
                                    Coût d'achat et taux (interne)
                                </summary>

                                <div className="mt-3 space-y-3">
                                    <div className="grid grid-cols-2 gap-2">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="cost_amount">
                                                Coût d'achat
                                            </Label>
                                            <Input
                                                id="cost_amount"
                                                name="cost_amount"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                defaultValue={
                                                    request.quote
                                                        ?.cost_amount ?? ''
                                                }
                                            />
                                            <InputError
                                                message={errors.cost_amount}
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="cost_currency">
                                                Devise d'achat
                                            </Label>
                                            <Input
                                                id="cost_currency"
                                                name="cost_currency"
                                                maxLength={3}
                                                defaultValue={
                                                    request.quote
                                                        ?.cost_currency ??
                                                    costCurrency
                                                }
                                            />
                                            <InputError
                                                message={errors.cost_currency}
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label htmlFor="exchange_rate">
                                            Taux de change
                                        </Label>
                                        <Input
                                            id="exchange_rate"
                                            name="exchange_rate"
                                            type="number"
                                            step="0.000001"
                                            min="0"
                                            defaultValue={
                                                request.quote?.exchange_rate ??
                                                ''
                                            }
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Combien de {quoteCurrency} pour 1{' '}
                                            {costCurrency}.
                                        </p>
                                        <InputError
                                            message={errors.exchange_rate}
                                        />
                                    </div>
                                </div>
                            </details>

                            <div className="space-y-1.5">
                                <Label htmlFor="notes">
                                    Note du devis (facultatif)
                                </Label>
                                <Textarea id="notes" name="notes" rows={2} />
                                <InputError message={errors.notes} />
                            </div>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-full"
                            >
                                {processing && <Spinner />}
                                {request.quote
                                    ? 'Mettre à jour le devis'
                                    : 'Envoyer le devis'}
                            </Button>
                            <InputError message={errors.status} />
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}

/**
 * The ledger of money received against the quote.
 *
 * Instalments are the norm rather than the exception — a deposit releases the
 * purchase, the balance often lands on delivery — so this lists every line and
 * leads with what is still owed, which is the figure that decides whether the
 * order can move forward.
 */
function PaymentsCard({
    request,
    methods,
    canRecord,
}: {
    request: PurchaseRequestDetail;
    methods: Option[];
    canRecord: boolean;
}) {
    const payments = request.payments;

    if (!payments) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Wallet className="size-4" />
                        Paiements
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">
                        Envoyez d'abord un devis : il n'y a rien à régler tant
                        qu'aucun montant n'a été annoncé au client.
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Wallet className="size-4" />
                    Paiements
                </CardTitle>
                <CardDescription>
                    Les versements partiels sont additionnés. Le statut passe à
                    « Paiement reçu » quand le solde atteint zéro.
                </CardDescription>
            </CardHeader>

            <CardContent className="space-y-4">
                <dl className="space-y-1.5 rounded-lg bg-muted/50 p-3 text-sm">
                    <Field
                        label="Déjà reçu"
                        value={`${payments.total_paid} ${payments.currency}`}
                    />
                    <Field
                        label={
                            Number(payments.balance) < 0
                                ? 'Trop-perçu'
                                : 'Reste à percevoir'
                        }
                        value={
                            payments.balance === null
                                ? null
                                : `${Math.abs(Number(payments.balance)).toFixed(2)} ${payments.currency}`
                        }
                    />
                </dl>

                {payments.is_settled && (
                    <p className="rounded-md bg-success/10 px-3 py-2 text-sm text-success">
                        Demande intégralement réglée.
                    </p>
                )}

                {payments.entries.length > 0 && (
                    <ul className="space-y-3">
                        {payments.entries.map((entry) => (
                            <li
                                key={entry.id}
                                className="rounded-lg border p-3 text-sm"
                            >
                                <div className="flex items-baseline justify-between gap-2">
                                    <span className="font-medium">
                                        {entry.amount} {entry.currency}
                                    </span>
                                    <Badge variant="secondary">
                                        {entry.method_label}
                                    </Badge>
                                </div>

                                <p className="mt-1 text-xs text-muted-foreground">
                                    {formatDateTime(entry.received_at)}
                                    {entry.provider && ` · ${entry.provider}`}
                                    {entry.recorded_by &&
                                        ` · saisi par ${entry.recorded_by}`}
                                </p>

                                {entry.provider_reference && (
                                    <p className="mt-1 font-mono text-xs break-all">
                                        {entry.provider_reference}
                                    </p>
                                )}

                                {entry.notes && (
                                    <p className="mt-1 text-sm">
                                        {entry.notes}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ul>
                )}

                {canRecord && (
                    <Form
                        {...storePayment.form(request.reference)}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="space-y-3 border-t pt-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid grid-cols-2 gap-2">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="amount">Montant</Label>
                                        <Input
                                            id="amount"
                                            name="amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            required
                                            defaultValue={
                                                payments.balance !== null &&
                                                Number(payments.balance) > 0
                                                    ? payments.balance
                                                    : ''
                                            }
                                        />
                                        <InputError message={errors.amount} />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label htmlFor="currency_payment">
                                            Devise
                                        </Label>
                                        <Input
                                            id="currency_payment"
                                            name="currency"
                                            maxLength={3}
                                            readOnly
                                            required
                                            defaultValue={payments.currency}
                                        />
                                        <InputError message={errors.currency} />
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="method">
                                        Moyen de paiement
                                    </Label>
                                    <Select
                                        name="method"
                                        defaultValue={methods[0]?.value}
                                    >
                                        <SelectTrigger id="method">
                                            <SelectValue placeholder="Choisir…" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {methods.map((method) => (
                                                <SelectItem
                                                    key={method.value}
                                                    value={method.value}
                                                >
                                                    {method.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.method} />
                                </div>

                                <div className="grid grid-cols-2 gap-2">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="provider">
                                            Opérateur
                                        </Label>
                                        <Input
                                            id="provider"
                                            name="provider"
                                            maxLength={60}
                                            placeholder="Orange Money"
                                        />
                                        <InputError message={errors.provider} />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label htmlFor="received_at">
                                            Reçu le
                                        </Label>
                                        <Input
                                            id="received_at"
                                            name="received_at"
                                            type="datetime-local"
                                            required
                                            defaultValue={localDateTimeInput()}
                                        />
                                        <InputError
                                            message={errors.received_at}
                                        />
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="provider_reference">
                                        Référence de transaction
                                    </Label>
                                    <Input
                                        id="provider_reference"
                                        name="provider_reference"
                                        maxLength={120}
                                        placeholder="Identifiant relevé par le client"
                                    />
                                    <InputError
                                        message={errors.provider_reference}
                                    />
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full"
                                >
                                    {processing && <Spinner />}
                                    Enregistrer le paiement
                                </Button>
                            </>
                        )}
                    </Form>
                )}
            </CardContent>
        </Card>
    );
}

function NotesCard({ request }: { request: PurchaseRequestDetail }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <MessageSquarePlus className="size-4" />
                    Notes internes
                </CardTitle>
                <CardDescription>
                    Jamais visibles par le client.
                </CardDescription>
            </CardHeader>

            <CardContent className="space-y-4">
                <Form
                    {...storeNote.form(request.reference)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    className="space-y-2"
                >
                    {({ processing, errors }) => (
                        <>
                            <Textarea
                                name="body"
                                rows={2}
                                required
                                placeholder="Ajouter une note…"
                                aria-label="Nouvelle note interne"
                            />
                            <InputError message={errors.body} />
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Ajouter
                            </Button>
                        </>
                    )}
                </Form>

                <ul className="space-y-3">
                    {request.notes.map((note) => (
                        <li key={note.id} className="text-sm">
                            <p className="whitespace-pre-line">{note.body}</p>
                            <p className="mt-0.5 text-xs text-muted-foreground">
                                {note.author} ·{' '}
                                {formatDateTime(note.created_at)}
                            </p>
                        </li>
                    ))}
                </ul>
            </CardContent>
        </Card>
    );
}

function HistoryCard({ request }: { request: PurchaseRequestDetail }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Paperclip className="size-4" />
                    Historique des statuts
                </CardTitle>
            </CardHeader>

            <CardContent>
                <ol className="space-y-4">
                    {request.status_history.map((entry) => (
                        <li key={entry.id} className="flex gap-3">
                            <div
                                aria-hidden
                                className="mt-1.5 size-2 shrink-0 rounded-full bg-border"
                            />
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    {entry.from_label && (
                                        <>
                                            <span className="text-sm text-muted-foreground">
                                                {entry.from_label}
                                            </span>
                                            <span className="text-muted-foreground">
                                                →
                                            </span>
                                        </>
                                    )}
                                    <StatusBadge
                                        label={entry.to_label}
                                        color={entry.to_color}
                                    />
                                </div>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    {entry.author ?? 'Système'} ·{' '}
                                    {formatDateTime(entry.created_at)}
                                </p>
                                {entry.comment && (
                                    <p className="mt-1 text-sm">
                                        {entry.comment}
                                    </p>
                                )}
                            </div>
                        </li>
                    ))}
                </ol>
            </CardContent>
        </Card>
    );
}

function Field({
    label,
    value,
    href,
}: {
    label: string;
    value: string | null;
    href?: string;
}) {
    return (
        <div>
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="truncate">
                {value && href ? (
                    <a href={href} className="text-primary hover:underline">
                        {value}
                    </a>
                ) : (
                    (value ?? '—')
                )}
            </dd>
        </div>
    );
}

/**
 * Now, in the shape `datetime-local` expects.
 *
 * Built from the local parts rather than `toISOString`, which would shift the
 * default to UTC and pre-fill a time the administrator never saw on the clock.
 */
function localDateTimeInput(): string {
    const now = new Date();
    const pad = (value: number) => String(value).padStart(2, '0');

    return (
        `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}` +
        `T${pad(now.getHours())}:${pad(now.getMinutes())}`
    );
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

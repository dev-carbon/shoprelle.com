import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    ExternalLink,
    ReceiptText,
} from 'lucide-react';

import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { CustomerLayout } from '@/layouts/customer-layout';
import { formatAmount, formatDate } from '@/lib/utils';
import { index } from '@/routes/orders';

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
};

type Props = {
    request: {
        reference: string;
        status_label: string;
        status_color: string;
        created_at: string | null;
        destination: string;
        items: Item[];
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
        <CustomerLayout>
            <Head title={`Demande ${request.reference}`} />

            <div className="animate-rise space-y-6">
                <div>
                    <Link
                        href={index()}
                        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Mes demandes
                    </Link>

                    <div className="mt-3 flex flex-wrap items-center gap-3">
                        <h1 className="font-mono text-xl font-semibold">
                            {request.reference}
                        </h1>
                        <StatusBadge
                            label={request.status_label}
                            color={request.status_color}
                        />
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Passée le {formatDate(request.created_at)} · Livraison à{' '}
                        {request.destination}
                    </p>
                </div>

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

                    <CardContent className="space-y-4">
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
                                            <span>×{item.quantity}</span>
                                            {item.color && (
                                                <span>{item.color}</span>
                                            )}
                                            {item.size && (
                                                <span>Taille {item.size}</span>
                                            )}
                                        </p>
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
                                    <dt className="font-medium">Total</dt>
                                    <dd className="font-bold">
                                        {formatAmount(quote.total_amount)}{' '}
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
                    </CardContent>
                </Card>

                {payments && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Règlement</CardTitle>
                        </CardHeader>

                        <CardContent>
                            {payments.is_settled ? (
                                <p className="flex items-center gap-2 text-sm font-medium text-success">
                                    <CheckCircle2 className="size-4" />
                                    Votre demande est entièrement réglée.
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
        </CustomerLayout>
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

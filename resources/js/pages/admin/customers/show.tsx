import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { KeyRound, Mail, Phone } from 'lucide-react';

import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index, show } from '@/routes/admin/customers';
import { store as storeAccessCode } from '@/routes/admin/customers/code';
import { show as showRequest } from '@/routes/admin/requests';
import type { CustomerDetail } from '@/types';

type Props = {
    customer: CustomerDetail;
};

/**
 * Issues the customer a new access code, for when they have lost theirs.
 *
 * Codes are stored hashed and cannot be looked up, so this is the only remedy —
 * and it is destructive: the old code stops working the moment this is pressed,
 * which is why it asks first and why the new one is shown only once, in the
 * toast that follows.
 */
function AccessCodeButton({ customer }: { customer: CustomerDetail }) {
    return (
        <Form
            {...storeAccessCode.form(customer.id)}
            options={{ preserveScroll: true }}
            onBefore={() =>
                confirm(
                    `Générer un nouveau code pour ${customer.full_name} ? Son code actuel cessera immédiatement de fonctionner.`,
                )
            }
        >
            {({ processing }) => (
                <Button
                    type="submit"
                    variant="outline"
                    size="sm"
                    disabled={processing}
                >
                    {processing ? <Spinner /> : <KeyRound className="size-4" />}
                    {customer.has_access_code
                        ? 'Nouveau code'
                        : "Générer un code d'accès"}
                </Button>
            )}
        </Form>
    );
}

export default function CustomerShow({ customer }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Clients', href: index() },
            { title: customer.full_name, href: show(customer.id) },
        ],
    });

    return (
        <>
            <Head title={customer.full_name} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <header className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">
                            {customer.full_name}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {customer.city}, {customer.country_label} · client
                            depuis le {formatDate(customer.created_at)}
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <a href={`tel:${customer.phone}`}>
                                <Phone className="size-4" />
                                {customer.phone}
                            </a>
                        </Button>

                        {customer.email && (
                            <Button variant="outline" size="sm" asChild>
                                <a href={`mailto:${customer.email}`}>
                                    <Mail className="size-4" />
                                    Écrire
                                </a>
                            </Button>
                        )}

                        <AccessCodeButton customer={customer} />
                    </div>
                </header>

                <div className="grid animate-rise gap-4 sm:grid-cols-3">
                    <Stat
                        label="Demandes"
                        value={String(customer.summary.request_count)}
                    />
                    <Stat
                        label="En cours"
                        value={String(customer.summary.active_count)}
                    />
                    <Stat
                        label="Total devisé"
                        value={`${new Intl.NumberFormat('fr-FR').format(
                            Math.round(Number(customer.summary.quoted_total)),
                        )} ${customer.summary.quote_currency}`}
                    />
                </div>

                <Card className="animate-rise">
                    <CardHeader>
                        <CardTitle>Historique des demandes</CardTitle>
                    </CardHeader>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Référence
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Produits
                                    </TableHead>
                                    <TableHead>Ville</TableHead>
                                    <TableHead>Devis</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead className="pr-6">
                                        Créée le
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {customer.requests.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-10 text-center text-sm text-muted-foreground"
                                        >
                                            Ce client n'a aucune demande.
                                        </TableCell>
                                    </TableRow>
                                )}

                                {customer.requests.map((request) => (
                                    <TableRow key={request.reference}>
                                        <TableCell className="pl-6 font-mono text-xs font-medium">
                                            <Link
                                                href={showRequest(
                                                    request.reference,
                                                )}
                                                className="hover:underline"
                                            >
                                                {request.reference}
                                            </Link>
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {request.item_count}
                                        </TableCell>
                                        <TableCell>{request.city}</TableCell>
                                        <TableCell className="tabular-nums">
                                            {request.quote_total_amount
                                                ? `${request.quote_total_amount} ${request.quote_currency ?? ''}`
                                                : '—'}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                label={request.status_label}
                                                color={request.status_color}
                                            />
                                        </TableCell>
                                        <TableCell className="pr-6 text-sm text-muted-foreground">
                                            {formatDate(request.created_at)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function Stat({ label, value }: { label: string; value: string }) {
    return (
        <Card>
            <CardContent>
                <p className="text-sm text-muted-foreground">{label}</p>
                <p className="mt-1 text-2xl font-semibold tabular-nums">
                    {value}
                </p>
            </CardContent>
        </Card>
    );
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

import { Form, Head, Link } from '@inertiajs/react';
import { ChevronRight, PackageOpen } from 'lucide-react';

import { Accent, Eyebrow } from '@/components/section-heading';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { CustomerLayout } from '@/layouts/customer-layout';
import { formatAmount, formatDate } from '@/lib/utils';
import { show as showChat } from '@/routes/chat';
import { show } from '@/routes/orders';
import { destroy } from '@/routes/orders/access';

type OrderRow = {
    reference: string;
    status_label: string;
    status_color: string;
    item_count: number;
    created_at: string | null;
    /** Null until the request has been quoted. */
    total_amount: string | null;
    currency: string | null;
};

type Props = {
    customer: { first_name: string; phone: string };
    requests: OrderRow[];
};

export default function OrdersIndex({ customer, requests }: Props) {
    return (
        <CustomerLayout
            action={
                <Form {...destroy.form()}>
                    <Button variant="ghost" size="sm" type="submit">
                        Se déconnecter
                    </Button>
                </Form>
            }
        >
            <Head title="Mes demandes" />

            <div className="animate-rise space-y-10">
                <header>
                    <Eyebrow>Mes demandes</Eyebrow>

                    {/* Le prénom en bleu et non en or : c'est un nom propre,
                        donc de l'information, et l'or ne porte pas de
                        l'information sur un fond clair. */}
                    {/* La même échelle que les titres de la vitrine : cet
                        espace est le même site, et un titre timide le faisait
                        passer pour une annexe. */}
                    <h1 className="mt-6 font-display text-title font-black">
                        Bonjour{' '}
                        <Accent tone="blue">{customer.first_name}</Accent>
                    </h1>
                    <p className="mt-4 text-body text-muted-foreground">
                        {requests.length === 0
                            ? 'Aucune demande pour le moment.'
                            : `${requests.length} demande${requests.length > 1 ? 's' : ''} au ${customer.phone}.`}
                    </p>
                </header>

                {requests.length === 0 ? (
                    <div className="rounded-3xl border border-dashed p-12 text-center">
                        <PackageOpen className="mx-auto size-6 text-muted-foreground" />
                        <p className="mt-3 text-sm text-muted-foreground">
                            Vos demandes apparaîtront ici dès que vous en aurez
                            passé une.
                        </p>
                        <Button asChild className="mt-4">
                            <a href={showChat().url}>Passer une demande</a>
                        </Button>
                    </div>
                ) : (
                    <ul className="space-y-4">
                        {requests.map((request) => (
                            <li key={request.reference}>
                                <Link
                                    href={show(request.reference)}
                                    className="flex items-center gap-5 rounded-2xl border bg-card p-5 transition hover:border-primary/40 hover:shadow-sm"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="font-mono font-semibold">
                                            {request.reference}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {formatDate(request.created_at)} ·{' '}
                                            {request.item_count} produit
                                            {request.item_count > 1 ? 's' : ''}
                                        </p>
                                    </div>

                                    <div className="flex flex-col items-end gap-1.5 text-right">
                                        <StatusBadge
                                            label={request.status_label}
                                            color={request.status_color}
                                        />
                                        {request.total_amount && (
                                            <span className="text-sm font-medium">
                                                {formatAmount(
                                                    request.total_amount,
                                                )}{' '}
                                                {request.currency}
                                            </span>
                                        )}
                                    </div>

                                    <ChevronRight className="size-4 shrink-0 text-muted-foreground" />
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </CustomerLayout>
    );
}

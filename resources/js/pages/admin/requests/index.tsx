import { Head, Link } from '@inertiajs/react';
import { Inbox } from 'lucide-react';

import { Pagination } from '@/components/admin/pagination';
import { RequestFilters } from '@/components/admin/request-filters';
import type { Filters } from '@/components/admin/request-filters';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index, show } from '@/routes/admin/requests';
import type {
    Option,
    Paginated,
    PurchaseRequestRow,
    StatusOption,
} from '@/types';

type Props = {
    requests: Paginated<PurchaseRequestRow>;
    filters: Filters;
    statuses: StatusOption[];
    marketplaces: Option[];
    countries: Record<string, string>;
};

export default function RequestsIndex({
    requests,
    filters,
    statuses,
    marketplaces,
    countries,
}: Props) {
    return (
        <>
            <Head title="Demandes" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
                <div>
                    <h1 className="font-display text-2xl font-extrabold tracking-tight">
                        Demandes d'achat
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Toutes les demandes transmises par les clients.
                    </p>
                </div>

                <RequestFilters
                    filters={filters}
                    statuses={statuses}
                    marketplaces={marketplaces}
                    countries={countries}
                />

                <div className="animate-rise rounded-xl border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-4">
                                    Référence
                                </TableHead>
                                <TableHead>Client</TableHead>
                                <TableHead>Plateformes</TableHead>
                                <TableHead className="text-right">
                                    Produits
                                </TableHead>
                                <TableHead>Destination</TableHead>
                                <TableHead>Statut</TableHead>
                                <TableHead className="pr-4">Créée le</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {requests.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-12 text-center"
                                    >
                                        <Inbox className="mx-auto size-8 text-muted-foreground" />
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            Aucune demande ne correspond à ces
                                            filtres.
                                        </p>
                                    </TableCell>
                                </TableRow>
                            )}

                            {requests.data.map((request) => (
                                <TableRow key={request.reference}>
                                    <TableCell className="pl-4 font-mono text-xs font-medium">
                                        <Link
                                            href={show(request.reference)}
                                            className="hover:underline"
                                        >
                                            {request.reference}
                                        </Link>
                                    </TableCell>

                                    <TableCell>
                                        <div className="font-medium">
                                            {request.customer_name}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {request.customer_phone}
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div className="flex flex-wrap gap-1">
                                            {request.marketplaces.map(
                                                (marketplace) => (
                                                    <Badge
                                                        key={marketplace}
                                                        variant="secondary"
                                                    >
                                                        {marketplace}
                                                    </Badge>
                                                ),
                                            )}
                                        </div>
                                    </TableCell>

                                    <TableCell className="text-right tabular-nums">
                                        {request.total_quantity}
                                        <span className="text-xs text-muted-foreground">
                                            {' '}
                                            / {request.item_count} ligne
                                            {request.item_count > 1 ? 's' : ''}
                                        </span>
                                    </TableCell>

                                    <TableCell>
                                        <div>{request.city}</div>
                                        <div className="text-xs text-muted-foreground">
                                            {request.country_label}
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <StatusBadge
                                            label={request.status_label}
                                            color={request.status_color}
                                        />
                                    </TableCell>

                                    <TableCell className="pr-4 text-sm text-muted-foreground">
                                        {formatDate(request.created_at)}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    <Pagination meta={requests.meta} noun="demande" />
                </div>
            </div>
        </>
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

RequestsIndex.layout = {
    breadcrumbs: [{ title: 'Demandes', href: index() }],
};

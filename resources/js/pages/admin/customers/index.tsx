import { Head, Link, router } from '@inertiajs/react';
import { Search, UserRound, X } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Pagination } from '@/components/admin/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index, show } from '@/routes/admin/customers';
import type { CustomerRow, Paginated } from '@/types';

const ALL = '__all__';

type Filters = {
    search: string | null;
    country: string | null;
    sort: string;
    direction: string;
};

type Props = {
    customers: Paginated<CustomerRow>;
    filters: Filters;
    countries: Record<string, string>;
};

export default function CustomersIndex({
    customers,
    filters,
    countries,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const apply = (changes: Partial<Filters>) => {
        const next = { ...filters, ...changes };

        router.get(
            index().url,
            Object.fromEntries(
                Object.entries(next).filter(
                    ([, value]) => value !== null && value !== '',
                ),
            ),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeout = setTimeout(
            () => apply({ search: search || null }),
            300,
        );

        return () => clearTimeout(timeout);
    }, [search]); // eslint-disable-line react-hooks/exhaustive-deps

    const hasFilters = Boolean(filters.search || filters.country);

    return (
        <>
            <Head title="Clients" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
                <div>
                    <h1 className="font-display text-2xl font-extrabold tracking-tight">
                        Clients
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Toute personne ayant déjà soumis une demande.
                    </p>
                </div>

                <div className="flex flex-wrap items-end gap-2">
                    <div className="relative min-w-56 flex-1">
                        <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Nom, téléphone, email, ville…"
                            className="pl-8"
                            aria-label="Rechercher un client"
                        />
                    </div>

                    <Select
                        value={filters.country ?? ALL}
                        onValueChange={(next) =>
                            apply({ country: next === ALL ? null : next })
                        }
                    >
                        <SelectTrigger
                            className="w-auto min-w-36"
                            aria-label="Pays"
                        >
                            <SelectValue placeholder="Pays" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>Pays : tous</SelectItem>
                            {Object.entries(countries).map(([code, label]) => (
                                <SelectItem key={code} value={code}>
                                    {label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.sort}
                        onValueChange={(next) => apply({ sort: next })}
                    >
                        <SelectTrigger
                            className="w-auto min-w-44"
                            aria-label="Trier"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="created_at">
                                Plus récents
                            </SelectItem>
                            <SelectItem value="purchase_requests_count">
                                Plus de demandes
                            </SelectItem>
                            <SelectItem value="last_name">Nom (A→Z)</SelectItem>
                        </SelectContent>
                    </Select>

                    {hasFilters && (
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setSearch('');
                                router.get(index().url, {}, { replace: true });
                            }}
                        >
                            <X className="size-4" />
                            Réinitialiser
                        </Button>
                    )}
                </div>

                <div className="animate-rise rounded-xl border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-4">Client</TableHead>
                                <TableHead>Contact</TableHead>
                                <TableHead>Destination</TableHead>
                                <TableHead className="text-right">
                                    Demandes
                                </TableHead>
                                <TableHead className="pr-4">
                                    Dernière demande
                                </TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {customers.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="py-12 text-center"
                                    >
                                        <UserRound className="mx-auto size-8 text-muted-foreground" />
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            Aucun client ne correspond à ces
                                            filtres.
                                        </p>
                                    </TableCell>
                                </TableRow>
                            )}

                            {customers.data.map((customer) => (
                                <TableRow key={customer.id}>
                                    <TableCell className="pl-4 font-medium">
                                        <Link
                                            href={show(customer.id)}
                                            className="hover:underline"
                                        >
                                            {customer.full_name}
                                        </Link>
                                    </TableCell>

                                    <TableCell>
                                        <div className="text-sm">
                                            {customer.phone}
                                        </div>
                                        {customer.email && (
                                            <div className="text-xs text-muted-foreground">
                                                {customer.email}
                                            </div>
                                        )}
                                    </TableCell>

                                    <TableCell>
                                        <div>{customer.city}</div>
                                        <div className="text-xs text-muted-foreground">
                                            {customer.country_label}
                                        </div>
                                    </TableCell>

                                    <TableCell className="text-right tabular-nums">
                                        {customer.request_count}
                                    </TableCell>

                                    <TableCell className="pr-4 text-sm text-muted-foreground">
                                        {formatDate(customer.last_request_at)}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    <Pagination meta={customers.meta} noun="client" />
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

CustomersIndex.layout = {
    breadcrumbs: [{ title: 'Clients', href: index() }],
};

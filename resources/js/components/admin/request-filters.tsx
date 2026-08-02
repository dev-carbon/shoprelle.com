import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/admin/requests';
import type { Option, StatusOption } from '@/types';

const ALL = '__all__';

export type Filters = {
    search: string | null;
    status: string | null;
    marketplace: string | null;
    country: string | null;
    from: string | null;
    to: string | null;
    sort: string;
    direction: string;
};

/**
 * Filters drive the URL, so a filtered view can be bookmarked and shared, and
 * the browser's back button behaves as expected.
 */
export function RequestFilters({
    filters,
    statuses,
    marketplaces,
    countries,
}: {
    filters: Filters;
    statuses: StatusOption[];
    marketplaces: Option[];
    countries: Record<string, string>;
}) {
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

    // Debounce the free-text search so typing does not fire a request per key.
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

    const hasFilters = Boolean(
        filters.search ||
        filters.status ||
        filters.marketplace ||
        filters.country ||
        filters.from ||
        filters.to,
    );

    return (
        <div className="flex flex-wrap items-end gap-2">
            <div className="relative min-w-56 flex-1">
                <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Référence, client, téléphone, lien…"
                    className="pl-8"
                    aria-label="Rechercher une demande"
                />
            </div>

            <FilterSelect
                value={filters.status}
                placeholder="Statut"
                options={statuses}
                onChange={(value) => apply({ status: value })}
            />

            <FilterSelect
                value={filters.marketplace}
                placeholder="Plateforme"
                options={marketplaces}
                onChange={(value) => apply({ marketplace: value })}
            />

            <FilterSelect
                value={filters.country}
                placeholder="Pays"
                options={Object.entries(countries).map(([value, label]) => ({
                    value,
                    label,
                }))}
                onChange={(value) => apply({ country: value })}
            />

            <Input
                type="date"
                value={filters.from ?? ''}
                onChange={(event) =>
                    apply({ from: event.target.value || null })
                }
                className="w-auto"
                aria-label="Date de début"
            />
            <Input
                type="date"
                value={filters.to ?? ''}
                onChange={(event) => apply({ to: event.target.value || null })}
                className="w-auto"
                aria-label="Date de fin"
            />

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
    );
}

function FilterSelect({
    value,
    placeholder,
    options,
    onChange,
}: {
    value: string | null;
    placeholder: string;
    options: Option[];
    onChange: (value: string | null) => void;
}) {
    return (
        <Select
            value={value ?? ALL}
            onValueChange={(next) => onChange(next === ALL ? null : next)}
        >
            <SelectTrigger className="w-auto min-w-36" aria-label={placeholder}>
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value={ALL}>{placeholder} : tous</SelectItem>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

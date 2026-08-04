import { Head, Link } from '@inertiajs/react';
import { CalendarClock, Inbox, Layers, PackageCheck } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import { StatusBadge } from '@/components/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/admin/requests';
import type {
    DashboardStatistics,
    PurchaseRequestRow,
    StatusOption,
} from '@/types';

type Props = {
    statistics: DashboardStatistics;
    statuses: StatusOption[];
    latestRequests: PurchaseRequestRow[];
};

export default function Dashboard({
    statistics,
    statuses,
    latestRequests,
}: Props) {
    return (
        <>
            <Head title="Tableau de bord" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
                <div>
                    <h1 className="font-display text-2xl font-extrabold tracking-tight">
                        Tableau de bord
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Vue d'ensemble des demandes d'achat.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Stat
                        label="Demandes au total"
                        value={statistics.total}
                        icon={Layers}
                    />
                    <Stat
                        label="En cours de traitement"
                        value={statistics.active}
                        icon={PackageCheck}
                        delay={50}
                    />
                    <Stat
                        label="Sur 7 jours"
                        value={statistics.last_seven_days}
                        icon={CalendarClock}
                        delay={100}
                    />
                    <Stat
                        label="Nouvelles à traiter"
                        value={statistics.by_status.new ?? 0}
                        icon={Inbox}
                        delay={150}
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Répartition par statut</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {statuses.map((status) => (
                            <Link
                                key={status.value}
                                href={index({
                                    query: { status: status.value },
                                })}
                                className="inline-flex items-center gap-2 rounded-lg border bg-card px-3 py-2 transition-colors hover:border-foreground/20"
                            >
                                <StatusBadge
                                    label={status.label}
                                    color={status.color}
                                />
                                <span className="text-sm font-semibold tabular-nums">
                                    {statistics.by_status[status.value] ?? 0}
                                </span>
                            </Link>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Dernières demandes</CardTitle>
                    </CardHeader>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Référence
                                    </TableHead>
                                    <TableHead>Client</TableHead>
                                    <TableHead>Destination</TableHead>
                                    <TableHead className="pr-6">
                                        Statut
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {latestRequests.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="py-10 text-center text-sm text-muted-foreground"
                                        >
                                            Aucune demande pour le moment.
                                        </TableCell>
                                    </TableRow>
                                )}

                                {latestRequests.map((request) => (
                                    <TableRow key={request.reference}>
                                        <TableCell className="pl-6 font-mono text-xs font-medium">
                                            <Link
                                                href={show(request.reference)}
                                                className="hover:underline"
                                            >
                                                {request.reference}
                                            </Link>
                                        </TableCell>
                                        <TableCell>
                                            {request.customer_name}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {request.city},{' '}
                                            {request.country_label}
                                        </TableCell>
                                        <TableCell className="pr-6">
                                            <StatusBadge
                                                label={request.status_label}
                                                color={request.status_color}
                                            />
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

function Stat({
    label,
    value,
    icon: Icon,
    delay = 0,
}: {
    label: string;
    value: number;
    icon: LucideIcon;
    delay?: number;
}) {
    return (
        <Card
            className="animate-rise transition-shadow duration-200 hover:shadow-md"
            style={{ animationDelay: `${delay}ms` }}
        >
            <CardContent className="flex items-center justify-between gap-4">
                <div>
                    <p className="text-sm text-muted-foreground">{label}</p>
                    {/* Le chiffre est ce qu'on vient lire : il parle dans la
                        voix des titres du site, en chiffres tabulaires pour
                        que la colonne ne bouge pas d'un jour à l'autre. */}
                    <p className="mt-1 font-display text-3xl font-black tabular-nums">
                        {value}
                    </p>
                </div>
                <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-accent text-accent-foreground">
                    <Icon className="size-5" />
                </span>
            </CardContent>
        </Card>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Tableau de bord', href: dashboard() }],
};

import { Head, Link } from '@inertiajs/react';
import { Banknote, Package, Receipt, Users } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import { BarList } from '@/components/charts/bar-list';
import { TrendChart } from '@/components/charts/trend-chart';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { statistics as statisticsRoute } from '@/routes/admin';
import type { Statistics } from '@/types';

type Props = {
    statistics: Statistics;
    periods: number[];
};

export default function StatisticsPage({ statistics, periods }: Props) {
    const { headline } = statistics;

    return (
        <>
            <Head title="Statistiques" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="font-display text-2xl font-extrabold tracking-tight">
                            Statistiques
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Activité sur les {statistics.period_days} derniers
                            jours.
                        </p>
                    </div>

                    <nav
                        className="flex gap-1 rounded-xl border bg-card p-1"
                        aria-label="Période"
                    >
                        {periods.map((period) => (
                            <Link
                                key={period}
                                href={statisticsRoute({
                                    query: { period },
                                })}
                                preserveScroll
                                className={cn(
                                    'rounded-lg px-3 py-1 text-sm transition-colors',
                                    period === statistics.period_days
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {period} j
                            </Link>
                        ))}
                    </nav>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Stat
                        label="Demandes sur la période"
                        value={String(headline.requests_in_period)}
                        icon={Package}
                    />
                    <Stat
                        label="Clients"
                        value={String(headline.customers_total)}
                        hint={`+${headline.new_customers_in_period} sur la période`}
                        icon={Users}
                        delay={50}
                    />
                    <Stat
                        label="Total devisé"
                        value={formatAmount(
                            headline.quoted_total,
                            headline.currency,
                        )}
                        icon={Banknote}
                        delay={100}
                    />
                    <Stat
                        label="Devis moyen"
                        value={formatAmount(
                            headline.average_quote,
                            headline.currency,
                        )}
                        icon={Receipt}
                        delay={150}
                    />
                </div>

                <Card className="animate-rise">
                    <CardHeader>
                        <CardTitle>Demandes reçues par jour</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <TrendChart data={statistics.daily} />
                    </CardContent>
                </Card>

                {/* Le trafic de la vitrine, compté en interne — voir le
                    middleware RecordPageVisit : deux compteurs par jour, et
                    c'est tout ce que le site mesure. */}
                <Card className="animate-rise">
                    <CardHeader>
                        <CardTitle>Visites de la vitrine</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="mb-5 flex flex-wrap gap-x-10 gap-y-2">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Pages vues
                                </p>
                                <p className="mt-1 text-2xl font-semibold tabular-nums">
                                    {statistics.traffic.views}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Visiteurs
                                </p>
                                <p className="mt-1 text-2xl font-semibold tabular-nums">
                                    {statistics.traffic.visitors}
                                </p>
                            </div>
                        </div>

                        <TrendChart data={statistics.traffic.daily} />
                    </CardContent>
                </Card>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="animate-rise">
                        <CardHeader>
                            <CardTitle>Parcours des demandes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BarList
                                data={statistics.funnel.map((stage) => ({
                                    label: stage.label,
                                    count: stage.count,
                                    note: `· ${stage.share} %`,
                                }))}
                            />
                        </CardContent>
                    </Card>

                    <Card className="animate-rise">
                        <CardHeader>
                            <CardTitle>Répartition par statut</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BarList data={statistics.by_status} />
                        </CardContent>
                    </Card>

                    <Card className="animate-rise">
                        <CardHeader>
                            <CardTitle>
                                Plateformes les plus demandées
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BarList data={statistics.top_marketplaces} />
                        </CardContent>
                    </Card>

                    <Card className="animate-rise">
                        <CardHeader>
                            <CardTitle>Villes de livraison</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BarList data={statistics.top_cities} />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

function Stat({
    label,
    value,
    hint,
    icon: Icon,
    delay = 0,
}: {
    label: string;
    value: string;
    hint?: string;
    icon: LucideIcon;
    delay?: number;
}) {
    return (
        <Card
            className="animate-rise transition-shadow duration-200 hover:shadow-md"
            style={{ animationDelay: `${delay}ms` }}
        >
            <CardContent className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-sm text-muted-foreground">{label}</p>
                    <p className="mt-1 truncate text-2xl font-semibold tabular-nums">
                        {value}
                    </p>
                    {hint && (
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {hint}
                        </p>
                    )}
                </div>
                <Icon className="size-5 shrink-0 text-muted-foreground" />
            </CardContent>
        </Card>
    );
}

function formatAmount(amount: string, currency: string): string {
    const value = new Intl.NumberFormat('fr-FR').format(
        Math.round(Number(amount)),
    );

    return `${value} ${currency}`;
}

StatisticsPage.layout = {
    breadcrumbs: [{ title: 'Statistiques', href: statisticsRoute() }],
};

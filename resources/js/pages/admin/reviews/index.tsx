import { Form, Head, Link } from '@inertiajs/react';
import { Eye, EyeOff, MessageSquareQuote, Star } from 'lucide-react';

import { Pagination } from '@/components/admin/pagination';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { show as customerShow } from '@/routes/admin/customers';
import { approval, index as reviewsIndex } from '@/routes/admin/reviews';
import type { Paginated, ReviewRow } from '@/types';

type Props = {
    reviews: Paginated<ReviewRow>;
    summary: { total: number; average: number };
};

const STARS = [1, 2, 3, 4, 5];

/**
 * The rating, drawn.
 *
 * The number is repeated in the accessible name rather than left to the icons:
 * five identical shapes tell a screen reader nothing about how many are filled.
 */
function Rating({ rating }: { rating: number }) {
    return (
        <span
            className="flex items-center gap-0.5"
            aria-label={`${rating} sur 5`}
        >
            {STARS.map((star) => (
                <Star
                    key={star}
                    aria-hidden
                    className={cn(
                        'size-4',
                        star <= rating
                            ? 'fill-accent-brand text-accent-brand'
                            : 'text-muted-foreground/40',
                    )}
                />
            ))}
        </span>
    );
}

export default function ReviewsIndex({ reviews: page, summary }: Props) {
    return (
        <>
            <Head title="Avis" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
                <div>
                    <h1 className="font-display text-2xl font-extrabold tracking-tight">
                        Avis
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Ce que les clients ont dit à l'assistant.
                    </p>
                </div>

                <div className="grid gap-3 sm:grid-cols-2">
                    <div className="rounded-xl border bg-card p-4">
                        <p className="text-sm text-muted-foreground">
                            Avis reçus
                        </p>
                        <p className="mt-1 text-2xl font-semibold tabular-nums">
                            {summary.total}
                        </p>
                    </div>

                    <div className="rounded-xl border bg-card p-4">
                        <p className="text-sm text-muted-foreground">
                            Note moyenne
                        </p>
                        <p className="mt-1 flex items-center gap-2 text-2xl font-semibold tabular-nums">
                            {summary.total === 0
                                ? '—'
                                : summary.average.toFixed(1)}
                            {summary.total > 0 && (
                                <Star
                                    aria-hidden
                                    className="size-5 fill-accent-brand text-accent-brand"
                                />
                            )}
                        </p>
                    </div>
                </div>

                {page.data.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-2 rounded-xl border border-dashed p-10 text-center">
                        <MessageSquareQuote className="size-6 text-muted-foreground" />
                        <p className="font-medium">Aucun avis pour l'instant</p>
                        <p className="max-w-sm text-sm text-muted-foreground">
                            Les clients peuvent en laisser un depuis le menu de
                            l'assistant, avec ou sans commande à leur nom.
                        </p>
                    </div>
                ) : (
                    <div className="flex flex-1 flex-col gap-3">
                        <ul className="space-y-3">
                            {page.data.map((review) => (
                                <li
                                    key={review.id}
                                    className="rounded-xl border bg-card p-4"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <Rating rating={review.rating} />

                                        <div className="flex items-center gap-3 text-xs text-muted-foreground">
                                            <span>{review.channel_label}</span>
                                            <span>
                                                {formatDate(review.created_at)}
                                            </span>
                                        </div>
                                    </div>

                                    {review.comment && (
                                        <p className="mt-3 text-sm whitespace-pre-line">
                                            {review.comment}
                                        </p>
                                    )}

                                    <div className="mt-3 flex flex-wrap items-center justify-between gap-3">
                                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                            {review.customer ? (
                                                <Link
                                                    href={customerShow(
                                                        review.customer.id,
                                                    )}
                                                    className="font-medium text-primary underline-offset-4 hover:underline"
                                                >
                                                    {review.customer.full_name}
                                                </Link>
                                            ) : (
                                                <span>Anonyme</span>
                                            )}

                                            {review.reference && (
                                                <span className="font-mono">
                                                    {review.reference}
                                                </span>
                                            )}
                                        </div>

                                        {/* Rien n'atteint la vitrine tout seul.
                                            Une bascule, dans les deux sens :
                                            retirer un avis doit être au moins
                                            aussi facile que le publier. */}
                                        <Form
                                            {...approval.form(review.id)}
                                            options={{ preserveScroll: true }}
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    size="sm"
                                                    variant={
                                                        review.is_approved
                                                            ? 'secondary'
                                                            : 'default'
                                                    }
                                                    disabled={processing}
                                                >
                                                    {review.is_approved ? (
                                                        <>
                                                            <EyeOff className="size-4" />
                                                            Retirer du site
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Eye className="size-4" />
                                                            Publier
                                                        </>
                                                    )}
                                                </Button>
                                            )}
                                        </Form>
                                    </div>
                                </li>
                            ))}
                        </ul>

                        <Pagination meta={page.meta} noun="avis" />
                    </div>
                )}
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

ReviewsIndex.layout = {
    breadcrumbs: [{ title: 'Avis', href: reviewsIndex() }],
};

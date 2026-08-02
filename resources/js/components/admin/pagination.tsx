import { Link } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import type { PaginationLink, PaginationMeta } from '@/types';

/**
 * Laravel ships previous/next labels as HTML entities. They are decoded to
 * plain text here rather than injected as markup, so nothing on this page ever
 * renders raw HTML.
 */
function labelOf(link: PaginationLink): string {
    if (link.label.includes('laquo')) {
        return '‹';
    }

    if (link.label.includes('raquo')) {
        return '›';
    }

    return link.label;
}

/**
 * The whole `meta` block is taken rather than its parts: the numbered links
 * live there, next to the counts they belong with.
 *
 * @param noun singular name of what is listed, pluralised with a trailing `s`.
 */
export function Pagination({
    meta,
    noun,
}: {
    meta: PaginationMeta;
    noun: string;
}) {
    if (meta.total === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 px-2 py-3">
            <p className="text-sm text-muted-foreground">
                {meta.from ?? 0}–{meta.to ?? 0} sur {meta.total} {noun}
                {meta.total > 1 ? 's' : ''}
            </p>

            <nav className="flex flex-wrap gap-1" aria-label="Pagination">
                {meta.links.map((link, index) =>
                    link.url ? (
                        <Link
                            key={`${link.label}-${index}`}
                            href={link.url}
                            preserveScroll
                            preserveState
                            className={cn(
                                'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm transition-colors',
                                link.active
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'hover:bg-accent',
                            )}
                        >
                            {labelOf(link)}
                        </Link>
                    ) : (
                        <span
                            key={`${link.label}-${index}`}
                            className="inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm text-muted-foreground opacity-50"
                        >
                            {labelOf(link)}
                        </span>
                    ),
                )}
            </nav>
        </div>
    );
}

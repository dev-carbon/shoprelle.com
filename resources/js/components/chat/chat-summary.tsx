import { ExternalLink, ImageIcon } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import type { ChatSummary as Summary } from '@/types';

/**
 * The recap the customer checks before confirming. Rendered as a real card
 * rather than a chat bubble, because this is the moment they verify everything.
 */
export function ChatSummary({ summary }: { summary: Summary }) {
    const { customer, items } = summary;

    return (
        <div className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-sm font-semibold">
                Récapitulatif de la demande
            </h2>

            <ul className="mt-3 space-y-3">
                {items.map((item, index) => (
                    <li
                        key={`${item.product_url}-${index}`}
                        className="rounded-lg border bg-background p-3"
                    >
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary">
                                {item.marketplace_label}
                            </Badge>
                            <span className="text-xs text-muted-foreground">
                                Produit n°{index + 1}
                            </span>
                        </div>

                        <a
                            href={item.product_url}
                            target="_blank"
                            rel="noopener noreferrer nofollow"
                            className="mt-2 flex items-start gap-1.5 text-sm text-primary hover:underline"
                        >
                            <span className="line-clamp-2 break-all">
                                {item.product_url}
                            </span>
                            <ExternalLink className="mt-0.5 size-3.5 shrink-0" />
                        </a>

                        <dl className="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-sm sm:grid-cols-4">
                            <Field
                                label="Quantité"
                                value={String(item.quantity)}
                            />
                            <Field label="Couleur" value={item.color} />
                            <Field label="Taille" value={item.size} />
                            <Field
                                label="Prix affiché"
                                value={
                                    item.declared_price
                                        ? `${item.declared_price} ${item.declared_currency ?? ''}`.trim()
                                        : null
                                }
                            />
                        </dl>

                        {item.comment && (
                            <p className="mt-2 text-sm text-muted-foreground">
                                « {item.comment} »
                            </p>
                        )}

                        {item.attachment_count > 0 && (
                            <p className="mt-2 flex items-center gap-1.5 text-xs text-muted-foreground">
                                <ImageIcon className="size-3.5" />
                                {item.attachment_count} capture
                                {item.attachment_count > 1 ? 's' : ''} jointe
                                {item.attachment_count > 1 ? 's' : ''}
                            </p>
                        )}
                    </li>
                ))}
            </ul>

            <div className="mt-4 rounded-lg bg-muted/50 p-3">
                <h3 className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                    Livraison
                </h3>
                <dl className="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                    <Field label="Nom" value={customer.full_name} />
                    <Field label="Téléphone" value={customer.phone} />
                    <Field
                        label="Destination"
                        value={
                            customer.city && customer.country_label
                                ? `${customer.city}, ${customer.country_label}`
                                : null
                        }
                    />
                    <Field label="Email" value={customer.email} />
                </dl>
            </div>
        </div>
    );
}

function Field({ label, value }: { label: string; value: string | null }) {
    return (
        <div>
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="truncate">{value || '—'}</dd>
        </div>
    );
}

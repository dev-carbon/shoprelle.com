import { Form, Head, Link } from '@inertiajs/react';
import { Check, Inbox, RotateCcw } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { show as customerShow } from '@/routes/admin/customers';
import { index as messagesIndex, update } from '@/routes/admin/messages';

type MessageRow = {
    id: number;
    message: string;
    reply_to: string | null;
    channel: string;
    customer_name: string | null;
    customer_id: number | null;
    handled_at: string | null;
    handled_by: string | null;
    created_at: string | null;
};

type Props = {
    messages: MessageRow[];
    pending: number;
};

/**
 * Ce que les visiteurs nous ont écrit depuis l'assistant.
 *
 * Les messages en attente d'abord, le plus ancien en tête : une file d'attente
 * se traite dans l'ordre où elle s'est formée. Un message traité reste visible,
 * en retrait — savoir ce qui a déjà été répondu fait partie du travail.
 */
export default function MessagesIndex({ messages, pending }: Props) {
    return (
        <>
            <Head title="Messages" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Messages</h1>
                    <p className="text-sm text-muted-foreground">
                        Ce que les visiteurs nous écrivent depuis l'assistant.
                        {pending > 0 && ` ${pending} en attente de réponse.`}
                    </p>
                </div>

                {messages.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center">
                        <Inbox className="mx-auto size-6 text-muted-foreground" />
                        <p className="mt-3 text-sm text-muted-foreground">
                            Aucun message pour le moment.
                        </p>
                    </div>
                ) : (
                    <ul className="grid gap-3">
                        {messages.map((message) => (
                            <li
                                key={message.id}
                                className={cn(
                                    'rounded-xl border bg-card p-4',
                                    message.handled_at && 'opacity-60',
                                )}
                            >
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm whitespace-pre-line">
                                            {message.message}
                                        </p>

                                        <p className="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                            <span>
                                                {formatDate(message.created_at)}
                                            </span>
                                            <span>· {message.channel}</span>

                                            {/* Le moyen de rappel, tel qu'il a
                                                été écrit. Il est facultatif :
                                                sans lui, on ne peut pas
                                                répondre, et l'écran le dit
                                                plutôt que de le laisser
                                                deviner. */}
                                            {message.reply_to ? (
                                                <span className="font-medium text-foreground">
                                                    · {message.reply_to}
                                                </span>
                                            ) : (
                                                <span>
                                                    · sans moyen de réponse
                                                </span>
                                            )}

                                            {message.customer_id && (
                                                <Link
                                                    href={customerShow(
                                                        message.customer_id,
                                                    )}
                                                    className="text-primary hover:underline"
                                                >
                                                    ·{' '}
                                                    {message.customer_name ??
                                                        'Client'}
                                                </Link>
                                            )}

                                            {message.handled_by && (
                                                <span>
                                                    · traité par{' '}
                                                    {message.handled_by}
                                                </span>
                                            )}
                                        </p>
                                    </div>

                                    <Form
                                        {...update.form(message.id)}
                                        options={{ preserveScroll: true }}
                                    >
                                        <Button
                                            type="submit"
                                            variant={
                                                message.handled_at
                                                    ? 'ghost'
                                                    : 'outline'
                                            }
                                            size="sm"
                                        >
                                            {message.handled_at ? (
                                                <>
                                                    <RotateCcw className="size-4" />
                                                    Rouvrir
                                                </>
                                            ) : (
                                                <>
                                                    <Check className="size-4" />
                                                    Marquer traité
                                                </>
                                            )}
                                        </Button>
                                    </Form>
                                </div>
                            </li>
                        ))}
                    </ul>
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
        hour: '2-digit',
        minute: '2-digit',
    });
}

MessagesIndex.layout = {
    breadcrumbs: [{ title: 'Messages', href: messagesIndex() }],
};

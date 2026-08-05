import { Head } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';

import { cn } from '@/lib/utils';
import { index as logsRoute } from '@/routes/admin/logs';

type LogEntry = {
    timestamp: string;
    level: string;
    message: string;
    detail: string | null;
};

type Props = {
    entries: LogEntry[];
};

/**
 * ── Le journal des erreurs ──────────────────────────────────────────────────
 *
 * Il n'y a pas de service de suivi d'erreurs externe : quand un email
 * d'alerte arrive, c'est ici qu'on vient lire le détail. Le serveur envoie les
 * dernières entrées du fichier de log, les plus récentes d'abord ; la pile
 * d'appels reste repliée derrière un résumé, parce qu'on vient d'abord voir
 * s'il s'est passé quelque chose, et seulement ensuite quoi exactement.
 */

/** La couleur dit la gravité avant que le libellé ne soit lu. */
const LEVEL_STYLES: Record<string, string> = {
    EMERGENCY: 'bg-destructive/15 text-destructive',
    ALERT: 'bg-destructive/15 text-destructive',
    CRITICAL: 'bg-destructive/15 text-destructive',
    ERROR: 'bg-destructive/15 text-destructive',
    WARNING: 'bg-warning/15 text-warning',
    NOTICE: 'bg-muted text-muted-foreground',
    INFO: 'bg-muted text-muted-foreground',
    DEBUG: 'bg-muted text-muted-foreground',
};

export default function LogsIndex({ entries }: Props) {
    return (
        <>
            <Head title="Journal" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
                <div>
                    <h1 className="font-display text-2xl font-extrabold tracking-tight">
                        Journal
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Les dernières erreurs du site, les plus récentes en
                        premier. C'est le détail des alertes reçues par email.
                    </p>
                </div>

                {entries.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border bg-card p-10 text-center">
                        <CheckCircle2 className="size-8 text-success" />
                        <p className="font-medium">Rien à signaler</p>
                        <p className="text-sm text-muted-foreground">
                            Le journal est vide : aucune erreur enregistrée
                            récemment.
                        </p>
                    </div>
                ) : (
                    <ul className="space-y-3">
                        {entries.map((entry, index) => (
                            <li
                                key={`${entry.timestamp}-${index}`}
                                className="rounded-xl border bg-card p-4"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <span
                                        className={cn(
                                            'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                            LEVEL_STYLES[entry.level] ??
                                                'bg-muted text-muted-foreground',
                                        )}
                                    >
                                        {entry.level}
                                    </span>
                                    <time className="text-xs text-muted-foreground tabular-nums">
                                        {entry.timestamp}
                                    </time>
                                </div>

                                <p className="mt-2 font-mono text-sm break-all">
                                    {entry.message}
                                </p>

                                {entry.detail && (
                                    <details className="mt-2">
                                        <summary className="cursor-pointer text-xs text-muted-foreground select-none hover:text-foreground">
                                            Voir le détail
                                        </summary>
                                        <pre className="mt-2 max-h-80 overflow-auto rounded-lg bg-muted p-3 font-mono text-xs leading-relaxed whitespace-pre-wrap">
                                            {entry.detail}
                                        </pre>
                                    </details>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

LogsIndex.layout = {
    breadcrumbs: [{ title: 'Journal', href: logsRoute() }],
};

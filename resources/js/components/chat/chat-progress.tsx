import { Check } from 'lucide-react';

import { cn } from '@/lib/utils';
import type { ChatProgress as Progress } from '@/types';

/**
 * Where the customer stands in their request.
 *
 * Knowing there are six short stages, and which one is running, is what turns a
 * long form into an errand someone is walking you through.
 */
export function ChatProgress({ progress }: { progress: Progress }) {
    const percent = Math.round(
        ((progress.current - 1) / (progress.total - 1)) * 100,
    );

    return (
        <div className="animate-enter">
            <div className="flex items-baseline justify-between gap-4">
                <p className="text-sm font-medium">Votre demande</p>
                <p className="text-sm text-muted-foreground">
                    Étape {progress.current} sur {progress.total}
                </p>
            </div>

            <div
                className="mt-2 h-1 overflow-hidden rounded-full bg-border"
                role="progressbar"
                aria-valuenow={progress.current}
                aria-valuemin={1}
                aria-valuemax={progress.total}
                aria-label="Progression de votre demande"
            >
                <div
                    className="h-full rounded-full bg-primary transition-[width] duration-500 ease-out"
                    style={{ width: `${Math.max(percent, 4)}%` }}
                />
            </div>

            <ol className="mt-3 hidden flex-wrap gap-x-4 gap-y-1.5 sm:flex">
                {progress.milestones.map((milestone) => (
                    <li
                        key={milestone.label}
                        className={cn(
                            'flex items-center gap-1.5 text-xs',
                            milestone.state === 'current' &&
                                'font-medium text-foreground',
                            milestone.state === 'done' &&
                                'text-muted-foreground',
                            milestone.state === 'todo' &&
                                'text-muted-foreground/60',
                        )}
                    >
                        {milestone.state === 'done' ? (
                            <Check className="size-3 text-primary" />
                        ) : (
                            <span
                                aria-hidden
                                className={cn(
                                    'size-1.5 rounded-full',
                                    milestone.state === 'current'
                                        ? 'bg-primary'
                                        : 'bg-border',
                                )}
                            />
                        )}
                        {milestone.label}
                    </li>
                ))}
            </ol>
        </div>
    );
}

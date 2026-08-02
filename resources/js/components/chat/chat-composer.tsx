import { router } from '@inertiajs/react';
import { ArrowRight, Paperclip, RotateCcw, Send } from 'lucide-react';
import { useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { message, menu, skip, upload } from '@/routes/chat';
import type { ChatStep } from '@/types';

const VISIT_OPTIONS = {
    preserveScroll: true,
    preserveState: false,
} as const;

/**
 * The answer control for the current step.
 *
 * The server decides which control to show through `input_type`, so a new step
 * needs no change here — and the same descriptor drives Telegram's inline
 * keyboards on the other channels.
 */
export function ChatComposer({
    step,
    attachmentCount,
    disabled,
}: {
    step: ChatStep;
    attachmentCount: number;
    disabled: boolean;
}) {
    const [value, setValue] = useState('');
    const [processing, setProcessing] = useState(false);
    const fileInput = useRef<HTMLInputElement>(null);

    const busy = processing || disabled;

    const send = (text: string) => {
        setProcessing(true);
        router.post(
            message().url,
            { message: text },
            {
                ...VISIT_OPTIONS,
                onFinish: () => {
                    setProcessing(false);
                    setValue('');
                },
            },
        );
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (busy) {
            return;
        }

        if (value.trim() === '' && !step.optional) {
            return;
        }

        send(value.trim());
    };

    const passStep = () => {
        setProcessing(true);
        router.post(
            skip().url,
            {},
            { ...VISIT_OPTIONS, onFinish: () => setProcessing(false) },
        );
    };

    const backToMenu = () => {
        setProcessing(true);
        router.post(
            menu().url,
            {},
            { ...VISIT_OPTIONS, onFinish: () => setProcessing(false) },
        );
    };

    const sendFile = (file: File) => {
        setProcessing(true);
        router.post(
            upload().url,
            { screenshot: file },
            {
                ...VISIT_OPTIONS,
                forceFormData: true,
                onFinish: () => {
                    setProcessing(false);

                    if (fileInput.current) {
                        fileInput.current.value = '';
                    }
                },
            },
        );
    };

    if (step.input_type === 'none') {
        return (
            <div className="flex flex-wrap justify-center gap-2">
                <Button onClick={backToMenu} disabled={busy}>
                    {busy && <Spinner />}
                    <RotateCcw className="size-4" />
                    Faire une nouvelle demande
                </Button>
            </div>
        );
    }

    if (step.input_type === 'choice') {
        return (
            /* Les réponses proposées sont le principal moyen d'avancer : elles
               doivent se voir et se donner pour cliquables. `secondary` ne le
               permettait pas — cette variante est crème, exactement la teinte
               qu'avait la page, et les puces disparaissaient dedans.

               Blanches, cernées, ombrées, elles se détachent du fond ; le bleu
               ne vient qu'au survol, pour que dix propositions côte à côte ne
               forment pas un mur de couleur. */
            <div className="flex flex-wrap justify-center gap-2">
                {step.options.map((option) => (
                    <Button
                        key={option.value}
                        type="button"
                        variant="outline"
                        disabled={busy}
                        onClick={() => send(option.value)}
                        className={cn(
                            'rounded-full border-border bg-card font-medium shadow-sm transition-colors',
                            'hover:border-primary hover:bg-primary/5 hover:text-primary',
                            option.value === 'restart' &&
                                'text-muted-foreground',
                        )}
                    >
                        {option.label}
                    </Button>
                ))}
            </div>
        );
    }

    if (step.input_type === 'file') {
        return (
            <div className="flex flex-wrap items-center justify-center gap-2">
                <input
                    ref={fileInput}
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/heic"
                    className="hidden"
                    onChange={(event) => {
                        const file = event.target.files?.[0];

                        if (file) {
                            sendFile(file);
                        }
                    }}
                />
                <Button
                    type="button"
                    variant="secondary"
                    disabled={busy}
                    onClick={() => fileInput.current?.click()}
                >
                    {busy ? <Spinner /> : <Paperclip className="size-4" />}
                    Joindre une capture
                </Button>
                <Button type="button" disabled={busy} onClick={passStep}>
                    {attachmentCount > 0 ? 'Continuer' : 'Passer cette étape'}
                    <ArrowRight className="size-4" />
                </Button>
            </div>
        );
    }

    const isLongText = step.input_type === 'long_text';

    return (
        <form onSubmit={submit} className="flex items-end gap-2">
            {isLongText ? (
                <Textarea
                    value={value}
                    onChange={(event) => setValue(event.target.value)}
                    placeholder={step.placeholder ?? 'Votre réponse…'}
                    rows={2}
                    maxLength={step.max_length ?? undefined}
                    disabled={busy}
                    autoFocus
                    className="min-h-11 resize-none"
                    onKeyDown={(event) => {
                        if (event.key === 'Enter' && !event.shiftKey) {
                            submit(event);
                        }
                    }}
                />
            ) : (
                <Input
                    value={value}
                    onChange={(event) => setValue(event.target.value)}
                    placeholder={step.placeholder ?? 'Votre réponse…'}
                    maxLength={step.max_length ?? undefined}
                    disabled={busy}
                    autoFocus
                    inputMode={inputModeFor(step)}
                    type={step.input_type === 'email' ? 'email' : 'text'}
                    className="h-11"
                />
            )}

            {step.optional && (
                <Button
                    type="button"
                    variant="ghost"
                    className="h-11"
                    disabled={busy}
                    onClick={passStep}
                >
                    Passer
                </Button>
            )}

            <Button
                type="submit"
                size="icon"
                className="size-11 shrink-0"
                disabled={busy || (value.trim() === '' && !step.optional)}
                aria-label="Envoyer"
            >
                {busy ? <Spinner /> : <Send className="size-4" />}
            </Button>
        </form>
    );
}

function inputModeFor(
    step: ChatStep,
): 'numeric' | 'decimal' | 'url' | 'tel' | 'text' {
    switch (step.input_type) {
        case 'number':
            return 'numeric';
        case 'decimal':
            return 'decimal';
        case 'url':
            return 'url';
        default:
            return step.step.includes('phone') ? 'tel' : 'text';
    }
}

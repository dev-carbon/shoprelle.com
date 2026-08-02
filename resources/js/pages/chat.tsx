import { Head, Link, router, usePage } from '@inertiajs/react';
import { CheckCircle2, RotateCcw } from 'lucide-react';
import { useEffect, useLayoutEffect, useRef } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';
import { ChatBubble } from '@/components/chat/chat-bubble';
import { ChatComposer } from '@/components/chat/chat-composer';
import { ChatProgress } from '@/components/chat/chat-progress';
import { ChatSummary } from '@/components/chat/chat-summary';
import { ThemeSwitcher } from '@/components/theme-switcher';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { restart } from '@/routes/chat';
import type { Conversation } from '@/types';

type Props = {
    conversation: Conversation;
};

/**
 * `useLayoutEffect` warns when rendered on the server, where there is no layout
 * to read. Falling back to `useEffect` there keeps the console clean without
 * giving up the pre-paint timing in the browser, which is the whole point.
 */
const useIsomorphicLayoutEffect =
    typeof window !== 'undefined' ? useLayoutEffect : useEffect;

export default function Chat({ conversation }: Props) {
    const { errors } = usePage().props;
    const transcript = useRef<HTMLDivElement>(null);

    // Keep the newest message in view as the conversation grows.
    //
    // Before paint, and without animation: every answer is sent with
    // `preserveState: false`, so the transcript is a fresh element starting at
    // the top. Scrolling it after paint showed that top for a frame, and
    // animating there made each reply look like a page reload.
    useIsomorphicLayoutEffect(() => {
        transcript.current?.scrollTo({ top: transcript.current.scrollHeight });
    }, [conversation.messages.length]);

    const errorMessage = Object.values(errors ?? {})[0];
    const { progress } = conversation.current;

    // The banded surface from the landing page is carried through here: the
    // bot's white bubbles and the visitor's blue ones both sit on it.
    return (
        // Pinned to the viewport rather than growing with the conversation:
        // the transcript owns the scrolling, which is what lets it be scrolled
        // to the newest message. On `min-h-svh` the page grew instead, the
        // transcript never overflowed, and scrollTo had nothing to move.
        <div className="flex h-svh flex-col bg-surface-alt">
            <Head title="Votre concierge d'achat" />

            <header className="shrink-0 border-b bg-surface-alt">
                <div className="mx-auto flex w-full max-w-3xl items-center justify-between gap-4 px-4 py-3">
                    <Link
                        href={home()}
                        className="flex items-center gap-2.5 font-semibold"
                    >
                        <span className="flex size-8 items-center justify-center rounded-lg bg-accent-brand">
                            <AppLogoIcon className="size-4 fill-current text-accent-brand-foreground" />
                        </span>
                        Shoprelle
                    </Link>

                    <div className="flex items-center gap-1">
                        <ThemeSwitcher />

                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                                router.post(
                                    restart().url,
                                    {},
                                    {
                                        preserveScroll: true,
                                        preserveState: false,
                                    },
                                )
                            }
                        >
                            <RotateCcw className="size-4" />
                            Recommencer
                        </Button>
                    </div>
                </div>

                {progress && (
                    <div className="mx-auto w-full max-w-3xl px-4 pb-3">
                        <ChatProgress progress={progress} />
                    </div>
                )}
            </header>

            {/* `min-h-0` lets this flex child shrink below its content, which
                is what allows the transcript inside it to scroll. */}
            <main className="mx-auto flex min-h-0 w-full max-w-3xl flex-1 flex-col px-4">
                <div
                    ref={transcript}
                    className="flex-1 space-y-4 overflow-y-auto py-6"
                    role="log"
                    aria-live="polite"
                    aria-label="Conversation avec Shopbot"
                >
                    {conversation.messages.map((message) => (
                        <ChatBubble key={message.id} message={message} />
                    ))}

                    {conversation.summary && (
                        <ChatSummary summary={conversation.summary} />
                    )}

                    {conversation.completed && conversation.reference && (
                        <div className="animate-rise overflow-hidden rounded-xl border bg-card shadow-sm">
                            <div className="p-5">
                                <p className="flex items-center gap-2 text-sm font-medium text-success">
                                    <CheckCircle2 className="size-4" />
                                    Demande enregistrée
                                </p>
                                <p className="mt-1.5 text-sm text-muted-foreground">
                                    Notez cette référence : elle vous permet de
                                    suivre votre demande à tout moment.
                                </p>
                            </div>

                            {/* The reference is what the visitor leaves with,
                                so it gets the brand gold to itself. */}
                            <div className="bg-accent-brand px-5 py-4 text-accent-brand-foreground">
                                <p className="font-mono text-2xl font-semibold tracking-tight">
                                    {conversation.reference}
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </main>

            <div className="shrink-0 border-t bg-surface-alt">
                <div className="mx-auto w-full max-w-3xl space-y-3 px-4 py-4">
                    {errorMessage && (
                        <Alert variant="destructive" className="animate-enter">
                            <AlertDescription>{errorMessage}</AlertDescription>
                        </Alert>
                    )}

                    <ChatComposer
                        step={conversation.current}
                        attachmentCount={conversation.attachment_count}
                        disabled={false}
                    />

                    <p className="text-center text-xs text-muted-foreground">
                        Vous trouvez le produit, nous nous occupons du reste.
                    </p>
                </div>
            </div>
        </div>
    );
}

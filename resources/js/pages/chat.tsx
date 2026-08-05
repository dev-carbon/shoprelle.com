import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowRight, Check, CheckCircle2, Copy, RotateCcw } from 'lucide-react';
import { useEffect, useLayoutEffect, useRef } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';
import { ChatBubble } from '@/components/chat/chat-bubble';
import { ChatComposer } from '@/components/chat/chat-composer';
import { ChatProgress } from '@/components/chat/chat-progress';
import { ChatSummary } from '@/components/chat/chat-summary';
import { ThemeSwitcher } from '@/components/theme-switcher';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useClipboard } from '@/hooks/use-clipboard';
import { home } from '@/routes';
import { restart } from '@/routes/chat';
import { index as orders } from '@/routes/orders';
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

/**
 * La carte qui clôt la conversation, avec la référence en or.
 *
 * La référence est la seule chose que le visiteur emporte, et on la lui
 * demandait des yeux : à recopier à la main depuis un téléphone, c'est elle
 * qu'on perd. Le bouton la met dans le presse-papiers, et la confirmation se
 * joue dans le bouton lui-même — un toast par-dessus une conversation serait
 * un message de plus.
 *
 * Le lien vers « Mes demandes » part d'ici parce que c'est ici qu'on apprend
 * que cet espace existe : au moment où l'on vient de recevoir la clé qui y
 * donne accès.
 */
function ReferenceCard({ reference }: { reference: string }) {
    const [copied, copy] = useClipboard();

    return (
        <div className="animate-rise overflow-hidden rounded-3xl border bg-card shadow-md">
            <div className="p-6">
                <p className="flex items-center gap-2 text-sm font-medium text-success">
                    <CheckCircle2 className="size-4" />
                    Demande enregistrée
                </p>
                <p className="mt-2 text-sm text-muted-foreground">
                    Notez cette référence : elle vous permet de suivre votre
                    demande à tout moment.
                </p>
            </div>

            {/* The reference is what the visitor leaves with, so it gets the
                brand gold to itself. */}
            <div className="flex items-center justify-between gap-4 bg-accent-brand px-6 py-5 text-accent-brand-foreground">
                <p className="font-mono text-2xl font-bold tracking-tight">
                    {reference}
                </p>

                <button
                    type="button"
                    onClick={() => copy(reference)}
                    className="inline-flex shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition-colors outline-none hover:bg-black/10 focus-visible:ring-[3px] focus-visible:ring-black/30"
                >
                    {copied === reference ? (
                        <Check className="size-4" />
                    ) : (
                        <Copy className="size-4" />
                    )}
                    {copied === reference ? 'Copié' : 'Copier'}
                </button>
            </div>

            <div className="border-t p-6">
                <Link
                    href={orders()}
                    className="inline-flex items-center gap-1.5 text-sm font-semibold text-primary underline-offset-4 hover:underline"
                >
                    Suivre ma demande dans mon espace
                    <ArrowRight className="size-4" />
                </Link>
            </div>
        </div>
    );
}

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
        // Le même fond que les mocks de conversation de la vitrine : la teinte
        // `muted` sous les pois. L'en-tête et la barre de saisie gardent leur
        // fond propre et se détachent dessus.
        <div className="relative flex h-svh flex-col bg-muted/50">
            <Head title="Votre concierge d'achat" />

            {/* Les pois du wordmark du footer, en fond du chat : la texture
                d'un fil de discussion — les losanges disaient « atelier », les
                pois disent « conversation ». Masqués en dégradé, comme partout
                ailleurs — une trame qui s'arrête net sur un bord se lit comme
                un défaut d'affichage. */}
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-dots-chat [mask-image:radial-gradient(95%_75%_at_50%_38%,black,transparent)] opacity-70"
            />

            <header className="relative shrink-0 border-b bg-background">
                <div className="mx-auto flex w-full max-w-3xl items-center justify-between gap-4 px-6 py-5 sm:px-8">
                    <Link
                        href={home()}
                        className="flex items-center gap-2.5 font-display font-extrabold tracking-tight"
                    >
                        <span className="flex size-9 items-center justify-center rounded-xl bg-accent-brand">
                            <AppLogoIcon className="size-4 text-accent-brand-foreground" />
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
                    <div className="mx-auto w-full max-w-3xl px-6 pb-5 sm:px-8">
                        <ChatProgress progress={progress} />
                    </div>
                )}
            </header>

            {/* `min-h-0` lets this flex child shrink below its content, which
                is what allows the transcript inside it to scroll. */}
            <main className="relative mx-auto flex min-h-0 w-full max-w-3xl flex-1 flex-col px-6 sm:px-8">
                <div
                    ref={transcript}
                    className="flex-1 space-y-7 overflow-y-auto py-10"
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
                        <ReferenceCard reference={conversation.reference} />
                    )}
                </div>
            </main>

            <div className="relative shrink-0 border-t bg-background">
                <div className="mx-auto w-full max-w-3xl space-y-5 px-6 py-6 sm:px-8">
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

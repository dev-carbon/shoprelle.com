import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';
import type { ChatMessage } from '@/types';

/**
 * One line of the transcript.
 *
 * The bot speaks under the brand's own mark — the gold tile every other screen
 * opens with — rather than a generic robot glyph: the promise is that the
 * service is handling this, not that a machine is.
 *
 * Bot text may contain newlines — the engine uses them for tracking results and
 * the help subjects — so whitespace is preserved.
 *
 * Words are allowed to break mid-way, because the customer pastes product URLs:
 * a link has no spaces to wrap at, so without this it widens the bubble past the
 * transcript and puts a horizontal scrollbar on the conversation.
 */
export function ChatBubble({ message }: { message: ChatMessage }) {
    const isBot = message.author === 'bot';

    return (
        <div
            className={cn(
                'flex w-full animate-enter gap-3',
                isBot ? 'justify-start' : 'justify-end',
            )}
        >
            {isBot && (
                <div
                    aria-hidden
                    className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-accent-brand"
                >
                    <AppLogoIcon className="size-4 text-accent-brand-foreground" />
                </div>
            )}

            {/* Une bulle plus large et un texte d'un point plus grand : la
                conversation est le produit, et c'est le seul endroit du site où
                l'on lit ligne à ligne pendant plusieurs minutes. */}
            <div
                className={cn(
                    'max-w-[85%] px-5 py-3 text-[0.9375rem] leading-relaxed break-words whitespace-pre-line sm:max-w-[75%]',
                    isBot
                        ? 'rounded-3xl rounded-tl-lg border bg-card text-card-foreground shadow-sm'
                        : 'rounded-3xl rounded-tr-lg bg-primary text-primary-foreground shadow-md shadow-primary/20',
                )}
            >
                {message.text}
            </div>
        </div>
    );
}

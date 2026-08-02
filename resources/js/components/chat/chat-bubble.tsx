import { cn } from '@/lib/utils';
import type { ChatMessage } from '@/types';

/**
 * One line of the transcript.
 *
 * The bot gets a named avatar rather than a generic robot glyph: the promise is
 * that someone competent is handling this, not that a machine is.
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
                'flex w-full animate-enter gap-2.5',
                isBot ? 'justify-start' : 'justify-end',
            )}
        >
            {isBot && (
                <div
                    aria-hidden
                    className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground"
                >
                    S
                </div>
            )}

            <div
                className={cn(
                    'max-w-[85%] px-4 py-2.5 text-sm leading-relaxed break-words whitespace-pre-line sm:max-w-[75%]',
                    isBot
                        ? 'rounded-2xl rounded-tl-md border bg-card text-card-foreground shadow-sm'
                        : 'rounded-2xl rounded-tr-md bg-primary text-primary-foreground',
                )}
            >
                {message.text}
            </div>
        </div>
    );
}

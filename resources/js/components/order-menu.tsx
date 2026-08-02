import { Link } from '@inertiajs/react';
import { MessagesSquare, Send } from 'lucide-react';
import type { ComponentType, ReactNode } from 'react';

import { WHATSAPP_GREEN, WhatsAppIcon } from '@/components/social-icons';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';

/**
 * Une ligne du menu : le canal, ce qu'il donne, et par où il passe.
 */
function Channel({
    icon: Icon,
    name,
    hint,
    tint,
    className,
    children,
}: {
    icon: ComponentType<{ className?: string }>;
    name: string;
    hint: string;
    tint?: string;
    className?: string;
    children: (content: ReactNode) => ReactNode;
}) {
    return (
        <DropdownMenuItem asChild className={cn('gap-3 p-3', className)}>
            {children(
                <>
                    <span
                        style={tint ? { backgroundColor: tint } : undefined}
                        className={cn(
                            'flex size-9 shrink-0 items-center justify-center rounded-lg',
                            tint
                                ? 'text-white'
                                : 'bg-primary text-primary-foreground',
                        )}
                    >
                        <Icon className="size-4" />
                    </span>

                    <span className="min-w-0">
                        <span className="block font-display text-sm font-extrabold">
                            {name}
                        </span>
                        <span className="block text-xs text-muted-foreground">
                            {hint}
                        </span>
                    </span>
                </>,
            )}
        </DropdownMenuItem>
    );
}

/**
 * Le choix du canal par lequel commander.
 *
 * Un choix plutôt qu'une imposition : quelqu'un qui vit dans WhatsApp n'ouvrira
 * pas un onglet de conversation, et l'inverse est vrai aussi. Les canaux qui ne
 * sont pas branchés ne sont pas listés — même règle que les cartes de la
 * section « L'assistant », pour ne jamais envoyer un visiteur vers une
 * conversation que personne n'écoute.
 *
 * Sur téléphone, Telegram passe en tête. La conversation y survit à la
 * fermeture de l'onglet et les réponses arrivent en notification, deux choses
 * que le chat web ne sait pas faire sur mobile. L'ordre est inversé en CSS et
 * non dans le balisage : le parcours au clavier suit le DOM, et il vaut mieux
 * qu'il reste celui du bureau, où l'on navigue effectivement au clavier.
 *
 * Le déclencheur est fourni par l'appelant : le même menu sert le bouton du
 * header et celui qui flotte en bas de page, et rien ici ne suppose l'un ou
 * l'autre.
 */
export function OrderMenu({
    trigger,
    chatHref,
    telegramUrl,
    whatsappUrl,
}: {
    trigger: ReactNode;
    chatHref: string;
    telegramUrl: string | null;
    whatsappUrl: string | null;
}) {
    const external = (href: string) => (content: ReactNode) => (
        <a href={href} target="_blank" rel="noopener noreferrer">
            {content}
        </a>
    );

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>{trigger}</DropdownMenuTrigger>

            <DropdownMenuContent
                align="end"
                sideOffset={10}
                className="w-72 p-1.5"
            >
                <DropdownMenuLabel className="px-3 pt-2 pb-1 text-xs font-normal text-muted-foreground">
                    Par où souhaitez-vous commander ?
                </DropdownMenuLabel>
                <DropdownMenuSeparator />

                <div className="flex flex-col">
                    <Channel
                        icon={MessagesSquare}
                        name="Chat web"
                        hint="Ici même, sans rien installer"
                        className={telegramUrl ? 'order-2 sm:order-1' : ''}
                    >
                        {(content) => <Link href={chatHref}>{content}</Link>}
                    </Channel>

                    {telegramUrl && (
                        <Channel
                            icon={Send}
                            name="Telegram"
                            hint="Le même assistant, dans votre messagerie"
                            className="order-1 sm:order-2"
                        >
                            {external(telegramUrl)}
                        </Channel>
                    )}

                    {whatsappUrl && (
                        <Channel
                            icon={WhatsAppIcon}
                            name="WhatsApp"
                            hint="Une personne vous répond"
                            tint={WHATSAPP_GREEN}
                            className="order-3"
                        >
                            {external(whatsappUrl)}
                        </Channel>
                    )}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

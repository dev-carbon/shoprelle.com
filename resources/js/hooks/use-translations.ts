import { usePage } from '@inertiajs/react';

/**
 * Rend la traduction d'un texte de la vitrine, ou le texte lui-même.
 */
export type Translator = (text: string) => string;

/**
 * La traduction de la vitrine, à clés françaises.
 *
 * Le français est la langue source : les clés du dictionnaire sont les
 * phrases elles-mêmes, servies par le contrôleur (`lang/en.json`) quand la
 * session est en anglais, et absentes en français. Une entrée manquante rend
 * donc sa clé — une phrase restée en français, jamais une page cassée ni un
 * identifiant technique à l'écran.
 *
 * Seule la vitrine est traduite pour l'instant, c'est pourquoi le
 * dictionnaire est un prop de la page d'accueil et non un partage global.
 */
export function useTranslations(): Translator {
    const { translations } = usePage().props as {
        translations?: Record<string, string>;
    };

    return (text: string) => translations?.[text] ?? text;
}

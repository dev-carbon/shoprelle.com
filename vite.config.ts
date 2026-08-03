import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            fonts: [
                // Le texte courant. Inter est dessinée pour être lue à l'écran
                // en petit : hauteur d'x haute, ouvertures larges, formes
                // neutres — exactement ce qu'une description de trois lignes
                // demande, et ce que la géométrie un peu bavarde de la
                // précédente rendait fatigant sur un paragraphe entier.
                bunny('Inter', {
                    weights: [400, 500, 600, 700],
                }),
                // Headings only, and only in the weights that carry them: the
                // display face is never set below 700 and reaches for 900 on
                // the hero, so the lighter cuts would be bytes nobody downloads
                // a page for. Fetched at build time, served from our own origin.
                bunny('Archivo', {
                    weights: [700, 800, 900],
                }),
            ],
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
});

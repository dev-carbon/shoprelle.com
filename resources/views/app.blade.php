<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'light') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{--
            Référencement et aperçus de lien.

            Rendus ici, dans le HTML que le serveur envoie, et non par le
            <Head> d'Inertia : celui-ci écrit ses balises en JavaScript, et
            aucun robot d'aperçu n'exécute JavaScript — ni WhatsApp, ni
            Telegram, ni Facebook. Or c'est précisément ainsi que ce service
            circule : on colle un lien dans une conversation.

            L'URL absolue est obligatoire pour og:image : la plupart des robots
            ne résolvent pas un chemin relatif.
        --}}
        @php
            $seo = config('shoprelle.seo');
            $seoImage = url($seo['image']);

            // Le JSON-LD est assemblé ici, et non écrit en Blade plus bas.
            // Blade cherche ses directives dans tout ce qu'il compile, et
            // `'@context'` comme `'@type'` — les deux clés obligatoires de
            // schema.org — ressemblent à des directives : il tente de les
            // compiler et casse le gabarit. À l'intérieur d'un bloc @php, rien
            // n'est scanné.
            $organisation = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => config('app.name'),
                'url' => url('/'),
                'logo' => url('/apple-touch-icon.png'),
                'image' => $seoImage,
                'description' => $seo['description'],
                'email' => config('shoprelle.contact.email'),
                'areaServed' => array_keys(config('shoprelle.countries')),
                // Ce qui dit aux moteurs que la page Facebook, le compte
                // Instagram et ce domaine sont une seule et même entreprise ;
                // sans quoi ils les traitent comme trois entités sans rapport.
                'sameAs' => array_values(array_filter(config('shoprelle.social'))),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        @endphp

        <meta name="description" content="{{ $seo['description'] }}">
        <link rel="canonical" href="{{ url()->current() }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:locale" content="fr_FR">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ $seo['title'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta property="og:image" content="{{ $seoImage }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="Shoprelle — vos envies voyagent jusqu'à vous.">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seo['title'] }}">
        <meta name="twitter:description" content="{{ $seo['description'] }}">
        <meta name="twitter:image" content="{{ $seoImage }}">

        <script type="application/ld+json">{!! $organisation !!}</script>

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "light" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.187 0.043 240);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        {{--
            Le titre de repli, celui que lisent les robots. Inertia le remplace
            côté client par celui de la page ; mais un crawler n'exécute rien,
            et « Shoprelle » seul ne dit pas ce que fait Shoprelle.
        --}}
        <x-inertia::head>
            <title>{{ config('shoprelle.seo.title') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>

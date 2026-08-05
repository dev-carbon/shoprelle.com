<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Destination Countries
    |--------------------------------------------------------------------------
    |
    | Countries Shoprelle currently ships to, keyed by ISO 3166-1 alpha-2 code.
    |
    | This one list drives everything: the destinations the chatbot offers, the
    | ones it refuses, the admin filter, and the countries lit up on the landing
    | page's map. Opening a country is a line here and nothing else — the map
    | already carries the ISO numeric ids for Côte d'Ivoire, Senegal, Gabon and
    | Congo, which were the next ones planned.
    |
    | Cameroon alone at launch.
    |
    */

    'countries' => [
        'CM' => 'Cameroun',
    ],

    /*
    |--------------------------------------------------------------------------
    | Announced Destinations
    |--------------------------------------------------------------------------
    |
    | Shown on the landing page as coming soon, and nowhere else. These are
    | deliberately kept out of `countries` above: the chatbot reads that list to
    | decide what it accepts, and a country announced here must still be refused
    | until it moves up. Opening one means cutting the line, not copying it.
    |
    */

    'upcoming_countries' => [
        'CI' => "Côte d'Ivoire",
        'SN' => 'Sénégal',
        'GA' => 'Gabon',
        'CG' => 'Congo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Times
    |--------------------------------------------------------------------------
    |
    | The estimate shown when a visitor hovers a destination on the landing
    | page's map, keyed by the same alpha-2 codes as the two lists above.
    |
    | Deliberately its own table rather than a third column on `countries`: that
    | list is a flat code => name map read by the chatbot, the admin filters and
    | the models, and giving it a shape none of them expect would break all of
    | them to add a line of marketing copy.
    |
    | A country with no entry here simply shows no estimate. That is the point —
    | a delay is a promise, and the page must never invent one for a destination
    | nobody has measured.
    |
    */

    'delivery_times' => [
        'CM' => '7 à 14 jours',
    ],

    /*
    |--------------------------------------------------------------------------
    | Network Figures
    |--------------------------------------------------------------------------
    |
    | The counters beside the map on the landing page.
    |
    | The number of destinations is not here: it is counted off the two lists
    | above, so it cannot drift from what the assistant actually accepts.
    |
    | These two can't be. They are claims about the business, and the rest of
    | this page is careful never to promise something it cannot back — so they
    | are unset by default and each one is simply left out of the page until it
    | is given a value. Set them from the environment once the figures are real:
    |
    |     SHOPRELLE_PARCELS_SHIPPED=1240
    |     SHOPRELLE_SATISFACTION_PERCENT=98
    |
    */

    'stats' => [
        'parcels_shipped' => env('SHOPRELLE_PARCELS_SHIPPED'),

        'satisfaction_percent' => env('SHOPRELLE_SATISFACTION_PERCENT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Currency used when an administrator quotes a request. Prices declared by
    | customers keep their own currency, since they are read off the source
    | marketplace.
    |
    */

    'quote_currency' => env('SHOPRELLE_QUOTE_CURRENCY', 'XAF'),

    'declared_currency' => env('SHOPRELLE_DECLARED_CURRENCY', 'EUR'),

    /*
    |--------------------------------------------------------------------------
    | Purchase Requests
    |--------------------------------------------------------------------------
    */

    'requests' => [
        'reference_prefix' => 'SHP',

        'max_items' => 20,

        'max_quantity_per_item' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    |
    | Customer screenshots are stored on a private disk and are only reachable
    | through an authorized route, never by public URL.
    |
    */

    'attachments' => [
        'disk' => env('SHOPRELLE_ATTACHMENT_DISK', 'local'),

        'directory' => 'purchase-requests',

        'max_size_kilobytes' => 5120,

        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'heic'],

        'max_per_item' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Promo Banner
    |--------------------------------------------------------------------------
    |
    | Le bandeau au-dessus de la vitrine. Ceci n'est que le point de départ :
    | dès que quelqu'un enregistre l'écran « Bandeau » du back-office, c'est
    | la ligne `promo_banner` de la table settings qui fait foi, sans
    | déploiement. Le message anglais est servi quand la vitrine est en
    | anglais ; vide, le français s'affiche pour tout le monde.
    |
    */

    'promo_banner' => [
        'enabled' => true,
        'message' => '-50 % sur la livraison de votre première commande',
        'message_en' => '50% off delivery on your first order',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | The languages the visitor can pick from the footer. French is the
    | application's locale; English exists for the storefront only for now —
    | its dictionary is lang/en.json, keyed by the French source strings, and
    | anything missing there simply stays in French.
    |
    */

    'locales' => [
        'fr' => 'Français',
        'en' => 'English',
    ],

    /*
    |--------------------------------------------------------------------------
    | Legal
    |--------------------------------------------------------------------------
    |
    | What the legal pages display. The publisher is the person behind the
    | service — the name is public on the site by definition, so it lives here
    | rather than in an environment file someone could forget to fill on the
    | server. The variable is the override for the day the immatriculation
    | turns it into a company name.
    |
    */

    'legal' => [
        'publisher' => env('SHOPRELLE_LEGAL_PUBLISHER', 'Farelle KEMENE'),

        'publisher_email' => 'farelle.kemene@shoprelle.com',

        'developer' => 'Hugues TCHOUALA',

        'developer_email' => 'hugues.tchouala@shoprelle.com',

        'contact_email' => 'contact@shoprelle.com',

        'host' => [
            'name' => 'IONOS SARL',
            'address' => '7 place de la Gare, BP 70109, 57201 Sarreguemines Cedex, France',
            'website' => 'https://www.ionos.fr',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backups
    |--------------------------------------------------------------------------
    |
    | Where `shoprelle:backup` writes its nightly archives of the database and
    | the customer screenshots. Kept out of `storage/app` so an archive never
    | ends up containing the previous ones. Point the variable at another
    | mount — or a directory synchronized off the server — to survive more
    | than an application mistake.
    |
    */

    'backups' => [
        'directory' => env('SHOPRELLE_BACKUP_DIRECTORY', storage_path('backups')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chatbot
    |--------------------------------------------------------------------------
    |
    | The conversation is a server-side state machine, so the same flow can be
    | driven later by a WhatsApp or Telegram webhook without changes.
    |
    */

    'chatbot' => [
        'session_key' => 'shoprelle.conversation',

        'idle_timeout_minutes' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer Area
    |--------------------------------------------------------------------------
    |
    | "Mes demandes", where a customer reads their own quotes. There is no
    | account and no password: the session simply remembers which customer
    | proved themselves with their access code, and forgets it like any other
    | session. Nothing here may be guessed from a phone number alone.
    |
    */

    'customer_area' => [
        'session_key' => 'shoprelle.customer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment
    |--------------------------------------------------------------------------
    |
    | Les moyens par lesquels un client règle son devis. Les deux publics n'ont
    | pas les mêmes besoins, et la liste sert les deux :
    |
    |   — la vitrine annonce les noms, tous, sans coordonnées. « Est-ce que je
    |     pourrai payer avec mon MoMo ? » se demande avant de commander, et la
    |     réponse est une information, pas un engagement sur un numéro.
    |   — la page du client qui vient d'accepter son devis donne le compte où
    |     envoyer l'argent, et là seuls les moyens réellement renseignés ont
    |     quelque chose à dire.
    |
    | Le `compte` est un numéro pour les portefeuilles mobiles et une adresse
    | pour PayPal ; c'est la seule raison pour laquelle il ne s'appelle pas
    | « numéro ».
    |
    */

    'payment' => [
        'methods' => [
            [
                'name' => 'MTN Mobile Money',
                'account' => env('SHOPRELLE_PAYMENT_MTN'),
                // Les couleurs des trois marques. La vitrine affiche
                // maintenant les logos eux-mêmes, sur leur tuile blanche ; la
                // couleur reste ce sur quoi retombe un moyen de paiement dont
                // aucune marque n'est fournie — une pastille et son nom.
                'colour' => '#FFCC00',
            ],
            [
                'name' => 'Orange Money',
                'account' => env('SHOPRELLE_PAYMENT_ORANGE'),
                'colour' => '#FF7900',
            ],
            [
                'name' => 'PayPal',
                'account' => env('SHOPRELLE_PAYMENT_PAYPAL'),
                'colour' => '#003087',
            ],
        ],

        /*
         * Le nom qui s'affiche sur le téléphone du client au moment de valider
         * le transfert. L'annoncer d'avance est ce qui distingue un paiement
         * attendu d'un numéro reçu par message.
         */
        'account_name' => env('SHOPRELLE_PAYMENT_ACCOUNT_NAME'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact
    |--------------------------------------------------------------------------
    |
    | Shown on the landing page. There is deliberately no contact form: anything
    | about an existing request belongs in the assistant, which already looks a
    | request up from its reference, and a form would only create a second
    | inbox to watch.
    |
    */

    'contact' => [
        'email' => env('SHOPRELLE_CONTACT_EMAIL', 'contact@shoprelle.com'),

        'response_time' => 'sous 24 h ouvrées',
    ],

    /*
    |--------------------------------------------------------------------------
    | Search & Sharing
    |--------------------------------------------------------------------------
    |
    | What a crawler and a link preview see. Rendered into the Blade layout, not
    | through Inertia's <Head> — and that distinction is the whole point of this
    | block. Inertia writes its head tags with JavaScript, and no unfurler runs
    | JavaScript: not WhatsApp, not Telegram, not Facebook. Tags that only exist
    | after hydration do not exist at all for the crawlers that matter, and this
    | service is shared by pasting a link into a conversation.
    |
    | The description is written for a preview card, not for a page: about a
    | hundred and sixty characters, the promise first, and no sentence that the
    | page itself does not already make good on.
    |
    */

    'seo' => [
        'title' => 'Shoprelle — Un lien. Une commande. Une livraison.',

        'description' => "Envoyez le lien d'un produit depuis Shein, Amazon, Zara ou n'importe quel site : nous l'achetons en France et le livrons chez vous. Sans compte, devis avant paiement.",

        /*
         * L'image d'aperçu, en 1200 × 630 — le format que Facebook, WhatsApp et
         * Telegram attendent pour une grande carte. Servie depuis `public/`, et
         * référencée en URL absolue : une URL relative n'est pas résolue par la
         * plupart des robots d'aperçu.
         */
        'image' => '/og-image.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp
    |--------------------------------------------------------------------------
    |
    | Un numéro, et rien d'autre : pas d'API, pas de webhook. Le lien `wa.me`
    | ouvre une conversation avec une personne, qui répond depuis WhatsApp
    | Business. Ce n'est donc pas l'assistant — la page le dit ainsi, et ne doit
    | jamais laisser croire le contraire.
    |
    | Le numéro s'écrit comme on veut : indicatif international, espaces, tirets,
    | `+` ou `00`. Le contrôleur ne garde que les chiffres, ce dont `wa.me` a
    | besoin. Tant qu'il est vide, la carte s'affiche « Bientôt disponible » et
    | n'est pas cliquable — même règle que Telegram.
    |
    */

    'whatsapp' => [
        'number' => env('SHOPRELLE_WHATSAPP_NUMBER'),

        /*
         * Le message pré-rempli. Il n'ouvre pas la conversation à la place du
         * client : il lui évite la page blanche, et nous dit d'où il vient.
         */
        'greeting' => 'Bonjour Shoprelle, je voudrais commander un produit.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Profiles
    |--------------------------------------------------------------------------
    |
    | Linked from the footer, and only once they exist: an unset value is
    | dropped rather than rendered, so the site never offers a visitor a profile
    | that is not there yet.
    |
    | The two that exist carry their URL as a default rather than waiting on an
    | environment variable. A profile address is a fact about the brand, not
    | about the machine the code runs on, and a server where somebody forgot to
    | set it would silently drop the icons — and, worse, drop the `sameAs` that
    | tells search engines these accounts and this site are the same business.
    |
    */

    'social' => [
        'instagram' => env('SHOPRELLE_INSTAGRAM_URL', 'https://www.instagram.com/_shoprelle_/'),
        'facebook' => env('SHOPRELLE_FACEBOOK_URL', 'https://www.facebook.com/profile.php?id=61592908914788'),
        'tiktok' => env('SHOPRELLE_TIKTOK_URL'),
    ],

];

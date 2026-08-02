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
    | Social Profiles
    |--------------------------------------------------------------------------
    |
    | Linked from the footer, and only once they exist: an unset value is
    | dropped rather than rendered, so the site never offers a visitor a profile
    | that is not there yet.
    |
    */

    'social' => [
        'instagram' => env('SHOPRELLE_INSTAGRAM_URL'),
        'facebook' => env('SHOPRELLE_FACEBOOK_URL'),
        'tiktok' => env('SHOPRELLE_TIKTOK_URL'),
    ],

];

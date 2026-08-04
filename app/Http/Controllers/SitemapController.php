<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * The site's public pages, for search engines.
     *
     * Built from named routes rather than written out: the two URLs come from
     * the router and the host from the app's own configuration, so a change of
     * domain or of path cannot leave a stale address behind — which is the one
     * failure a static sitemap.xml makes silently.
     *
     * Only the pages a visitor may land on cold. Everything behind a session is
     * excluded here and in robots.txt both: a crawler that follows those
     * collects nothing but redirects to a login form.
     *
     * "Mes demandes" belongs here despite what it leads to: unidentified, it is
     * a form anybody may land on, and a returning customer looking for it is
     * exactly the kind of search that should find it. What sits behind it — one
     * request per address — is what robots.txt turns away.
     */
    public function __invoke(): Response
    {
        $pages = [
            ['route' => 'home', 'priority' => '1.0'],
            ['route' => 'chat.show', 'priority' => '0.8'],
            ['route' => 'orders.index', 'priority' => '0.5'],
        ];

        $urls = array_map(
            fn (array $page): string => sprintf(
                '    <url><loc>%s</loc><changefreq>weekly</changefreq><priority>%s</priority></url>',
                htmlspecialchars(route($page['route']), ENT_XML1),
                $page['priority'],
            ),
            $pages,
        );

        $xml = implode("\n", [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            ...$urls,
            '</urlset>',
        ]);

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}

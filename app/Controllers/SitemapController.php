<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class SitemapController extends BaseController
{
    public function index(Request $request): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => url('/casos'), 'priority' => '0.9'],
            ['loc' => url('/apoyo-diagnostico'), 'priority' => '0.9'],
            ['loc' => url('/apoyo-diagnostico/ia'), 'priority' => '0.7'],
            ['loc' => url('/enfermedades'), 'priority' => '0.8'],
            ['loc' => url('/recursos'), 'priority' => '0.8'],
            ['loc' => url('/recursos/kit'), 'priority' => '0.6'],
            ['loc' => url('/recursos/glosario'), 'priority' => '0.6'],
            ['loc' => url('/recursos/revistas'), 'priority' => '0.7'],
            ['loc' => url('/vetdatahub'), 'priority' => '0.8'],
            ['loc' => url('/peteval'), 'priority' => '0.8'],
            ['loc' => url('/nosotros'), 'priority' => '0.5'],
            ['loc' => url('/etica'), 'priority' => '0.4'],
            ['loc' => url('/privacidad'), 'priority' => '0.3'],
            ['loc' => url('/terminos'), 'priority' => '0.3'],
        ];

        foreach (Database::select("select slug, updated_at from cases where status = 'publicado' and deleted_at is null order by published_at desc") as $case) {
            $urls[] = ['loc' => url('/casos/' . $case['slug']), 'lastmod' => date('Y-m-d', strtotime((string) $case['updated_at'])), 'priority' => '0.7'];
        }
        foreach (Database::select('select slug, updated_at from diseases order by name') as $disease) {
            $urls[] = ['loc' => url('/enfermedades/' . $disease['slug']), 'lastmod' => date('Y-m-d', strtotime((string) $disease['updated_at'])), 'priority' => '0.6'];
        }
        foreach (Database::select('select slug, updated_at from resources where slug is not null order by title') as $resource) {
            $urls[] = ['loc' => url('/recursos/' . $resource['slug']), 'lastmod' => date('Y-m-d', strtotime((string) $resource['updated_at'])), 'priority' => '0.5'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $entry) {
            $xml .= '  <url><loc>' . htmlspecialchars($entry['loc'], ENT_XML1) . '</loc>';
            if (isset($entry['lastmod'])) {
                $xml .= '<lastmod>' . $entry['lastmod'] . '</lastmod>';
            }
            $xml .= '<priority>' . $entry['priority'] . '</priority></url>' . "\n";
        }
        $xml .= '</urlset>';

        return (new Response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']))
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }

    public function robots(Request $request): Response
    {
        $content = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /perfil\nDisallow: /mis-casos\nSitemap: " . url('/sitemap.xml') . "\n";
        return (new Response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']))
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }
}

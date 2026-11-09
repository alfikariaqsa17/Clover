<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Plant;
use App\Models\Product;
use App\Models\Service;

/** sitemap.xml untuk mesin pencari: halaman publik, produk, jasa, panduan, dan artikel. */
class SitemapController extends Controller
{
    public function __invoke()
    {
        $urls = [];
        $add = function (string $loc, $lastmod = null, string $freq = 'weekly', string $prio = '0.6') use (&$urls) {
            $urls[] = ['loc' => $loc, 'lastmod' => $lastmod?->toAtomString(), 'freq' => $freq, 'prio' => $prio];
        };

        foreach (['home' => '1.0', 'products' => '0.9', 'services' => '0.8', 'guide' => '0.7', 'finder' => '0.6', 'health' => '0.6', 'tutorial' => '0.7', 'about' => '0.4', 'certificates' => '0.5', 'contact' => '0.4', 'track' => '0.3', 'privacy' => '0.2', 'terms' => '0.2'] as $route => $prio) {
            $add(route($route), null, 'weekly', $prio);
        }
        foreach (Product::active()->get(['slug', 'updated_at']) as $m) {
            $add(route('products.show', $m), $m->updated_at, 'weekly', '0.8');
        }
        foreach (Service::active()->get(['slug', 'updated_at']) as $m) {
            $add(route('services.show', $m), $m->updated_at, 'monthly', '0.7');
        }
        foreach (Plant::get(['slug', 'updated_at']) as $m) {
            $add(route('guide.show', $m), $m->updated_at, 'monthly', '0.6');
        }
        foreach (Article::published()->get(['slug', 'updated_at']) as $m) {
            $add(route('tutorial.show', $m), $m->updated_at, 'monthly', '0.6');
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>'
                . ($u['lastmod'] ? '<lastmod>' . $u['lastmod'] . '</lastmod>' : '')
                . '<changefreq>' . $u['freq'] . '</changefreq><priority>' . $u['prio'] . '</priority></url>' . "\n";
        }

        return response($xml . '</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }
}

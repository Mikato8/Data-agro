<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Cache;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CaseRepository;
use App\Repositories\CatalogRepository;
use App\Repositories\DiseaseRepository;
use App\Repositories\ResourceRepository;

final class HomeController extends BaseController
{
    public function index(Request $request): Response
    {
        $cases = new CaseRepository();
        $featured = Cache::remember('home.featured', 300, static fn (): array => $cases->featured(3));
        $latest = Cache::remember('home.latest', 300, static fn (): array => $cases->latest(6));

        if (count($featured) < 3) {
            $seen = array_column($featured, 'id');
            foreach ($latest as $case) {
                if (count($featured) >= 3) {
                    break;
                }
                if (!in_array($case['id'], $seen, true)) {
                    $featured[] = $case;
                    $seen[] = $case['id'];
                }
            }
        }

        $catalog = new CatalogRepository();
        $diseases = new DiseaseRepository();
        $resources = new ResourceRepository();

        return $this->view('home.index', [
            'title' => 'Diagnóstico Veterinario · Casos clínicos para médicos veterinarios en México',
            'metaDescription' => 'Biblioteca colaborativa de casos clínicos veterinarios en México. Consulta diagnósticos, comparte tus casos y usa herramientas de apoyo diagnóstico basadas en evidencia.',
            'stats' => $cases->stats(),
            'featured' => $featured,
            'latest' => $latest,
            'resources' => $resources->featured(4),
            'diseases' => $diseases->featured(4),
            'species' => $catalog->species(),
            'tags' => $cases->popularTags(10),
            'bodyClass' => 'page-home',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\VetDataHubRepository;

final class VetDataHubController extends BaseController
{
    public function index(Request $request): Response
    {
        $repo = new VetDataHubRepository();
        $filters = [
            'q' => $request->string('q'),
            'category' => $request->string('category'),
            'source' => $request->string('source'),
        ];
        $page = max(1, $request->int('page', 1));
        $perPage = 12;
        $results = $repo->search($filters['q'], $filters['category'], $filters['source']);
        $total = count($results);
        $paginator = new Paginator($total, $perPage, $page);
        $datasets = array_slice($results, $paginator->offset(), $perPage);

        return $this->view('vetdatahub.index', [
            'title' => 'VetDataHub · Catálogo de datasets veterinarios',
            'metaDescription' => 'Explora el catálogo VetDataHub de datasets veterinarios abiertos: imágenes, registros clínicos, genómica, epidemiología y más.',
            'datasets' => $datasets,
            'total' => $total,
            'filters' => $filters,
            'paginator' => $paginator,
            'categories' => $repo->categories(),
            'sources' => $repo->sources(),
            'catalogTotal' => $repo->total(),
            'commit' => $repo->commit(),
            'importedAt' => $repo->importedAt(),
            'bodyClass' => 'page-vetdatahub',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AgroDatosRepository;

final class AgroDatosController extends BaseController
{
    public function index(Request $request): Response
    {
        $repo = new AgroDatosRepository();
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

        return $this->view('agrodatos.index', [
            'title' => 'Datos abiertos · AgroDiagnóstico',
            'metaDescription' => 'Catálogo curado de datasets, APIs y recursos de datos abiertos para agricultura, clima, suelos y sanidad vegetal en México.',
            'datasets' => $datasets,
            'total' => $total,
            'filters' => $filters,
            'paginator' => $paginator,
            'categories' => $repo->categories(),
            'sources' => $repo->sources(),
            'catalogTotal' => $repo->total(),
            'bodyClass' => 'page-agrodatos',
        ]);
    }
}

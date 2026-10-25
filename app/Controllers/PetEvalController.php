<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\PetEvalRepository;

final class PetEvalController extends BaseController
{
    public function index(Request $request): Response
    {
        $repo = new PetEvalRepository();
        $filters = [
            'q' => $request->string('q'),
            'icd' => $request->string('icd'),
            'content' => $request->string('content'),
        ];
        $page = max(1, $request->int('page', 1));
        $perPage = 10;
        $result = $repo->search($filters['q'], $filters['icd'], $filters['content'], $page, $perPage);
        $paginator = new Paginator($result['total'], $perPage, $page);

        return $this->view('peteval.index', [
            'title' => 'PetEVAL · Benchmark de historiales veterinarios',
            'metaDescription' => 'Explora PetEVAL, el benchmark de historiales clínicos veterinarios anotados con ICD-11, enfermedades y anonimización.',
            'records' => $result['records'],
            'total' => $result['total'],
            'filters' => $filters,
            'paginator' => $paginator,
            'stats' => $repo->stats(),
            'icdLabels' => $repo->icdLabels(),
            'topDiseases' => $repo->topDiseases(),
            'anonymisationTypes' => $repo->anonymisationTypes(),
            'catalogTotal' => $repo->total(),
            'bodyClass' => 'page-peteval',
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $repo = new PetEvalRepository();
        $record = $repo->find((int) $id);
        if ($record === null) {
            $this->notFound('No encontramos este registro de PetEVAL.');
        }

        return $this->view('peteval.show', [
            'title' => 'Registro PetEVAL #' . $record['id'],
            'metaDescription' => 'Registro anonimizado del benchmark PetEVAL con etiquetas ICD-11, enfermedades y anonimización.',
            'record' => $record,
            'stats' => $repo->stats(),
            'canonical' => url('/peteval/' . $record['id']),
        ]);
    }
}

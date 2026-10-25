<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CatalogRepository;
use App\Repositories\ProblemRepository;
use App\Services\RiskAiClient;

final class DiagnosticController extends BaseController
{
    public function index(Request $request): Response
    {
        $catalog = new CatalogRepository();
        $cropsList = $catalog->crops();
        $code = (string) $request->query('crops', '');
        $crops = $catalog->cropsByCode($code) ?? ($cropsList[0] ?? null);
        if ($crops === null) {
            $this->notFound('No hay cultivos configurados en el catálogo.');
        }

        $problemRepo = new ProblemRepository();
        $symptoms = $problemRepo->symptomsForCrops((int) $crops['id']);
        $grouped = [];
        foreach ($symptoms as $symptom) {
            $symptom['id'] = (int) $symptom['id'];
            $grouped[(string) $symptom['category']][] = $symptom;
        }

        return $this->view('diagnostic.index', [
            'title' => 'Apoyo diagnóstico por similitud · AgroDiagnóstico',
            'metaDescription' => 'Selecciona cultivo y síntomas para obtener diagnósticos diferenciales ponderados, con síntomas clave y nivel de riesgo fitosanitario.',
            'cropsList' => $cropsList,
            'crops' => $crops,
            'signGroups' => $grouped,
            'bodyClass' => 'page-diagnostic',
        ]);
    }

    public function symptoms(Request $request): Response
    {
        $catalog = new CatalogRepository();
        $crops = $catalog->cropsByCode((string) $request->query('crops', ''));
        if ($crops === null) {
            return Response::json(['groups' => []]);
        }
        $symptoms = (new ProblemRepository())->symptomsForCrops((int) $crops['id']);
        $groups = [];
        foreach ($symptoms as $symptom) {
            $groups[(string) $symptom['category']][] = [
                'id' => (int) $symptom['id'],
                'name' => $symptom['name'],
                'slug' => $symptom['slug'],
            ];
        }
        return Response::json(['crops' => $crops['name'], 'groups' => $groups]);
    }

    public function analyze(Request $request): Response
    {
        $catalog = new CatalogRepository();
        $cropsList = $catalog->crops();
        $crops = $catalog->cropsByCode($request->string('crops'));
        if ($crops === null) {
            Flash::error('Selecciona un cultivo válido.');
            return $this->redirect('/apoyo-diagnostico');
        }

        $rawSigns = $request->post('symptoms', []);
        $symptomIds = [];
        if (is_array($rawSigns)) {
            foreach ($rawSigns as $raw) {
                $id = (int) $raw;
                if ($id > 0) {
                    $symptomIds[$id] = $id;
                }
            }
        }
        $symptomIds = array_values(array_slice($symptomIds, 0, 20));
        if ($symptomIds === []) {
            Flash::error('Selecciona al menos un síntoma clínico para calcular los diferenciales.');
            return $this->redirect('/apoyo-diagnostico?crops=' . rawurlencode((string) $crops['code']));
        }

        $problemRepo = new ProblemRepository();
        $results = $problemRepo->match((int) $crops['id'], $symptomIds, count($symptomIds));
        $selectedSigns = Database::select(
            'select id, name, category from symptoms where id = any($1::int[]) order by category, name',
            ['{' . implode(',', $symptomIds) . '}']
        );

        return $this->view('diagnostic.results', [
            'title' => 'Diferenciales sugeridos · AgroDiagnóstico',
            'metaDescription' => 'Resultados de apoyo diagnóstico por similitud de síntomas.',
            'crops' => $crops,
            'cropsList' => $cropsList,
            'selectedSigns' => $selectedSigns,
            'selectedIds' => $symptomIds,
            'results' => $results,
            'bodyClass' => 'page-diagnostic',
        ]);
    }

    public function aiForm(Request $request): Response
    {
        $client = new RiskAiClient();
        return $this->view('diagnostic.ai', [
            'title' => 'Clasificador de riesgo fitosanitario · AgroDiagnóstico',
            'metaDescription' => 'Clasificador orientativo del nivel de riesgo fitosanitario de un relato de campo. Herramienta educativa, no sustituye el diagnóstico.',
            'configured' => $client->isConfigured(),
            'text' => '',
            'result' => null,
            'error' => null,
            'bodyClass' => 'page-diagnostic',
        ]);
    }

    public function aiClassify(Request $request): Response
    {
        $client = new RiskAiClient();
        $text = trim($request->string('text'));
        $error = null;
        $result = null;

        if (!$client->isConfigured()) {
            $error = 'La IA todavía no está configurada en este entorno.';
        } elseif (mb_strlen($text) < 10) {
            $error = 'Describe el caso con al menos 10 caracteres.';
        } elseif (mb_strlen($text) > 2000) {
            $error = 'El texto es demasiado largo. Usa un máximo de 2,000 caracteres.';
        } else {
            $last = (int) ($_SESSION['ai_risk_last'] ?? 0);
            if (time() - $last < 2) {
                $error = 'Espera un par de segundos antes de volver a clasificar.';
            } else {
                $_SESSION['ai_risk_last'] = time();
                try {
                    $result = $client->classify($text);
                } catch (\RuntimeException $e) {
                    $error = $e->getMessage();
                    \App\Core\Logger::warning('Fallo del clasificador de riesgo', ['message' => $e->getMessage()]);
                }
            }
        }

        return $this->view('diagnostic.ai', [
            'title' => 'Clasificador de riesgo fitosanitario · AgroDiagnóstico',
            'metaDescription' => 'Clasificador orientativo del nivel de riesgo fitosanitario de un relato de campo. Herramienta educativa, no sustituye el diagnóstico.',
            'configured' => $client->isConfigured(),
            'text' => $text,
            'result' => $result,
            'error' => $error,
            'bodyClass' => 'page-diagnostic',
        ]);
    }

    public function problems(Request $request): Response
    {
        $filters = [
            'q' => $request->string('q'),
            'crops' => $request->string('crops'),
            'category' => $request->string('category'),
        ];
        $repo = new ProblemRepository();
        $catalog = new CatalogRepository();

        return $this->view('problems.index', [
            'title' => 'Plagas y enfermedades · AgroDiagnóstico',
            'metaDescription' => 'Directorio de plagas, enfermedades y deficiencias con síntomas clave, manejo y prevención para cultivos de México.',
            'problems' => $repo->all($filters),
            'filters' => $filters,
            'cropsList' => $catalog->crops(),
            'categories' => $repo->categories(),
        ]);
    }

    public function problem(Request $request, string $slug): Response
    {
        $problem = (new ProblemRepository())->findBySlug($slug);
        if ($problem === null) {
            $this->notFound('No encontramos esta ficha fitosanitaria.');
        }

        return $this->view('problems.show', [
            'title' => $problem['name'] . ' · AgroDiagnóstico',
            'metaDescription' => excerpt($problem['summary'], 160),
            'problem' => $problem,
            'canonical' => url('/enfermedades/' . $slug),
        ]);
    }
}

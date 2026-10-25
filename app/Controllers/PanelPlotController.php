<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CatalogRepository;

final class PanelPlotController extends PanelBaseController
{
    private const IRRIGATION = ['temporal', 'gravedad', 'goteo', 'aspersion', 'microaspersion', 'otro'];

    public function index(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $q = trim($request->string('q'));
        $page = max(1, $request->int('page', 1));
        $perPage = 15;

        $where = 'owner_id = $1';
        $params = [$uid];
        if ($q !== '') {
            $params[] = '%' . $q . '%';
            $where .= ' and (name ilike $2 or producer_name ilike $2 or producer_phone ilike $2 or crop ilike $2)';
        }
        $total = (int) Database::value('select count(*) from farm_plots where ' . $where, $params);
        $params[] = $perPage;
        $limit = '$' . count($params);
        $params[] = ($page - 1) * $perPage;
        $offset = '$' . count($params);
        $plots = Database::select(
            "select * from farm_plots where {$where} order by name limit {$limit} offset {$offset}",
            $params
        );

        return $this->panelView('panel.plots.index', [
            'title' => 'Parcelas · Panel de campo',
            'user' => $user,
            'plots' => $plots,
            'total' => $total,
            'q' => $q,
            'paginator' => new Paginator($total, $perPage, $page),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->panelUser();
        return $this->panelView('panel.plots.form', [
            'title' => 'Nueva parcela · Panel de campo',
            'user' => $user,
            'plot' => null,
            'cropsList' => (new CatalogRepository())->crops(),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->panelUser();
        [$data, $errors] = $this->validatePlot($request);
        if ($errors !== []) {
            $this->withErrors($errors);
            $this->flashOld($data);
            return $this->redirect('/panel/parcelas/nuevo');
        }

        $row = Database::insert(
            'insert into farm_plots (owner_id, name, crop, variety, area_ha, municipality, state_code,
                irrigation, soil, producer_name, producer_phone, producer_email, notes)
             values ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13) returning id',
            [
                (int) $user['id'], $data['name'], $data['crop'], $data['variety'], $data['area_ha'],
                $data['municipality'], $data['state_code'], $data['irrigation'], $data['soil'],
                $data['producer_name'], $data['producer_phone'], $data['producer_email'], $data['notes'],
            ]
        );
        $id = (int) ($row['id'] ?? 0);
        $this->audit('campo.parcela_creada', 'farm_plot', (string) $id);
        Flash::success('Parcela registrada.');
        return $this->redirect('/panel/parcelas/' . $id);
    }

    public function show(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $plot = $this->findPlot((int) $user['id'], (int) $id);
        if ($plot === null) {
            $this->notFound('No encontramos esa parcela en tu cuenta.');
        }

        $visits = Database::select(
            'select * from farm_visits where owner_id = $1 and plot_id = $2 order by starts_at desc limit 8',
            [(int) $user['id'], (int) $plot['id']]
        );
        $recommendations = Database::select(
            'select * from farm_recommendations where owner_id = $1 and plot_id = $2 order by created_at desc limit 6',
            [(int) $user['id'], (int) $plot['id']]
        );
        $sales = Database::select(
            'select * from farm_sales where owner_id = $1 and plot_id = $2 order by created_at desc limit 6',
            [(int) $user['id'], (int) $plot['id']]
        );

        return $this->panelView('panel.plots.show', [
            'title' => $plot['name'] . ' · Panel de campo',
            'user' => $user,
            'plot' => $plot,
            'visits' => $visits,
            'recommendations' => $recommendations,
            'sales' => $sales,
        ]);
    }

    public function edit(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $plot = $this->findPlot((int) $user['id'], (int) $id);
        if ($plot === null) {
            $this->notFound('No encontramos esa parcela en tu cuenta.');
        }
        return $this->panelView('panel.plots.form', [
            'title' => 'Editar ' . $plot['name'] . ' · Panel de campo',
            'user' => $user,
            'plot' => $plot,
            'cropsList' => (new CatalogRepository())->crops(),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $plot = $this->findPlot((int) $user['id'], (int) $id);
        if ($plot === null) {
            $this->notFound('No encontramos esa parcela en tu cuenta.');
        }
        [$data, $errors] = $this->validatePlot($request);
        if ($errors !== []) {
            $this->withErrors($errors);
            $this->flashOld($data);
            return $this->redirect('/panel/parcelas/' . (int) $plot['id'] . '/editar');
        }

        Database::execute(
            'update farm_plots set name = $1, crop = $2, variety = $3, area_ha = $4, municipality = $5,
                state_code = $6, irrigation = $7, soil = $8, producer_name = $9, producer_phone = $10,
                producer_email = $11, notes = $12
             where id = $13 and owner_id = $14',
            [
                $data['name'], $data['crop'], $data['variety'], $data['area_ha'], $data['municipality'],
                $data['state_code'], $data['irrigation'], $data['soil'], $data['producer_name'],
                $data['producer_phone'], $data['producer_email'], $data['notes'],
                (int) $plot['id'], (int) $user['id'],
            ]
        );
        $this->audit('campo.parcela_actualizada', 'farm_plot', (string) $plot['id']);
        Flash::success('Parcela actualizada.');
        return $this->redirect('/panel/parcelas/' . (int) $plot['id']);
    }

    /** @return array<string, mixed>|null */
    private function findPlot(int $ownerId, int $id): ?array
    {
        return Database::first('select * from farm_plots where id = $1 and owner_id = $2', [$id, $ownerId]);
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>} */
    private function validatePlot(Request $request): array
    {
        $data = [
            'name' => trim($request->string('name')),
            'crop' => $request->string('crop'),
            'variety' => trim($request->string('variety')),
            'area_ha' => trim($request->string('area_ha')),
            'municipality' => trim($request->string('municipality')),
            'state_code' => $request->string('state_code') !== '' ? $request->string('state_code') : null,
            'irrigation' => $request->string('irrigation', 'temporal'),
            'soil' => trim($request->string('soil')),
            'producer_name' => trim($request->string('producer_name')),
            'producer_phone' => trim($request->string('producer_phone')),
            'producer_email' => trim($request->string('producer_email')),
            'notes' => trim($request->string('notes')),
        ];

        $cropCodes = array_map(
            static fn (array $crop): string => (string) $crop['code'],
            (new CatalogRepository())->crops()
        );

        $validator = $this->validator($data, [
            'name' => 'nombre de la parcela', 'crop' => 'cultivo', 'variety' => 'variedad',
            'area_ha' => 'superficie', 'municipality' => 'municipio', 'state_code' => 'estado',
            'irrigation' => 'riego', 'soil' => 'suelo',
            'producer_name' => 'nombre del productor', 'producer_phone' => 'teléfono',
            'producer_email' => 'correo', 'notes' => 'notas',
        ]);
        $validator->validate([
            'name' => 'required|min:1|max:80',
            'crop' => 'required|in:' . implode(',', $cropCodes),
            'variety' => 'nullable|max:80',
            'area_ha' => 'nullable|numeric|between:0.01,100000',
            'municipality' => 'nullable|max:120',
            'irrigation' => 'required|in:' . implode(',', self::IRRIGATION),
            'soil' => 'nullable|max:120',
            'producer_name' => 'required|min:2|max:120',
            'producer_phone' => 'nullable|max:30',
            'producer_email' => 'nullable|email|max:180',
            'notes' => 'nullable|max:3000',
        ]);

        $errors = $validator->errors();
        return [[
            'name' => $data['name'], 'crop' => $data['crop'],
            'variety' => $data['variety'] !== '' ? $data['variety'] : null,
            'area_ha' => $data['area_ha'] !== '' ? $data['area_ha'] : null,
            'municipality' => $data['municipality'] !== '' ? $data['municipality'] : null,
            'state_code' => $data['state_code'],
            'irrigation' => $data['irrigation'],
            'soil' => $data['soil'] !== '' ? $data['soil'] : null,
            'producer_name' => $data['producer_name'],
            'producer_phone' => $data['producer_phone'] !== '' ? $data['producer_phone'] : null,
            'producer_email' => $data['producer_email'] !== '' ? $data['producer_email'] : null,
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ], $errors];
    }
}

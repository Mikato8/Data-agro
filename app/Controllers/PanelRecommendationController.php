<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;

final class PanelRecommendationController extends PanelBaseController
{
    public function index(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $plotId = max(0, $request->int('plot_id'));
        $where = 'r.owner_id = $1';
        $params = [$uid];
        if ($plotId > 0) {
            $params[] = $plotId;
            $where .= ' and r.plot_id = $2';
        }
        $recommendations = Database::select(
            "select r.*, p.name as plot_name, p.crop, p.producer_name
             from farm_recommendations r join farm_plots p on p.id = r.plot_id
             where {$where} order by r.created_at desc limit 80",
            $params
        );
        $plots = Database::select('select id, name, producer_name from farm_plots where owner_id = $1 order by name', [$uid]);

        return $this->panelView('panel.recommendations.index', [
            'title' => 'Recomendaciones · Panel de campo',
            'user' => $user,
            'recommendations' => $recommendations,
            'plots' => $plots,
            'plotId' => $plotId,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $plots = Database::select('select id, name, crop, producer_name from farm_plots where owner_id = $1 order by name', [$uid]);
        $visits = Database::select(
            'select a.id, a.starts_at, a.reason, p.name as plot_name
             from farm_visits a join farm_plots p on p.id = a.plot_id
             where a.owner_id = $1 and a.status <> \'cancelada\' order by a.starts_at desc limit 30',
            [$uid]
        );
        return $this->panelView('panel.recommendations.form', [
            'title' => 'Nueva recomendación · Panel de campo',
            'user' => $user,
            'plots' => $plots,
            'visits' => $visits,
            'plotId' => $request->int('plot_id'),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $plotId = $request->int('plot_id');
        $visitId = $request->int('visit_id');
        $diagnosis = trim($request->string('diagnosis'));
        $inputsPrescribed = trim($request->string('inputs_prescribed'));
        $instructions = trim($request->string('instructions'));

        $validator = $this->validator([
            'plot_id' => $plotId, 'diagnosis' => $diagnosis, 'inputs_prescribed' => $inputsPrescribed,
        ], ['plot_id' => 'parcela', 'diagnosis' => 'diagnóstico', 'inputs_prescribed' => 'medicamentos']);
        $validator->validate([
            'plot_id' => 'required|integer',
            'diagnosis' => 'required|min:3|max:1000',
            'inputs_prescribed' => 'required|min:3|max:3000',
            'instructions' => 'nullable|max:2000',
        ]);
        $plot = Database::first('select id from farm_plots where id = $1 and owner_id = $2', [$plotId, $uid]);
        if ($plot === null) {
            $validator->add('plot_id', 'Selecciona una parcela.');
        }
        if ($visitId > 0) {
            $visit = Database::first('select id from farm_visits where id = $1 and owner_id = $2 and plot_id = $3', [$visitId, $uid, $plotId]);
            if ($visit === null) {
                $validator->add('visit_id', 'La visita no corresponde a la parcela.');
            }
        }
        if ($validator->fails()) {
            $this->withErrors($validator->errors());
            $this->flashOld(['plot_id' => $plotId, 'visit_id' => $visitId, 'diagnosis' => $diagnosis, 'inputs_prescribed' => $inputsPrescribed, 'instructions' => $instructions]);
            return $this->redirect('/panel/recomendaciones/nueva?plot_id=' . $plotId);
        }

        $row = Database::insert(
            'insert into farm_recommendations (owner_id, plot_id, visit_id, diagnosis, inputs_prescribed, instructions)
             values ($1, $2, $3, $4, $5, $6) returning id',
            [$uid, $plotId, $visitId > 0 ? $visitId : null, $diagnosis, $inputsPrescribed, $instructions !== '' ? $instructions : null]
        );
        $id = (int) ($row['id'] ?? 0);
        $this->audit('campo.recomendacion_creada', 'farm_recommendation', (string) $id);
        Flash::success('Recomendación guardada.');
        return $this->redirect('/panel/recomendaciones/' . $id);
    }

    public function show(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $recommendation = Database::first(
            'select r.*, p.name as plot_name, p.crop, p.variety, p.area_ha, p.municipality,
                    p.producer_name, p.producer_phone, a.starts_at
             from farm_recommendations r
             join farm_plots p on p.id = r.plot_id
             left join farm_visits a on a.id = r.visit_id
             where r.id = $1 and r.owner_id = $2',
            [(int) $id, (int) $user['id']]
        );
        if ($recommendation === null) {
            $this->notFound('No encontramos esa recomendación.');
        }
        return $this->panelView('panel.recommendations.show', [
            'title' => 'Recomendación de ' . $recommendation['plot_name'] . ' · Panel de campo',
            'user' => $user,
            'recommendation' => $recommendation,
        ]);
    }
}

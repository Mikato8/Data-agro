<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;

final class PanelVisitController extends PanelBaseController
{
    private const STATUSES = ['programada', 'confirmada', 'realizada', 'cancelada'];

    public function index(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $plotId = max(0, $request->int('plot_id'));
        $date = $request->string('date');
        $status = $request->string('status');
        if ($date === '' && $plotId === 0) {
            $date = (new \DateTimeImmutable('today', new \DateTimeZone((string) config('app.timezone', 'America/Mexico_City'))))->format('Y-m-d');
        }

        $where = ['a.owner_id = $1'];
        $params = [$uid];
        $dateLabel = '';
        if ($date !== '' && $date !== 'all') {
            $start = new \DateTimeImmutable($date . ' 00:00:00', new \DateTimeZone((string) config('app.timezone', 'America/Mexico_City')));
            $end = $start->modify('+1 day');
            $params[] = $start->format('c');
            $params[] = $end->format('c');
            $where[] = 'a.starts_at >= $' . (count($params) - 1) . ' and a.starts_at < $' . count($params);
            $dateLabel = $start->format('d/m/Y');
        }
        if ($plotId > 0) {
            $params[] = $plotId;
            $where[] = 'a.plot_id = $' . count($params);
        }
        if (in_array($status, self::STATUSES, true)) {
            $params[] = $status;
            $where[] = 'a.status = $' . count($params);
        }

        $visits = Database::select(
            'select a.*, p.name as plot_name, p.crop, p.producer_name, p.producer_phone
             from farm_visits a join farm_plots p on p.id = a.plot_id
             where ' . implode(' and ', $where) . '
             order by a.starts_at asc limit 100',
            $params
        );

        return $this->panelView('panel.visits.index', [
            'title' => 'Visitas · Panel de campo',
            'user' => $user,
            'visits' => $visits,
            'date' => $date,
            'status' => $status,
            'plotId' => $plotId,
            'dateLabel' => $dateLabel,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->panelUser();
        $plots = $this->plots((int) $user['id']);
        return $this->panelView('panel.visits.form', [
            'title' => 'Nueva visita · Panel de campo',
            'user' => $user,
            'plots' => $plots,
            'plotId' => $request->int('plot_id'),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $plotId = $request->int('plot_id');
        $startsAt = trim($request->string('starts_at'));
        $duration = $request->int('duration_min', 30);
        $reason = trim($request->string('reason'));
        $notes = trim($request->string('notes'));

        $plot = Database::first('select id from farm_plots where id = $1 and owner_id = $2', [$plotId, $uid]);
        $validator = $this->validator([
            'plot_id' => $plotId, 'starts_at' => $startsAt, 'duration_min' => $duration, 'reason' => $reason,
        ], ['plot_id' => 'cultivo', 'starts_at' => 'fecha y hora', 'duration_min' => 'duración', 'reason' => 'motivo']);
        $validator->validate([
            'plot_id' => 'required|integer',
            'starts_at' => 'required|date',
            'duration_min' => 'required|integer|between:15,240',
            'reason' => 'required|min:2|max:180',
        ]);
        if ($plot === null) {
            $validator->add('plot_id', 'Selecciona una parcela.');
        }
        if ($startsAt !== '' && strtotime($startsAt) === false) {
            $validator->add('starts_at', 'La fecha y hora no son válidas.');
        }
        if ($validator->fails()) {
            $this->withErrors($validator->errors());
            $this->flashOld(['plot_id' => $plotId, 'starts_at' => $startsAt, 'duration_min' => $duration, 'reason' => $reason, 'notes' => $notes]);
            return $this->redirect('/panel/visitas/nueva?plot_id=' . $plotId);
        }

        $row = Database::insert(
            'insert into farm_visits (owner_id, plot_id, starts_at, duration_min, reason, notes)
             values ($1, $2, $3, $4, $5, $6) returning id',
            [$uid, $plotId, date('c', strtotime($startsAt)), $duration, $reason, $notes !== '' ? $notes : null]
        );
        $this->audit('campo.cita_creada', 'farm_visit', (string) ($row['id'] ?? 0));
        Flash::success('Visita agendada.');
        return $this->redirect('/panel/visitas?date=' . rawurlencode(substr($startsAt, 0, 10)));
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $status = $request->string('status');
        if (!in_array($status, self::STATUSES, true)) {
            Flash::error('Estado de visita no válido.');
            return $this->redirect('/panel/visitas');
        }
        $visit = Database::first('select id from farm_visits where id = $1 and owner_id = $2', [(int) $id, (int) $user['id']]);
        if ($visit === null) {
            $this->notFound('No encontramos esa visita.');
        }
        Database::execute('update farm_visits set status = $1 where id = $2 and owner_id = $3', [$status, (int) $id, (int) $user['id']]);
        $this->audit('campo.cita_estado', 'farm_visit', $id, ['status' => $status]);
        Flash::success('Estado de la visita actualizado.');
        return $this->redirect('/panel/visitas?date=' . rawurlencode($request->string('date', 'all')));
    }

    /** @return array<int, array<string, mixed>> */
    private function plots(int $ownerId): array
    {
        return Database::select('select id, name, crop, producer_name from farm_plots where owner_id = $1 order by name', [$ownerId]);
    }
}

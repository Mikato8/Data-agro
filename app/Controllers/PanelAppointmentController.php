<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;

final class PanelAppointmentController extends PanelBaseController
{
    private const STATUSES = ['programada', 'confirmada', 'atendida', 'cancelada'];

    public function index(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $patientId = max(0, $request->int('patient_id'));
        $date = $request->string('date');
        $status = $request->string('status');
        if ($date === '' && $patientId === 0) {
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
        if ($patientId > 0) {
            $params[] = $patientId;
            $where[] = 'a.patient_id = $' . count($params);
        }
        if (in_array($status, self::STATUSES, true)) {
            $params[] = $status;
            $where[] = 'a.status = $' . count($params);
        }

        $appointments = Database::select(
            'select a.*, p.name as patient_name, p.species, p.tutor_name, p.tutor_phone
             from clinic_appointments a join clinic_patients p on p.id = a.patient_id
             where ' . implode(' and ', $where) . '
             order by a.starts_at asc limit 100',
            $params
        );

        return $this->panelView('panel.appointments.index', [
            'title' => 'Citas · Panel clínico',
            'user' => $user,
            'appointments' => $appointments,
            'date' => $date,
            'status' => $status,
            'patientId' => $patientId,
            'dateLabel' => $dateLabel,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->panelUser();
        $patients = $this->patients((int) $user['id']);
        return $this->panelView('panel.appointments.form', [
            'title' => 'Nueva cita · Panel clínico',
            'user' => $user,
            'patients' => $patients,
            'patientId' => $request->int('patient_id'),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $patientId = $request->int('patient_id');
        $startsAt = trim($request->string('starts_at'));
        $duration = $request->int('duration_min', 30);
        $reason = trim($request->string('reason'));
        $notes = trim($request->string('notes'));

        $patient = Database::first('select id from clinic_patients where id = $1 and owner_id = $2', [$patientId, $uid]);
        $validator = $this->validator([
            'patient_id' => $patientId, 'starts_at' => $startsAt, 'duration_min' => $duration, 'reason' => $reason,
        ], ['patient_id' => 'paciente', 'starts_at' => 'fecha y hora', 'duration_min' => 'duración', 'reason' => 'motivo']);
        $validator->validate([
            'patient_id' => 'required|integer',
            'starts_at' => 'required|date',
            'duration_min' => 'required|integer|between:15,240',
            'reason' => 'required|min:2|max:180',
        ]);
        if ($patient === null) {
            $validator->add('patient_id', 'Selecciona un paciente de tu clínica.');
        }
        if ($startsAt !== '' && strtotime($startsAt) === false) {
            $validator->add('starts_at', 'La fecha y hora no son válidas.');
        }
        if ($validator->fails()) {
            $this->withErrors($validator->errors());
            $this->flashOld(['patient_id' => $patientId, 'starts_at' => $startsAt, 'duration_min' => $duration, 'reason' => $reason, 'notes' => $notes]);
            return $this->redirect('/panel/citas/nueva?patient_id=' . $patientId);
        }

        $row = Database::insert(
            'insert into clinic_appointments (owner_id, patient_id, starts_at, duration_min, reason, notes)
             values ($1, $2, $3, $4, $5, $6) returning id',
            [$uid, $patientId, date('c', strtotime($startsAt)), $duration, $reason, $notes !== '' ? $notes : null]
        );
        $this->audit('clinica.cita_creada', 'clinic_appointment', (string) ($row['id'] ?? 0));
        Flash::success('Cita agendada.');
        return $this->redirect('/panel/citas?date=' . rawurlencode(substr($startsAt, 0, 10)));
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $status = $request->string('status');
        if (!in_array($status, self::STATUSES, true)) {
            Flash::error('Estado de cita no válido.');
            return $this->redirect('/panel/citas');
        }
        $appointment = Database::first('select id from clinic_appointments where id = $1 and owner_id = $2', [(int) $id, (int) $user['id']]);
        if ($appointment === null) {
            $this->notFound('No encontramos esa cita.');
        }
        Database::execute('update clinic_appointments set status = $1 where id = $2 and owner_id = $3', [$status, (int) $id, (int) $user['id']]);
        $this->audit('clinica.cita_estado', 'clinic_appointment', $id, ['status' => $status]);
        Flash::success('Estado de la cita actualizado.');
        return $this->redirect('/panel/citas?date=' . rawurlencode($request->string('date', 'all')));
    }

    /** @return array<int, array<string, mixed>> */
    private function patients(int $ownerId): array
    {
        return Database::select('select id, name, species, tutor_name from clinic_patients where owner_id = $1 order by name', [$ownerId]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;

final class PanelPrescriptionController extends PanelBaseController
{
    public function index(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $patientId = max(0, $request->int('patient_id'));
        $where = 'r.owner_id = $1';
        $params = [$uid];
        if ($patientId > 0) {
            $params[] = $patientId;
            $where .= ' and r.patient_id = $2';
        }
        $prescriptions = Database::select(
            "select r.*, p.name as patient_name, p.species, p.tutor_name
             from clinic_prescriptions r join clinic_patients p on p.id = r.patient_id
             where {$where} order by r.created_at desc limit 80",
            $params
        );
        $patients = Database::select('select id, name, tutor_name from clinic_patients where owner_id = $1 order by name', [$uid]);

        return $this->panelView('panel.prescriptions.index', [
            'title' => 'Recetas · Panel clínico',
            'user' => $user,
            'prescriptions' => $prescriptions,
            'patients' => $patients,
            'patientId' => $patientId,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $patients = Database::select('select id, name, species, tutor_name from clinic_patients where owner_id = $1 order by name', [$uid]);
        $appointments = Database::select(
            'select a.id, a.starts_at, a.reason, p.name as patient_name
             from clinic_appointments a join clinic_patients p on p.id = a.patient_id
             where a.owner_id = $1 and a.status <> \'cancelada\' order by a.starts_at desc limit 30',
            [$uid]
        );
        return $this->panelView('panel.prescriptions.form', [
            'title' => 'Nueva receta · Panel clínico',
            'user' => $user,
            'patients' => $patients,
            'appointments' => $appointments,
            'patientId' => $request->int('patient_id'),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $patientId = $request->int('patient_id');
        $appointmentId = $request->int('appointment_id');
        $diagnosis = trim($request->string('diagnosis'));
        $medications = trim($request->string('medications'));
        $instructions = trim($request->string('instructions'));

        $validator = $this->validator([
            'patient_id' => $patientId, 'diagnosis' => $diagnosis, 'medications' => $medications,
        ], ['patient_id' => 'paciente', 'diagnosis' => 'diagnóstico', 'medications' => 'medicamentos']);
        $validator->validate([
            'patient_id' => 'required|integer',
            'diagnosis' => 'required|min:3|max:1000',
            'medications' => 'required|min:3|max:3000',
            'instructions' => 'nullable|max:2000',
        ]);
        $patient = Database::first('select id from clinic_patients where id = $1 and owner_id = $2', [$patientId, $uid]);
        if ($patient === null) {
            $validator->add('patient_id', 'Selecciona un paciente de tu clínica.');
        }
        if ($appointmentId > 0) {
            $appointment = Database::first('select id from clinic_appointments where id = $1 and owner_id = $2 and patient_id = $3', [$appointmentId, $uid, $patientId]);
            if ($appointment === null) {
                $validator->add('appointment_id', 'La cita no corresponde al paciente.');
            }
        }
        if ($validator->fails()) {
            $this->withErrors($validator->errors());
            $this->flashOld(['patient_id' => $patientId, 'appointment_id' => $appointmentId, 'diagnosis' => $diagnosis, 'medications' => $medications, 'instructions' => $instructions]);
            return $this->redirect('/panel/recetas/nueva?patient_id=' . $patientId);
        }

        $row = Database::insert(
            'insert into clinic_prescriptions (owner_id, patient_id, appointment_id, diagnosis, medications, instructions)
             values ($1, $2, $3, $4, $5, $6) returning id',
            [$uid, $patientId, $appointmentId > 0 ? $appointmentId : null, $diagnosis, $medications, $instructions !== '' ? $instructions : null]
        );
        $id = (int) ($row['id'] ?? 0);
        $this->audit('clinica.receta_creada', 'clinic_prescription', (string) $id);
        Flash::success('Receta guardada.');
        return $this->redirect('/panel/recetas/' . $id);
    }

    public function show(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $prescription = Database::first(
            'select r.*, p.name as patient_name, p.species, p.breed, p.sex, p.weight_kg, p.tutor_name, p.tutor_phone,
                    p.birth_date, a.starts_at
             from clinic_prescriptions r
             join clinic_patients p on p.id = r.patient_id
             left join clinic_appointments a on a.id = r.appointment_id
             where r.id = $1 and r.owner_id = $2',
            [(int) $id, (int) $user['id']]
        );
        if ($prescription === null) {
            $this->notFound('No encontramos esa receta.');
        }
        return $this->panelView('panel.prescriptions.show', [
            'title' => 'Receta de ' . $prescription['patient_name'] . ' · Panel clínico',
            'user' => $user,
            'prescription' => $prescription,
        ]);
    }
}

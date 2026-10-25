<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;

final class PanelPatientController extends PanelBaseController
{
    private const SPECIES = ['canino', 'felino', 'equino', 'bovino', 'porcino', 'ovino', 'caprino', 'ave', 'exotico', 'otro'];

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
            $where .= ' and (name ilike $2 or tutor_name ilike $2 or tutor_phone ilike $2)';
        }
        $total = (int) Database::value('select count(*) from clinic_patients where ' . $where, $params);
        $params[] = $perPage;
        $limit = '$' . count($params);
        $params[] = ($page - 1) * $perPage;
        $offset = '$' . count($params);
        $patients = Database::select(
            "select * from clinic_patients where {$where} order by name limit {$limit} offset {$offset}",
            $params
        );

        return $this->panelView('panel.patients.index', [
            'title' => 'Pacientes · Panel clínico',
            'user' => $user,
            'patients' => $patients,
            'total' => $total,
            'q' => $q,
            'paginator' => new Paginator($total, $perPage, $page),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->panelUser();
        return $this->panelView('panel.patients.form', [
            'title' => 'Nuevo paciente · Panel clínico',
            'user' => $user,
            'patient' => null,
            'speciesList' => self::SPECIES,
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->panelUser();
        [$data, $errors] = $this->validatePatient($request);
        if ($errors !== []) {
            $this->withErrors($errors);
            $this->flashOld($data);
            return $this->redirect('/panel/pacientes/nuevo');
        }

        $row = Database::insert(
            'insert into clinic_patients (owner_id, name, species, breed, sex, birth_date, weight_kg,
                tutor_name, tutor_phone, tutor_email, notes)
             values ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11) returning id',
            [
                (int) $user['id'], $data['name'], $data['species'], $data['breed'], $data['sex'],
                $data['birth_date'], $data['weight_kg'], $data['tutor_name'], $data['tutor_phone'],
                $data['tutor_email'], $data['notes'],
            ]
        );
        $id = (int) ($row['id'] ?? 0);
        $this->audit('clinica.paciente_creado', 'clinic_patient', (string) $id);
        Flash::success('Paciente registrado.');
        return $this->redirect('/panel/pacientes/' . $id);
    }

    public function show(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $patient = $this->findPatient((int) $user['id'], (int) $id);
        if ($patient === null) {
            $this->notFound('No encontramos ese paciente en tu clínica.');
        }

        $appointments = Database::select(
            'select * from clinic_appointments where owner_id = $1 and patient_id = $2 order by starts_at desc limit 8',
            [(int) $user['id'], (int) $patient['id']]
        );
        $prescriptions = Database::select(
            'select * from clinic_prescriptions where owner_id = $1 and patient_id = $2 order by created_at desc limit 6',
            [(int) $user['id'], (int) $patient['id']]
        );
        $sales = Database::select(
            'select * from clinic_sales where owner_id = $1 and patient_id = $2 order by created_at desc limit 6',
            [(int) $user['id'], (int) $patient['id']]
        );

        return $this->panelView('panel.patients.show', [
            'title' => $patient['name'] . ' · Panel clínico',
            'user' => $user,
            'patient' => $patient,
            'appointments' => $appointments,
            'prescriptions' => $prescriptions,
            'sales' => $sales,
        ]);
    }

    public function edit(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $patient = $this->findPatient((int) $user['id'], (int) $id);
        if ($patient === null) {
            $this->notFound('No encontramos ese paciente en tu clínica.');
        }
        return $this->panelView('panel.patients.form', [
            'title' => 'Editar ' . $patient['name'] . ' · Panel clínico',
            'user' => $user,
            'patient' => $patient,
            'speciesList' => self::SPECIES,
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $patient = $this->findPatient((int) $user['id'], (int) $id);
        if ($patient === null) {
            $this->notFound('No encontramos ese paciente en tu clínica.');
        }
        [$data, $errors] = $this->validatePatient($request);
        if ($errors !== []) {
            $this->withErrors($errors);
            $this->flashOld($data);
            return $this->redirect('/panel/pacientes/' . (int) $patient['id'] . '/editar');
        }

        Database::execute(
            'update clinic_patients set name = $1, species = $2, breed = $3, sex = $4, birth_date = $5,
                weight_kg = $6, tutor_name = $7, tutor_phone = $8, tutor_email = $9, notes = $10
             where id = $11 and owner_id = $12',
            [
                $data['name'], $data['species'], $data['breed'], $data['sex'], $data['birth_date'],
                $data['weight_kg'], $data['tutor_name'], $data['tutor_phone'], $data['tutor_email'],
                $data['notes'], (int) $patient['id'], (int) $user['id'],
            ]
        );
        $this->audit('clinica.paciente_actualizado', 'clinic_patient', (string) $patient['id']);
        Flash::success('Paciente actualizado.');
        return $this->redirect('/panel/pacientes/' . (int) $patient['id']);
    }

    /** @return array<string, mixed>|null */
    private function findPatient(int $ownerId, int $id): ?array
    {
        return Database::first('select * from clinic_patients where id = $1 and owner_id = $2', [$id, $ownerId]);
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>} */
    private function validatePatient(Request $request): array
    {
        $data = [
            'name' => trim($request->string('name')),
            'species' => $request->string('species'),
            'breed' => trim($request->string('breed')),
            'sex' => $request->string('sex', 'desconocido'),
            'birth_date' => $request->string('birth_date'),
            'weight_kg' => $request->string('weight_kg'),
            'tutor_name' => trim($request->string('tutor_name')),
            'tutor_phone' => trim($request->string('tutor_phone')),
            'tutor_email' => trim($request->string('tutor_email')),
            'notes' => trim($request->string('notes')),
        ];

        $validator = $this->validator($data, [
            'name' => 'nombre del paciente', 'species' => 'especie', 'breed' => 'raza',
            'sex' => 'sexo', 'birth_date' => 'fecha de nacimiento', 'weight_kg' => 'peso',
            'tutor_name' => 'nombre del tutor', 'tutor_phone' => 'teléfono', 'tutor_email' => 'correo',
            'notes' => 'notas',
        ]);
        $validator->validate([
            'name' => 'required|min:1|max:80',
            'species' => 'required|in:' . implode(',', self::SPECIES),
            'breed' => 'nullable|max:80',
            'sex' => 'required|in:macho,hembra,desconocido',
            'birth_date' => 'nullable|date',
            'weight_kg' => 'nullable|numeric|between:0.01,9999',
            'tutor_name' => 'required|min:2|max:120',
            'tutor_phone' => 'nullable|max:30',
            'tutor_email' => 'nullable|email|max:180',
            'notes' => 'nullable|max:3000',
        ]);
        if ($data['birth_date'] !== '' && strtotime($data['birth_date']) > time()) {
            $validator->add('birth_date', 'La fecha de nacimiento no puede estar en el futuro.');
        }

        $errors = $validator->errors();
        return [[
            'name' => $data['name'], 'species' => $data['species'],
            'breed' => $data['breed'] !== '' ? $data['breed'] : null,
            'sex' => $data['sex'],
            'birth_date' => $data['birth_date'] !== '' ? $data['birth_date'] : null,
            'weight_kg' => $data['weight_kg'] !== '' ? $data['weight_kg'] : null,
            'tutor_name' => $data['tutor_name'],
            'tutor_phone' => $data['tutor_phone'] !== '' ? $data['tutor_phone'] : null,
            'tutor_email' => $data['tutor_email'] !== '' ? $data['tutor_email'] : null,
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ], $errors];
    }
}

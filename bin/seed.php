<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Support\ResourceSeeder;

$options = array_slice($argv, 1);
$withDemo = in_array('--demo', $options, true) || env_bool('SEED_DEMO', false);

$catalog = require BASE_PATH . '/database/seeds/catalog.php';
$diseases = require BASE_PATH . '/database/seeds/diseases.php';
$demo = $withDemo ? require BASE_PATH . '/database/seeds/demo.php' : null;

echo "Sembrando catálogos...\n";

foreach ($catalog['states'] as [$code, $name, $region]) {
    Database::execute(
        'insert into states (code, name, region) values ($1, $2, $3)
         on conflict (code) do update set name = excluded.name, region = excluded.region',
        [$code, $name, $region]
    );
}

$speciesIds = [];
foreach ($catalog['species'] as [$code, $name, $scientific, $emoji, $order]) {
    $row = Database::insert(
        'insert into species (code, name, scientific_name, emoji, sort_order)
         values ($1, $2, $3, $4, $5)
         on conflict (code) do update set
            name = excluded.name,
            scientific_name = excluded.scientific_name,
            emoji = excluded.emoji,
            sort_order = excluded.sort_order
         returning id',
        [$code, $name, $scientific, $emoji, $order]
    );
    $speciesIds[$code] = (int) $row['id'];
}

foreach ($catalog['breeds'] as $speciesCode => $breeds) {
    foreach ($breeds as $breed) {
        Database::execute(
            'insert into breeds (species_id, name) values ($1, $2)
             on conflict (species_id, name) do nothing',
            [$speciesIds[$speciesCode], $breed]
        );
    }
}

foreach ($catalog['specialties'] as [$slug, $name]) {
    Database::execute(
        'insert into specialties (slug, name) values ($1, $2)
         on conflict (slug) do update set name = excluded.name',
        [$slug, $name]
    );
}

$signIds = [];
foreach ($catalog['signs'] as $index => [$slug, $name, $category, $description]) {
    $row = Database::insert(
        'insert into clinical_signs (slug, name, category, description, sort_order)
         values ($1, $2, $3, $4, $5)
         on conflict (slug) do update set
            name = excluded.name,
            category = excluded.category,
            description = excluded.description,
            sort_order = excluded.sort_order
         returning id',
        [$slug, $name, $category, $description, $index + 1]
    );
    $signIds[$slug] = (int) $row['id'];
}

echo "  ✓ " . count($catalog['states']) . " estados, " . count($speciesIds) . " especies, " . count($signIds) . " signos\n";

echo "Sembrando enfermedades...\n";
foreach ($diseases as $disease) {
    $row = Database::insert(
        'insert into diseases (
            slug, name, scientific_name, category, summary, etiology, diagnosis,
            treatment, prevention, prognosis, zoonotic, urgency, icd11_code, references_text
         ) values ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14)
         on conflict (slug) do update set
            name = excluded.name,
            scientific_name = excluded.scientific_name,
            category = excluded.category,
            summary = excluded.summary,
            etiology = excluded.etiology,
            diagnosis = excluded.diagnosis,
            treatment = excluded.treatment,
            prevention = excluded.prevention,
            prognosis = excluded.prognosis,
            zoonotic = excluded.zoonotic,
            urgency = excluded.urgency,
            icd11_code = excluded.icd11_code,
            references_text = excluded.references_text
         returning id',
        [
            $disease['slug'],
            $disease['name'],
            $disease['scientific_name'],
            $disease['category'],
            $disease['summary'],
            $disease['etiology'],
            $disease['diagnosis'],
            $disease['treatment'],
            $disease['prevention'],
            $disease['prognosis'],
            $disease['zoonotic'],
            $disease['urgency'],
            $disease['icd11_code'],
            $disease['references_text'],
        ]
    );
    $diseaseId = (int) $row['id'];

    Database::execute('delete from disease_species where disease_id = $1', [$diseaseId]);
    foreach ($disease['species'] as $speciesCode) {
        Database::execute(
            'insert into disease_species (disease_id, species_id) values ($1, $2)
             on conflict do nothing',
            [$diseaseId, $speciesIds[$speciesCode]]
        );
    }

    Database::execute('delete from disease_signs where disease_id = $1', [$diseaseId]);
    foreach ($disease['signs'] as $signSlug => $weight) {
        Database::execute(
            'insert into disease_signs (disease_id, sign_id, weight, is_key)
             values ($1, $2, $3, $4)',
            [$diseaseId, $signIds[$signSlug], $weight, $weight >= 4]
        );
    }
}
echo "  ✓ " . count($diseases) . " enfermedades\n";

echo "Sembrando recursos...\n";
$resourceCount = ResourceSeeder::run();
echo "  ✓ {$resourceCount} recursos con guías internas\n";

if ($withDemo) {
    echo "Sembrando usuarios y casos demostrativos...\n";
    $demoPassword = (string) (getenv('DEMO_PASSWORD') ?: App\Core\Str::token(9));
    $hash = password_hash($demoPassword, PASSWORD_DEFAULT);
    $userIds = [];

    foreach ($demo['users'] as $user) {
        $existing = Database::first('select id from users where email = $1', [$user['email']]);
        if ($existing !== null) {
            Database::execute(
                'update users set name = $1, role = $2, status = $3, license_number = $4,
                    institution = $5, state_code = $6, bio = $7, password_hash = $8, email_verified_at = case when $9 = \'verificado\' then coalesce(email_verified_at, now()) else email_verified_at end
                 where id = $10',
                [
                    $user['name'], $user['role'], $user['status'], $user['license_number'],
                    $user['institution'], $user['state_code'], $user['bio'], $hash, $user['status'],
                    (int) $existing['id'],
                ]
            );
            $userIds[$user['email']] = (int) $existing['id'];
            echo "  · Usuario existente: {$user['email']}\n";
            continue;
        }

        $row = Database::insert(
            'insert into users (
                name, email, password_hash, role, status, license_number,
                institution, state_code, bio, email_verified_at
             ) values ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
             returning id',
            [
                $user['name'], $user['email'], $hash, $user['role'], $user['status'],
                $user['license_number'], $user['institution'], $user['state_code'], $user['bio'],
                $user['status'] === 'verificado' ? date('c') : null,
            ]
        );
        $userIds[$user['email']] = (int) $row['id'];
    }

    $breedRows = Database::select('select b.id, b.name, s.code from breeds b join species s on s.id = b.species_id');
    $breedMap = [];
    foreach ($breedRows as $breedRow) {
        $breedMap[$breedRow['code'] . '|' . $breedRow['name']] = (int) $breedRow['id'];
    }

    $createdCases = 0;
    foreach ($demo['cases'] as $case) {
        $exists = Database::value('select id from cases where slug = $1', [$case['slug']]);
        if ($exists !== null) {
            echo "  · Caso existente: {$case['slug']}\n";
            continue;
        }

        $publishedAt = date('Y-m-d H:i:sP', strtotime('-' . (int) $case['days_ago'] . ' days'));
        $publishedYear = date('Y', strtotime($publishedAt));
        $folioNumber = (int) Database::value("select nextval('case_folio_seq')");
        $folio = 'CV-' . $publishedYear . '-' . str_pad((string) $folioNumber, 4, '0', STR_PAD_LEFT);
        $breedKey = $case['species'] . '|' . $case['breed'];

        $row = Database::insert(
            'insert into cases (
                folio, slug, author_id, title, species_id, breed_id, sex,
                age_value, age_unit, weight_kg, state_code, setting,
                chief_complaint, anamnesis, physical_exam, diagnostics, differentials,
                final_diagnosis, treatment, outcome, discussion, references_text,
                owner_consent, anonymized, consent_at, license, status, is_demo,
                featured, views, published_at, created_at
             ) values (
                $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14,
                $15, $16, $17, $18, $19, $20, $21, $22, $23, $24, $25, $26, $27,
                $28, $29, $30, $31, $32
             ) returning id',
            [
                $folio,
                $case['slug'],
                $userIds[$case['author_email']],
                $case['title'],
                $speciesIds[$case['species']],
                $breedMap[$breedKey] ?? null,
                $case['sex'],
                $case['age_value'],
                $case['age_unit'],
                $case['weight_kg'],
                $case['state_code'],
                $case['setting'],
                $case['chief_complaint'],
                $case['anamnesis'],
                $case['physical_exam'],
                $case['diagnostics'],
                $case['differentials'],
                $case['final_diagnosis'],
                $case['treatment'],
                $case['outcome'],
                $case['discussion'],
                $case['references_text'],
                true,
                true,
                $publishedAt,
                'CC BY-NC-SA 4.0',
                'publicado',
                true,
                $case['featured'],
                $case['views'],
                $publishedAt,
                $publishedAt,
            ]
        );
        $caseId = (int) $row['id'];
        $createdCases++;

        foreach ($case['tags'] as $tag) {
            Database::execute(
                'insert into case_tags (case_id, tag) values ($1, $2) on conflict do nothing',
                [$caseId, $tag]
            );
        }

        foreach ($case['comments'] ?? [] as $comment) {
            Database::execute(
                'insert into case_comments (case_id, user_id, body, created_at)
                 values ($1, $2, $3, $4)',
                [
                    $caseId,
                    $userIds[$comment['author_email']],
                    $comment['body'],
                    date('Y-m-d H:i:sP', strtotime('-' . (int) $comment['days_ago'] . ' days')),
                ]
            );
        }
    }

    echo "  ✓ " . count($demo['users']) . " usuarios y {$createdCases} casos nuevos\n";
    echo "  Credenciales demo: admin@diagnostico.vet / {$demoPassword}\n";
}

echo "\nResumen:\n";
foreach ([
    'estados' => 'states',
    'especies' => 'species',
    'razas' => 'breeds',
    'signos' => 'clinical_signs',
    'enfermedades' => 'diseases',
    'recursos' => 'resources',
    'usuarios' => 'users',
    'casos' => 'cases',
] as $label => $table) {
    $count = (int) Database::value('select count(*) from ' . $table);
    echo "  {$label}: {$count}\n";
}
App\Core\Cache::flush();
echo "\nListo. Controlador: " . Database::driverName() . "\n";

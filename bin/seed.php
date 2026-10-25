<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Support\ResourceSeeder;

$options = array_slice($argv, 1);
$withDemo = in_array('--demo', $options, true) || env_bool('SEED_DEMO', false);

$catalog = require BASE_PATH . '/database/seeds/catalog.php';
$problems = require BASE_PATH . '/database/seeds/problems.php';
$demo = $withDemo ? require BASE_PATH . '/database/seeds/demo.php' : null;

echo "Sembrando catálogos...\n";

foreach ($catalog['states'] as [$code, $name, $region]) {
    Database::execute(
        'insert into states (code, name, region) values ($1, $2, $3)
         on conflict (code) do update set name = excluded.name, region = excluded.region',
        [$code, $name, $region]
    );
}

$cropIds = [];
foreach ($catalog['crops'] as [$code, $name, $scientific, $emoji, $family, $cycle, $order]) {
    $row = Database::insert(
        'insert into crops (code, name, scientific_name, emoji, family, cycle, sort_order)
         values ($1, $2, $3, $4, $5, $6, $7)
         on conflict (code) do update set
            name = excluded.name,
            scientific_name = excluded.scientific_name,
            emoji = excluded.emoji,
            family = excluded.family,
            cycle = excluded.cycle,
            sort_order = excluded.sort_order
         returning id',
        [$code, $name, $scientific, $emoji, $family, $cycle, $order]
    );
    $cropIds[$code] = (int) $row['id'];
}

foreach ($catalog['varieties'] as $cropCode => $varieties) {
    foreach ($varieties as $variety) {
        Database::execute(
            'insert into varieties (crop_id, name) values ($1, $2)
             on conflict (crop_id, name) do nothing',
            [$cropIds[$cropCode], $variety]
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

$symptomIds = [];
foreach ($catalog['symptoms'] as $index => [$slug, $name, $category, $description]) {
    $row = Database::insert(
        'insert into symptoms (slug, name, category, description, sort_order)
         values ($1, $2, $3, $4, $5)
         on conflict (slug) do update set
            name = excluded.name,
            category = excluded.category,
            description = excluded.description,
            sort_order = excluded.sort_order
         returning id',
        [$slug, $name, $category, $description, $index + 1]
    );
    $symptomIds[$slug] = (int) $row['id'];
}

echo "  ✓ " . count($catalog['states']) . " estados, " . count($cropIds) . " cultivos, " . count($symptomIds) . " síntomas\n";

echo "Sembrando enfermedades...\n";
foreach ($problems as $problem) {
    $row = Database::insert(
        'insert into problems (
            slug, name, scientific_name, category, summary, etiology, diagnosis,
            management, prevention, prognosis, reportable, risk, reference_code, references_text
         ) values ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14)
         on conflict (slug) do update set
            name = excluded.name,
            scientific_name = excluded.scientific_name,
            category = excluded.category,
            summary = excluded.summary,
            etiology = excluded.etiology,
            diagnosis = excluded.diagnosis,
            management = excluded.management,
            prevention = excluded.prevention,
            prognosis = excluded.prognosis,
            reportable = excluded.reportable,
            risk = excluded.risk,
            reference_code = excluded.reference_code,
            references_text = excluded.references_text
         returning id',
        [
            $problem['slug'],
            $problem['name'],
            $problem['scientific_name'],
            $problem['category'],
            $problem['summary'],
            $problem['etiology'],
            $problem['diagnosis'],
            $problem['management'],
            $problem['prevention'],
            $problem['prognosis'],
            $problem['reportable'],
            $problem['risk'],
            $problem['reference_code'],
            $problem['references_text'],
        ]
    );
    $problemId = (int) $row['id'];

    Database::execute('delete from problem_crops where problem_id = $1', [$problemId]);
    foreach ($problem['crops'] as $cropCode) {
        Database::execute(
            'insert into problem_crops (problem_id, crop_id) values ($1, $2)
             on conflict do nothing',
            [$problemId, $cropIds[$cropCode]]
        );
    }

    Database::execute('delete from problem_symptoms where problem_id = $1', [$problemId]);
    foreach ($problem['symptoms'] as $symptomSlug => $weight) {
        Database::execute(
            'insert into problem_symptoms (problem_id, symptom_id, weight, is_key)
             values ($1, $2, $3, $4)',
            [$problemId, $symptomIds[$symptomSlug], $weight, $weight >= 4]
        );
    }
}
echo "  ✓ " . count($problems) . " problemas fitosanitarios\n";

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

    $varietyRows = Database::select('select b.id, b.name, s.code from varieties b join crops s on s.id = b.crop_id');
    $varietyMap = [];
    foreach ($varietyRows as $varietyRow) {
        $varietyMap[$varietyRow['code'] . '|' . $varietyRow['name']] = (int) $varietyRow['id'];
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
        $folio = 'AG-' . $publishedYear . '-' . str_pad((string) $folioNumber, 4, '0', STR_PAD_LEFT);
        $varietyKey = $case['crops'] . '|' . $case['variety'];

        $row = Database::insert(
            'insert into cases (
                folio, slug, author_id, title, crop_id, variety_id, cycle, crop_stage,
                area_ha, incidence_pct, state_code, municipality, system, chief_complaint,
                crop_history, field_inspection, lab_tests, differentials, final_diagnosis,
                management, outcome, discussion, references_text, producer_consent,
                anonymized, consent_at, license, status, is_demo, featured, views,
                published_at, created_at
             ) values (
                $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15,
                $16, $17, $18, $19, $20, $21, $22, $23, $24, $25, $26, $27, $28,
                $29, $30, $31, $32, $33
             ) returning id',
            [
                $folio,
                $case['slug'],
                $userIds[$case['author_email']],
                $case['title'],
                $cropIds[$case['crops']],
                $varietyMap[$varietyKey] ?? null,
                $case['cycle'],
                $case['crop_stage'],
                $case['area_ha'],
                $case['incidence_pct'],
                $case['state_code'],
                $case['municipality'],
                $case['system'],
                $case['chief_complaint'],
                $case['crop_history'],
                $case['field_inspection'],
                $case['lab_tests'],
                $case['differentials'],
                $case['final_diagnosis'],
                $case['management'],
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
    echo "  Credenciales demo: admin@agrodiagnostico.mx / {$demoPassword}\n";

    if (isset($demo['crm'])) {
        $admin = Database::first("select id from users where role = 'admin' order by id limit 1");
        if ($admin !== null) {
            $ownerId = (int) $admin['id'];
            $existingPlots = (int) Database::value('select count(*) from farm_plots where owner_id = $1', [$ownerId]);
            if ($existingPlots === 0) {
                $plotIds = [];
                foreach ($demo['crm']['plots'] as $plot) {
                    $row = Database::insert(
                        'insert into farm_plots (owner_id, name, crop, variety, area_ha, municipality, state_code,
                            irrigation, soil, producer_name, producer_phone, producer_email, notes)
                         values ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13) returning id',
                        [
                            $ownerId, $plot['name'], $plot['crop'], $plot['variety'], $plot['area_ha'],
                            $plot['municipality'], $plot['state_code'], $plot['irrigation'], $plot['soil'],
                            $plot['producer_name'], $plot['producer_phone'], $plot['producer_email'], $plot['notes'],
                        ]
                    );
                    $plotIds[] = (int) $row['id'];
                }

                $visitIds = [];
                foreach ($demo['crm']['visits'] as $visit) {
                    $startsAt = date('Y-m-d H:i:sP', strtotime('+' . (int) $visit['days'] . ' days ' . $visit['hour']));
                    $row = Database::insert(
                        'insert into farm_visits (owner_id, plot_id, starts_at, duration_min, reason, status, notes)
                         values ($1, $2, $3, $4, $5, $6, $7) returning id',
                        [$ownerId, $plotIds[$visit['plot']], $startsAt, $visit['duration_min'], $visit['reason'], $visit['status'], $visit['notes']]
                    );
                    $visitIds[] = (int) $row['id'];
                }

                foreach ($demo['crm']['recommendations'] as $index => $recommendation) {
                    Database::execute(
                        'insert into farm_recommendations (owner_id, plot_id, visit_id, diagnosis, inputs_prescribed, instructions)
                         values ($1, $2, $3, $4, $5, $6)',
                        [
                            $ownerId, $plotIds[$recommendation['plot']], $visitIds[$index] ?? null,
                            $recommendation['diagnosis'], $recommendation['inputs_prescribed'], $recommendation['instructions'],
                        ]
                    );
                }

                $inputIds = [];
                foreach ($demo['crm']['inputs'] as $input) {
                    $row = Database::insert(
                        'insert into farm_inputs (owner_id, sku, name, category, unit, price, stock, min_stock, active)
                         values ($1, $2, $3, $4, $5, $6, $7, $8, $9) returning id',
                        [
                            $ownerId, $input['sku'], $input['name'], $input['category'], $input['unit'],
                            $input['price'], $input['stock'], $input['min_stock'], $input['active'],
                        ]
                    );
                    $inputIds[$input['sku']] = (int) $row['id'];
                }

                foreach ($demo['crm']['sales'] as $sale) {
                    $items = [];
                    foreach ($sale['items'] as $item) {
                        $items[] = ['input_id' => $inputIds[$item['sku']], 'quantity' => $item['quantity']];
                    }
                    Database::value(
                        'select farm_create_sale($1, $2, $3, $4::jsonb)',
                        [
                            $ownerId,
                            $plotIds[$sale['plot']] ?? null,
                            $sale['payment_method'],
                            json_encode($items, JSON_UNESCAPED_UNICODE),
                        ]
                    );
                }
                echo "  ✓ Panel de campo demo: " . count($demo['crm']['plots']) . " parcelas, " . count($demo['crm']['visits']) . " visitas y " . count($demo['crm']['inputs']) . " insumos\n";
            }
        }
    }
}

echo "\nResumen:\n";
foreach ([
    'estados' => 'states',
    'cultivos' => 'crops',
    'variedades' => 'varieties',
    'síntomas' => 'symptoms',
    'enfermedades' => 'problems',
    'recursos' => 'resources',
    'usuarios' => 'users',
    'casos' => 'cases',
] as $label => $table) {
    $count = (int) Database::value('select count(*) from ' . $table);
    echo "  {$label}: {$count}\n";
}
App\Core\Cache::flush();
echo "\nListo. Controlador: " . Database::driverName() . "\n";

<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\SqlSplitter;

$files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
sort($files, SORT_STRING);

if ($files === []) {
    fwrite(STDERR, "No se encontraron migraciones.\n");
    exit(1);
}

Database::raw('create table if not exists schema_migrations (
    version text primary key,
    applied_at timestamptz not null default now()
)');

$applied = [];
foreach (Database::select('select version from schema_migrations') as $row) {
    $applied[$row['version']] = true;
}

$ran = 0;
foreach ($files as $file) {
    $version = basename($file);
    if (isset($applied[$version])) {
        echo "· {$version} ya aplicada\n";
        continue;
    }

    echo "→ Aplicando {$version}...\n";
    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "No se pudo leer {$file}\n");
        exit(1);
    }

    $statements = SqlSplitter::split($sql);
    foreach ($statements as $position => $statement) {
        try {
            Database::raw($statement);
        } catch (Throwable $e) {
            fwrite(STDERR, sprintf(
                "Error en %s, sentencia %d: %s\n",
                $version,
                $position + 1,
                $e->getMessage()
            ));
            exit(1);
        }
    }

    Database::execute('insert into schema_migrations (version) values ($1) on conflict do nothing', [$version]);
    echo "  ✓ {$version} aplicada (" . count($statements) . " sentencias)\n";
    $ran++;
}

echo "\nMigraciones completadas. Nuevas: {$ran}. Controlador: " . Database::driverName() . "\n";

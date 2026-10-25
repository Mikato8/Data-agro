<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$ok = true;
$line = static function (string $label, string $value, bool $good = true) use (&$ok): void {
    if (!$good) {
        $ok = false;
    }
    printf("%-34s %s %s\n", $label, $good ? '✓' : '✗', $value);
};

echo "Diagnóstico Veterinario · verificación del entorno\n";
echo str_repeat('─', 68) . "\n";

$line('PHP', PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>='));
$line('Extensiones requeridas', implode(', ', array_filter([
    extension_loaded('pdo') ? 'pdo' : null,
    extension_loaded('pdo_pgsql') ? 'pdo_pgsql' : null,
    extension_loaded('curl') ? 'curl' : null,
    extension_loaded('mbstring') ? 'mbstring' : null,
    extension_loaded('fileinfo') ? 'fileinfo' : null,
    extension_loaded('gd') ? 'gd' : null,
    extension_loaded('intl') ? 'intl' : null,
])), extension_loaded('curl') && extension_loaded('mbstring') && extension_loaded('fileinfo'));
$line('Zona horaria', date_default_timezone_get());
$line('APP_ENV', (string) config('app.env'));
$line('APP_DEBUG', config('app.debug') ? 'true (no usar en producción)' : 'false', !config('app.debug') || config('app.env') !== 'production');
$line('APP_URL', (string) config('app.url'));

try {
    $driver = Database::driverName();
    $ping = Database::ping();
    $line('Base de datos', $driver . ($ping ? ' · conexión OK' : ' · sin conexión'), $ping);
    if ($ping) {
        $migrations = Database::select('select version, applied_at from schema_migrations order by version');
        $line('Migraciones aplicadas', (string) count($migrations), $migrations !== []);
        $guides = (int) Database::value("select count(*) from resources where slug is not null and what_for is not null");
        $line('recursos con guía', (string) $guides, $guides > 0);
        foreach ([
            'usuarios' => 'users',
            'casos' => 'cases',
            'enfermedades' => 'diseases',
            'recursos' => 'resources',
            'signos clínicos' => 'clinical_signs',
        ] as $label => $table) {
            $count = (int) Database::value('select count(*) from ' . $table);
            $line($label, (string) $count, $count > 0 || $table === 'cases');
        }
    }
} catch (Throwable $e) {
    $line('Base de datos', 'error: ' . $e->getMessage(), false);
}

$uploads = (string) config('app.uploads.path');
$line('Directorio de imágenes', $uploads, is_dir($uploads) && is_writable($uploads));
$line('Directorio de sesiones', BASE_PATH . '/storage/sessions', is_dir(BASE_PATH . '/storage/sessions') && is_writable(BASE_PATH . '/storage/sessions'));
$line('Directorio de logs', BASE_PATH . '/storage/logs', is_dir(BASE_PATH . '/storage/logs') && is_writable(BASE_PATH . '/storage/logs'));
$line('.env', is_file(BASE_PATH . '/.env') ? 'presente' : 'ausente', is_file(BASE_PATH . '/.env'));
$vethubFile = BASE_PATH . '/database/data/vetdatahub.json';
$vethub = is_file($vethubFile) ? json_decode((string) file_get_contents($vethubFile), true) : null;
$line('VetDataHub integrado', is_array($vethub) ? count($vethub['datasets'] ?? []) . ' datasets' : 'sin catálogo', is_array($vethub) && !empty($vethub['datasets']));
$petevalFile = BASE_PATH . '/database/data/peteval.json';
$peteval = is_file($petevalFile) ? json_decode((string) file_get_contents($petevalFile), true) : null;
$line('PetEVAL integrado', is_array($peteval) ? count($peteval['records'] ?? []) . ' registros' : 'sin dataset', is_array($peteval) && !empty($peteval['records']));
$line('IA de urgencia', (string) config('app.ai.url') !== '' ? 'configurada' : 'sin configurar (opcional)', true);
$cedulaEnabled = (bool) config('app.cedula.enabled', false);
$cedulaConfigured = $cedulaEnabled && (string) config('app.cedula.token', '') !== '';
$line('Verificación de cédula', $cedulaConfigured ? 'automática (API Market)' : ($cedulaEnabled ? 'activada sin token' : 'manual'), true);

echo str_repeat('─', 68) . "\n";
echo $ok ? "Resultado: entorno listo.\n" : "Resultado: revisa los puntos marcados antes de desplegar.\n";
exit($ok ? 0 : 1);

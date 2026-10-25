<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Support/helpers.php';
require BASE_PATH . '/app/Support/icons.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

App\Core\Env::load(BASE_PATH . '/.env');

$config = require BASE_PATH . '/config/app.php';
App\Core\Config::set(['app' => $config]);

date_default_timezone_set((string) config('app.timezone', 'America/Mexico_City'));
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}
if (function_exists('setlocale')) {
    setlocale(LC_TIME, 'es_MX.UTF-8', 'es_MX', 'es_ES.UTF-8', 'es_ES');
}

error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    if ($severity & (E_DEPRECATED | E_USER_DEPRECATED)) {
        App\Core\Logger::warning($message . ' en ' . $file . ':' . $line);
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

if (PHP_SAPI !== 'cli') {
    $sessionPath = BASE_PATH . '/storage/sessions';
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0775, true);
    }
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }

    session_name((string) config('app.session.name', 'dv_session'));
    $rememberSession = ($_COOKIE['dv_remember'] ?? '') === '1';
    $sessionLifetime = $rememberSession ? 60 * 60 * 24 * 30 : (int) config('app.session.lifetime', 7200);
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => '/',
        'domain' => '',
        'secure' => (bool) config('app.session.secure', false),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.gc_maxlifetime', (string) $sessionLifetime);

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

App\Core\Database::configure((array) config('app.db', []));

return $config;

<?php

declare(strict_types=1);

$port = (string) (getenv('PORT') ?: '8080');
$root = dirname(__DIR__);
$command = escapeshellarg(PHP_BINARY)
    . ' -S 127.0.0.1:' . escapeshellarg($port)
    . ' -t ' . escapeshellarg($root . '/public')
    . ' ' . escapeshellarg($root . '/public/router.php');

echo "Servidor local: http://127.0.0.1:{$port}\n";
echo "Presiona Ctrl+C para detenerlo.\n";
passthru($command, $exitCode);
exit((int) $exitCode);

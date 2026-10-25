<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Support\ResourceSeeder;

$count = ResourceSeeder::run();
echo "Recursos actualizados: {$count}\n";
echo "Guías internas y enlaces listos.\n";

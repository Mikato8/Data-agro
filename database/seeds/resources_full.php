<?php

declare(strict_types=1);

$resources = require __DIR__ . '/resources.php';
$guides = require __DIR__ . '/resource_guides.php';

foreach ($resources as &$resource) {
    $guide = $guides[$resource['url']] ?? [];
    $resource = array_merge($resource, $guide);
}
unset($resource);

return $resources;

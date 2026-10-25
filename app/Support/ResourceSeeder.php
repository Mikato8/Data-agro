<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Cache;
use App\Core\Database;

final class ResourceSeeder
{
    public static function run(): int
    {
        $resources = require BASE_PATH . '/database/seeds/resources_full.php';

        foreach ($resources as $resource) {
            $tags = '{' . implode(',', array_map(
                static fn (string $tag): string => '"' . str_replace('"', '\\"', $tag) . '"',
                $resource['tags'] ?? []
            )) . '}';

            Database::execute(
                'insert into resources (
                    title, description, url, category, organization, country, access,
                    license, language, tags, featured, sort_order, slug, what_for,
                    how_to, requirements, guide, action_label, reviewed_at
                 ) values (
                    $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14,
                    $15::jsonb, $16, $17, $18, $19
                 )
                 on conflict (url) do update set
                    title = excluded.title,
                    description = excluded.description,
                    category = excluded.category,
                    organization = excluded.organization,
                    country = excluded.country,
                    access = excluded.access,
                    license = excluded.license,
                    language = excluded.language,
                    tags = excluded.tags,
                    featured = excluded.featured,
                    sort_order = excluded.sort_order,
                    slug = excluded.slug,
                    what_for = excluded.what_for,
                    how_to = excluded.how_to,
                    requirements = excluded.requirements,
                    guide = excluded.guide,
                    action_label = excluded.action_label,
                    reviewed_at = excluded.reviewed_at',
                [
                    $resource['title'],
                    $resource['description'],
                    $resource['url'],
                    $resource['category'],
                    $resource['organization'],
                    $resource['country'],
                    $resource['access'],
                    $resource['license'],
                    $resource['language'],
                    $tags,
                    $resource['featured'],
                    $resource['sort_order'],
                    $resource['slug'],
                    $resource['what_for'],
                    json_encode($resource['how_to'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    $resource['requirements'],
                    $resource['guide'],
                    $resource['action_label'],
                    $resource['reviewed_at'],
                ]
            );
        }

        Cache::flush();
        return count($resources);
    }
}

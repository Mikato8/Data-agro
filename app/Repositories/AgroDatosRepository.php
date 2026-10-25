<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Core\Database;

/**
 * Catálogo de datos abiertos: toma los recursos del directorio cuya categoría
 * corresponde a conjuntos de datos, APIs o recursos de aprendizaje automático.
 */
final class AgroDatosRepository
{
    private const CATEGORIES = ['dataset', 'api', 'ml'];

    /** @return array<int, array<string, mixed>> */
    public function categories(): array
    {
        return Cache::remember('agrodatos.categories', 600, static function (): array {
            $rows = Database::select(
                "select category, count(*) as total
                 from resources
                 where category = any($1::text[])
                 group by category
                 order by category",
                ['{' . implode(',', self::CATEGORIES) . '}']
            );
            return array_map(static fn (array $row): array => [
                'key' => (string) $row['category'],
                'label' => category_label((string) $row['category']),
                'count' => (int) $row['total'],
            ], $rows);
        });
    }

    /** @return array<int, string> */
    public function sources(): array
    {
        $rows = Database::select(
            "select distinct organization from resources
             where category = any($1::text[]) and organization is not null and organization <> ''
             order by organization",
            ['{' . implode(',', self::CATEGORIES) . '}']
        );
        return array_map(static fn (array $row): string => (string) $row['organization'], $rows);
    }

    /** @return array<int, array<string, mixed>> */
    public function search(string $q = '', string $category = '', string $source = ''): array
    {
        $params = ['{' . implode(',', self::CATEGORIES) . '}'];
        $where = ['category = any($1::text[])'];
        if ($category !== '' && in_array($category, self::CATEGORIES, true)) {
            $params[] = $category;
            $where[] = 'category = $' . count($params);
        }
        if ($source !== '') {
            $params[] = $source;
            $where[] = 'organization = $' . count($params);
        }
        $q = trim($q);
        if ($q !== '') {
            $params[] = '%' . $q . '%';
            $where[] = '(title ilike $' . count($params) . ' or description ilike $' . count($params) . ' or organization ilike $' . count($params) . ')';
        }

        $rows = Database::select(
            'select id, title, description, url, organization as source, category, slug, access, license, country
             from resources
             where ' . implode(' and ', $where) . '
             order by featured desc, sort_order, title',
            $params
        );

        foreach ($rows as &$row) {
            $row['category_label'] = category_label((string) $row['category']);
        }
        unset($row);

        return $rows;
    }

    public function total(): int
    {
        return (int) Database::value(
            "select count(*) from resources where category = any($1::text[])",
            ['{' . implode(',', self::CATEGORIES) . '}']
        );
    }
}

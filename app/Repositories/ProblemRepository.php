<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Core\Database;

final class ProblemRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function all(array $filters = []): array
    {
        $params = [];
        $where = [];
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $where[] = '(d.name ILIKE $1 or coalesce(d.scientific_name, \'\') ILIKE $2)';
        }
        $crops = (string) ($filters['crops'] ?? '');
        if ($crops !== '') {
            $params[] = $crops;
            $where[] = 'exists (select 1 from problem_crops dsp join crops sp on sp.id = dsp.crop_id
                        where dsp.problem_id = d.id and sp.code = $' . count($params) . ')';
        }
        $category = (string) ($filters['category'] ?? '');
        if ($category !== '') {
            $params[] = $category;
            $where[] = 'd.category = $' . count($params);
        }
        $whereSql = $where === [] ? '' : 'where ' . implode(' and ', $where);

        return Database::select(
            "select d.id, d.slug, d.name, d.scientific_name, d.category, d.summary,
                    d.reportable, d.risk, d.reference_code,
                    count(distinct ds.symptom_id) as signs_count,
                    string_agg(distinct sp.name, ', ' order by sp.name) as crop_names
             from problems d
             left join problem_symptoms ds on ds.problem_id = d.id
             left join problem_crops dsp on dsp.problem_id = d.id
             left join crops sp on sp.id = dsp.crop_id
             {$whereSql}
             group by d.id
             order by d.name asc",
            $params
        );
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        return Cache::remember('problem.' . $slug, 300, fn (): ?array => $this->findBySlugFresh($slug));
    }

    /** @return array<string, mixed>|null */
    private function findBySlugFresh(string $slug): ?array
    {
        $problem = Database::first('select * from problems where slug = $1', [$slug]);
        if ($problem === null) {
            return null;
        }
        $problem['id'] = (int) $problem['id'];
        $problem['reportable'] = $problem['reportable'] === true || $problem['reportable'] === 't';
        $problem['crops'] = Database::select(
            'select s.id, s.code, s.name, s.emoji from problem_crops dsp
             join crops s on s.id = dsp.crop_id where dsp.problem_id = $1 order by s.sort_order',
            [$problem['id']]
        );
        $problem['symptoms'] = Database::select(
            'select cs.id, cs.slug, cs.name, cs.category, ds.weight, ds.is_key
             from problem_symptoms ds join symptoms cs on cs.id = ds.symptom_id
             where ds.problem_id = $1
             order by ds.weight desc, cs.category, cs.name',
            [$problem['id']]
        );
        return $problem;
    }

    /** @return array<int, array<string, mixed>> */
    public function symptomsForCrops(int $cropsId): array
    {
        return Database::select(
            "select cs.* from symptoms cs
             where exists (
                 select 1 from problem_symptoms ds
                 join problem_crops dsp on dsp.problem_id = ds.problem_id
                 where ds.symptom_id = cs.id and dsp.crop_id = $1
             )
             order by case cs.category
                 when 'Follaje' then 1
                 when 'Tallo' then 2
                 when 'Raíz' then 3
                 when 'Fruto' then 4
                 when 'Flor' then 5
                 when 'Planta' then 6
                 when 'Suelo' then 7
                 when 'Manejo' then 8
                 else 9 end, cs.sort_order",
            [$cropsId]
        );
    }

    /**
     * @param array<int, int> $symptomIds
     * @return array<int, array<string, mixed>>
     */
    public function match(int $cropsId, array $symptomIds, int $selectedCount): array
    {
        if ($symptomIds === []) {
            return [];
        }
        $arrayLiteral = '{' . implode(',', array_map('intval', $symptomIds)) . '}';
        $rows = Database::select(
            "select d.id, d.slug, d.name, d.scientific_name, d.category, d.summary,
                    d.risk, d.reportable,
                    (select count(*) from problem_symptoms ds
                     where ds.problem_id = d.id and ds.symptom_id = any($2::int[])) as matched_count,
                    (select count(*) from problem_symptoms ds where ds.problem_id = d.id) as total_signs,
                    (select coalesce(sum(ds.weight), 0) from problem_symptoms ds
                     where ds.problem_id = d.id and ds.symptom_id = any($2::int[])) as matched_weight,
                    (select coalesce(sum(ds.weight), 0) from problem_symptoms ds
                     where ds.problem_id = d.id) as total_weight,
                    (select json_agg(cs.name order by ds.weight desc)
                     from problem_symptoms ds join symptoms cs on cs.id = ds.symptom_id
                     where ds.problem_id = d.id and ds.symptom_id = any($2::int[])) as matched_signs_json,
                    (select json_agg(cs.name)
                     from problem_symptoms ds join symptoms cs on cs.id = ds.symptom_id
                     where ds.problem_id = d.id and ds.is_key
                       and not (ds.symptom_id = any($2::int[]))) as missing_key_json
             from problems d
             join problem_crops dsp on dsp.problem_id = d.id
             where dsp.crop_id = $1
               and exists (
                   select 1 from problem_symptoms ds
                   where ds.problem_id = d.id and ds.symptom_id = any($2::int[])
               )
             order by matched_weight desc, matched_count desc
             limit 12",
            [$cropsId, $arrayLiteral]
        );

        foreach ($rows as &$row) {
            $matched = (int) $row['matched_count'];
            $total = max(1, (int) $row['total_signs']);
            $coverage = $selectedCount > 0 ? $matched / $selectedCount : 0;
            $profile = $matched / $total;
            $row['score'] = (int) round(min(1.0, ($coverage * 0.65) + ($profile * 0.35)) * 100);
            $row['matched_signs'] = json_decode((string) ($row['matched_signs_json'] ?? '[]'), true) ?: [];
            $row['missing_key'] = json_decode((string) ($row['missing_key_json'] ?? '[]'), true) ?: [];
            $row['reportable'] = $row['reportable'] === true || $row['reportable'] === 't';
            $row['id'] = (int) $row['id'];
            unset($row['matched_signs_json'], $row['missing_key_json']);
        }
        unset($row);

        return $rows;
    }

    /**
     * Fichas del portal relacionadas con un tema o enfermedad.
     * @return array<int, array<string, mixed>>
     */
    public function findForTopic(string $topic, string $cropCode = '', int $limit = 3): array
    {
        $topic = trim($topic);
        if ($topic === '') {
            return [];
        }
        return Cache::remember('problems.topic.' . sha1($topic . '|' . $cropCode) . '.' . $limit, 300, function () use ($topic, $cropCode, $limit): array {
            $params = ['%' . $topic . '%'];
            $where = "(d.name ILIKE $1 or coalesce(d.scientific_name, '') ILIKE $1 or d.summary ILIKE $1)";
            if ($cropCode !== '') {
                $params[] = $cropCode;
                $where .= ' and exists (
                    select 1 from problem_crops dsp
                    join crops sp on sp.id = dsp.crop_id
                    where dsp.problem_id = d.id and sp.code = $2
                )';
            }
            $params[] = $limit;
            return Database::select(
                'select d.id, d.slug, d.name, d.summary, d.risk, d.reportable
                 from problems d
                 where ' . $where . '
                 order by d.name
                 limit $' . count($params),
                $params
            );
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function featured(int $limit = 6): array
    {
        return Cache::remember('problems.featured.' . $limit, 300, static fn (): array => Database::select(
            "select d.id, d.slug, d.name, d.category, d.summary, d.reportable, d.risk,
                    string_agg(distinct s.name, ', ' order by s.name) as crop_names
             from problems d
             left join problem_crops dsp on dsp.problem_id = d.id
             left join crops s on s.id = dsp.crop_id
             where d.risk in ('alto', 'critico')
             group by d.id
             order by case d.risk when 'critico' then 0 when 'alto' then 1 else 2 end, d.name
             limit $1",
            [$limit]
        ));
    }

    /** @return array<int, string> */
    public function categories(): array
    {
        return Cache::remember('problems.categories', 3600, static function (): array {
            $rows = Database::select('select distinct category from problems order by category');
            return array_map(static fn (array $row): string => (string) $row['category'], $rows);
        });
    }

    public function count(): int
    {
        return (int) Database::value('select count(*) from problems');
    }
}

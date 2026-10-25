<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Core\Database;

final class DiseaseRepository
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
        $species = (string) ($filters['species'] ?? '');
        if ($species !== '') {
            $params[] = $species;
            $where[] = 'exists (select 1 from disease_species dsp join species sp on sp.id = dsp.species_id
                        where dsp.disease_id = d.id and sp.code = $' . count($params) . ')';
        }
        $category = (string) ($filters['category'] ?? '');
        if ($category !== '') {
            $params[] = $category;
            $where[] = 'd.category = $' . count($params);
        }
        $whereSql = $where === [] ? '' : 'where ' . implode(' and ', $where);

        return Database::select(
            "select d.id, d.slug, d.name, d.scientific_name, d.category, d.summary,
                    d.zoonotic, d.urgency, d.icd11_code,
                    count(distinct ds.sign_id) as signs_count,
                    string_agg(distinct sp.name, ', ' order by sp.name) as species_names
             from diseases d
             left join disease_signs ds on ds.disease_id = d.id
             left join disease_species dsp on dsp.disease_id = d.id
             left join species sp on sp.id = dsp.species_id
             {$whereSql}
             group by d.id
             order by d.name asc",
            $params
        );
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        return Cache::remember('disease.' . $slug, 300, fn (): ?array => $this->findBySlugFresh($slug));
    }

    /** @return array<string, mixed>|null */
    private function findBySlugFresh(string $slug): ?array
    {
        $disease = Database::first('select * from diseases where slug = $1', [$slug]);
        if ($disease === null) {
            return null;
        }
        $disease['id'] = (int) $disease['id'];
        $disease['zoonotic'] = $disease['zoonotic'] === true || $disease['zoonotic'] === 't';
        $disease['species'] = Database::select(
            'select s.id, s.code, s.name, s.emoji from disease_species dsp
             join species s on s.id = dsp.species_id where dsp.disease_id = $1 order by s.sort_order',
            [$disease['id']]
        );
        $disease['signs'] = Database::select(
            'select cs.id, cs.slug, cs.name, cs.category, ds.weight, ds.is_key
             from disease_signs ds join clinical_signs cs on cs.id = ds.sign_id
             where ds.disease_id = $1
             order by ds.weight desc, cs.category, cs.name',
            [$disease['id']]
        );
        return $disease;
    }

    /** @return array<int, array<string, mixed>> */
    public function signsForSpecies(int $speciesId): array
    {
        return Database::select(
            "select cs.* from clinical_signs cs
             where exists (
                 select 1 from disease_signs ds
                 join disease_species dsp on dsp.disease_id = ds.disease_id
                 where ds.sign_id = cs.id and dsp.species_id = $1
             )
             order by case cs.category
                 when 'Generales' then 1 when 'Digestivas' then 2 when 'Respiratorias' then 3
                 when 'Cardiovasculares' then 4 when 'Neurológicas' then 5 when 'Urinarias' then 6
                 when 'Reproductivas' then 7 when 'Dermatológicas' then 8 when 'Oftalmológicas' then 9
                 when 'Otorrinolaringológicas' then 10 when 'Musculoesqueléticas' then 11
                 when 'Hematológicas' then 12 when 'Epidemiológicas' then 13 when 'Producción' then 14
                 when 'Inmunológicas' then 15 else 16 end, cs.sort_order",
            [$speciesId]
        );
    }

    /**
     * @param array<int, int> $signIds
     * @return array<int, array<string, mixed>>
     */
    public function match(int $speciesId, array $signIds, int $selectedCount): array
    {
        if ($signIds === []) {
            return [];
        }
        $arrayLiteral = '{' . implode(',', array_map('intval', $signIds)) . '}';
        $rows = Database::select(
            "select d.id, d.slug, d.name, d.scientific_name, d.category, d.summary,
                    d.urgency, d.zoonotic,
                    (select count(*) from disease_signs ds
                     where ds.disease_id = d.id and ds.sign_id = any($2::int[])) as matched_count,
                    (select count(*) from disease_signs ds where ds.disease_id = d.id) as total_signs,
                    (select coalesce(sum(ds.weight), 0) from disease_signs ds
                     where ds.disease_id = d.id and ds.sign_id = any($2::int[])) as matched_weight,
                    (select coalesce(sum(ds.weight), 0) from disease_signs ds
                     where ds.disease_id = d.id) as total_weight,
                    (select json_agg(cs.name order by ds.weight desc)
                     from disease_signs ds join clinical_signs cs on cs.id = ds.sign_id
                     where ds.disease_id = d.id and ds.sign_id = any($2::int[])) as matched_signs_json,
                    (select json_agg(cs.name)
                     from disease_signs ds join clinical_signs cs on cs.id = ds.sign_id
                     where ds.disease_id = d.id and ds.is_key
                       and not (ds.sign_id = any($2::int[]))) as missing_key_json
             from diseases d
             join disease_species dsp on dsp.disease_id = d.id
             where dsp.species_id = $1
               and exists (
                   select 1 from disease_signs ds
                   where ds.disease_id = d.id and ds.sign_id = any($2::int[])
               )
             order by matched_weight desc, matched_count desc
             limit 12",
            [$speciesId, $arrayLiteral]
        );

        foreach ($rows as &$row) {
            $matched = (int) $row['matched_count'];
            $total = max(1, (int) $row['total_signs']);
            $coverage = $selectedCount > 0 ? $matched / $selectedCount : 0;
            $profile = $matched / $total;
            $row['score'] = (int) round(min(1.0, ($coverage * 0.65) + ($profile * 0.35)) * 100);
            $row['matched_signs'] = json_decode((string) ($row['matched_signs_json'] ?? '[]'), true) ?: [];
            $row['missing_key'] = json_decode((string) ($row['missing_key_json'] ?? '[]'), true) ?: [];
            $row['zoonotic'] = $row['zoonotic'] === true || $row['zoonotic'] === 't';
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
    public function findForTopic(string $topic, string $speciesCode = '', int $limit = 3): array
    {
        $topic = trim($topic);
        if ($topic === '') {
            return [];
        }
        return Cache::remember('diseases.topic.' . sha1($topic . '|' . $speciesCode) . '.' . $limit, 300, function () use ($topic, $speciesCode, $limit): array {
            $params = ['%' . $topic . '%'];
            $where = "(d.name ILIKE $1 or coalesce(d.scientific_name, '') ILIKE $1 or d.summary ILIKE $1)";
            if ($speciesCode !== '') {
                $params[] = $speciesCode;
                $where .= ' and exists (
                    select 1 from disease_species dsp
                    join species sp on sp.id = dsp.species_id
                    where dsp.disease_id = d.id and sp.code = $2
                )';
            }
            $params[] = $limit;
            return Database::select(
                'select d.id, d.slug, d.name, d.summary, d.urgency, d.zoonotic
                 from diseases d
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
        return Cache::remember('diseases.featured.' . $limit, 300, static fn (): array => Database::select(
            "select d.id, d.slug, d.name, d.category, d.summary, d.zoonotic, d.urgency,
                    string_agg(distinct s.name, ', ' order by s.name) as species_names
             from diseases d
             left join disease_species dsp on dsp.disease_id = d.id
             left join species s on s.id = dsp.species_id
             where d.urgency in ('alta', 'critica')
             group by d.id
             order by case d.urgency when 'critica' then 0 when 'alta' then 1 else 2 end, d.name
             limit $1",
            [$limit]
        ));
    }

    /** @return array<int, string> */
    public function categories(): array
    {
        return Cache::remember('diseases.categories', 3600, static function (): array {
            $rows = Database::select('select distinct category from diseases order by category');
            return array_map(static fn (array $row): string => (string) $row['category'], $rows);
        });
    }

    public function count(): int
    {
        return (int) Database::value('select count(*) from diseases');
    }
}

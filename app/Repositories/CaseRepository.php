<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Core\Database;

final class CaseRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function search(array $filters, int $page = 1, int $perPage = 9, bool $admin = false): array
    {
        $params = [];
        $where = [];
        if ($admin) {
            $where[] = 'c.deleted_at is null';
            $status = (string) ($filters['status'] ?? '');
            if ($status !== '' && in_array($status, ['borrador', 'en_revision', 'cambios_solicitados', 'publicado', 'rechazado', 'archivado'], true)) {
                $params[] = $status;
                $where[] = 'c.status = $' . count($params);
            }
        } else {
            $where[] = "c.status = 'publicado'";
            $where[] = 'c.deleted_at is null';
        }

        $q = trim((string) ($filters['q'] ?? ''));
        $rankSql = '0::real';
        if ($q !== '') {
            $params[] = $q;
            $fts = '$' . count($params);
            $params[] = '%' . $q . '%';
            $titleLike = '$' . count($params);
            $params[] = '%' . $q . '%';
            $diagnosisLike = '$' . count($params);
            $params[] = $q;
            $rankParam = '$' . count($params);
            $where[] = "(c.search_vector @@ websearch_to_tsquery('spanish', {$fts})
                        or c.title ILIKE {$titleLike}
                        or c.final_diagnosis ILIKE {$diagnosisLike})";
            $rankSql = "ts_rank_cd(c.search_vector, websearch_to_tsquery('spanish', {$rankParam}))";
        }

        $crops = (string) ($filters['crops'] ?? '');
        if ($crops !== '') {
            $params[] = $crops;
            $where[] = 's.code = $' . count($params);
        }

        $state = (string) ($filters['state'] ?? '');
        if ($state !== '') {
            $params[] = $state;
            $where[] = 'c.state_code = $' . count($params);
        }

        $outcome = (string) ($filters['outcome'] ?? '');
        if ($outcome !== '' && in_array($outcome, ['recuperado', 'en_manejo', 'perdida_parcial', 'perdida_total', 'en_observacion', 'desconocido'], true)) {
            $params[] = $outcome;
            $where[] = 'c.outcome = $' . count($params);
        }

        $year = (int) ($filters['year'] ?? 0);
        if ($year > 2000 && $year < 2100) {
            $params[] = $year;
            $where[] = 'extract(year from c.published_at) = $' . count($params);
        }

        $tag = trim((string) ($filters['tag'] ?? ''));
        if ($tag !== '') {
            $params[] = $tag;
            $where[] = 'exists (select 1 from case_tags ct where ct.case_id = c.id and ct.tag = $' . count($params) . ')';
        }

        $authorId = (int) ($filters['author_id'] ?? 0);
        if ($authorId > 0) {
            $params[] = $authorId;
            $where[] = 'c.author_id = $' . count($params);
        }

        if (!empty($filters['featured'])) {
            $where[] = 'c.featured = true';
        }

        $whereSql = implode(' and ', $where);
        $sort = (string) ($filters['sort'] ?? 'recientes');
        $orderSql = match ($sort) {
            'relevancia' => $q !== '' ? $rankSql . ' desc, c.published_at desc' : 'c.published_at desc',
            'vistos' => 'c.views desc, c.published_at desc',
            'antiguos' => 'c.published_at asc',
            default => 'c.published_at desc',
        };

        $offset = max(0, ($page - 1) * $perPage);
        $params[] = $perPage;
        $limit = '$' . count($params);
        $params[] = $offset;
        $offsetParam = '$' . count($params);

        $items = Database::select(
            "select c.id, c.slug, c.folio, c.title, c.final_diagnosis, c.outcome, c.views,
                    c.published_at, c.created_at, c.featured, c.is_demo, c.status,
                    c.chief_complaint, c.cycle, c.crop_stage, c.area_ha, c.incidence_pct,
                    c.municipality, c.system,
                    s.name as crop_name, s.code as crop_code, s.emoji as crop_emoji,
                    st.name as state_name, u.name as author_name, u.institution as author_institution,
                    u.status as author_status,
                    (select ci.path from case_images ci where ci.case_id = c.id order by ci.sort_order limit 1) as cover_image,
                    (select count(*) from case_comments cc where cc.case_id = c.id and cc.status = 'visible') as comments_count,
                    count(*) over() as total_count,
                    {$rankSql} as rank
             from cases c
             join crops s on s.id = c.crop_id
             join users u on u.id = c.author_id
             left join states st on st.code = c.state_code
             where {$whereSql}
             order by {$orderSql}
             limit {$limit} offset {$offsetParam}",
            $params
        );

        $total = $items === [] ? 0 : (int) $items[0]['total_count'];
        foreach ($items as &$item) {
            $item['id'] = (int) $item['id'];
            $item['views'] = (int) $item['views'];
            $item['comments_count'] = (int) $item['comments_count'];
            $item['featured'] = $item['featured'] === true || $item['featured'] === 't';
            $item['is_demo'] = $item['is_demo'] === true || $item['is_demo'] === 't';
        }
        unset($item);

        return ['items' => $items, 'total' => $total];
    }

    /** @return array<string, mixed>|null */
    public function findPublishedBySlug(string $slug): ?array
    {
        $row = Database::first(
            $this->detailSql() . " where c.slug = $1 and c.status = 'publicado' and c.deleted_at is null",
            [$slug]
        );
        return $row !== null ? $this->hydrate($row) : null;
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        $row = Database::first($this->detailSql() . ' where c.slug = $1 and c.deleted_at is null', [$slug]);
        return $row !== null ? $this->hydrate($row) : null;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $row = Database::first($this->detailSql() . ' where c.id = $1 and c.deleted_at is null', [$id]);
        return $row !== null ? $this->hydrate($row) : null;
    }

    private function detailSql(): string
    {
        return "select c.*, s.name as crop_name, s.code as crop_code, s.emoji as crop_emoji,
                       st.name as state_name, b.name as variety_name,
                       u.name as author_name, u.email as author_email, u.institution as author_institution, u.bio as author_bio,
                       u.avatar_url as author_avatar_url,
                       u.status as author_status, u.license_number as author_license,
                       coalesce((
                           select json_agg(json_build_object(
                               'id', ci.id, 'path', ci.path, 'caption', ci.caption,
                               'alt_text', ci.alt_text, 'width', ci.width, 'height', ci.height
                           ) order by ci.sort_order, ci.id)
                           from case_images ci where ci.case_id = c.id
                       ), '[]'::json) as images_json,
                       coalesce((
                           select json_agg(ct.tag order by ct.tag)
                           from case_tags ct where ct.case_id = c.id
                       ), '[]'::json) as tags_json,
                       (select count(*) from case_comments cc where cc.case_id = c.id and cc.status = 'visible') as comments_count,
                       (select count(*) from case_reactions cr where cr.case_id = c.id) as reactions_count
                from cases c
                join crops s on s.id = c.crop_id
                join users u on u.id = c.author_id
                left join states st on st.code = c.state_code
                left join varieties b on b.id = c.variety_id";
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    private function hydrate(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['views'] = (int) $row['views'];
        $row['comments_count'] = (int) $row['comments_count'];
        $row['reactions_count'] = (int) $row['reactions_count'];
        $row['featured'] = $row['featured'] === true || $row['featured'] === 't';
        $row['is_demo'] = $row['is_demo'] === true || $row['is_demo'] === 't';
        $row['producer_consent'] = $row['producer_consent'] === true || $row['producer_consent'] === 't';
        $row['anonymized'] = $row['anonymized'] === true || $row['anonymized'] === 't';
        $row['images'] = json_decode((string) ($row['images_json'] ?? '[]'), true) ?: [];
        $row['tags'] = json_decode((string) ($row['tags_json'] ?? '[]'), true) ?: [];
        unset($row['images_json'], $row['tags_json']);
        return $row;
    }

    /** @param array<string, mixed> $case
     *  @return array<int, array<string, mixed>>
     */
    public function similar(array $case, int $limit = 3): array
    {
        return Database::select(
            "select c.id, c.slug, c.title, c.final_diagnosis, c.published_at,
                    s.name as crop_name, s.code as crop_code, s.emoji as crop_emoji,
                    st.name as state_name, u.name as author_name,
                    similarity(c.final_diagnosis, $1) as diagnosis_similarity
             from cases c
             join crops s on s.id = c.crop_id
             join users u on u.id = c.author_id
             left join states st on st.code = c.state_code
             where c.status = 'publicado' and c.deleted_at is null
               and c.id <> $2 and c.crop_id = $3
             order by diagnosis_similarity desc, c.published_at desc
             limit $4",
            [(string) $case['final_diagnosis'], (int) $case['id'], (int) $case['crop_id'], $limit]
        );
    }

    /** @return array<string, int> */
    public function stats(): array
    {
        return Cache::remember('cases.stats', 300, function (): array {
            return $this->statsFresh();
        });
    }

    /** @return array<string, int> */
    private function statsFresh(): array
    {
        $row = Database::first(
            "select
                (select count(*) from cases where status = 'publicado' and deleted_at is null) as cases,
                (select count(*) from users where status = 'verificado' and deleted_at is null) as agronomos,
                (select count(distinct crop_id) from cases where status = 'publicado' and deleted_at is null) as crops,
                (select count(distinct state_code) from cases where status = 'publicado' and state_code is not null) as states,
                (select count(*) from case_comments where status = 'visible') as comments"
        ) ?? [];
        return array_map('intval', $row);
    }

    /** @return array<string, int> */
    public function adminStats(): array
    {
        $row = Database::first(
            "select
                (select count(*) from cases where status = 'en_revision' and deleted_at is null) as pending,
                (select count(*) from cases where status = 'publicado' and deleted_at is null) as published,
                (select count(*) from cases where status = 'borrador' and deleted_at is null) as drafts,
                (select count(*) from users where status = 'pendiente' and deleted_at is null) as pending_users,
                (select count(*) from users where deleted_at is null) as users,
                (select count(*) from case_comments where status = 'visible') as comments"
        ) ?? [];
        return array_map('intval', $row);
    }

    /** @return array<int, array<string, mixed>> */
    public function featured(int $limit = 3): array
    {
        return Database::select(
            "select c.id, c.slug, c.folio, c.title, c.final_diagnosis, c.chief_complaint, c.outcome, c.views, c.published_at,
                    c.featured, c.is_demo, s.name as crop_name, s.code as crop_code,
                    s.emoji as crop_emoji, st.name as state_name, u.name as author_name,
                    (select ci.path from case_images ci where ci.case_id = c.id order by ci.sort_order limit 1) as cover_image,
                    (select count(*) from case_comments cc where cc.case_id = c.id and cc.status = 'visible') as comments_count
             from cases c
             join crops s on s.id = c.crop_id
             join users u on u.id = c.author_id
             left join states st on st.code = c.state_code
             where c.status = 'publicado' and c.deleted_at is null
             order by c.featured desc, c.published_at desc
             limit $1",
            [$limit]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function latest(int $limit = 6): array
    {
        return Database::select(
            "select c.id, c.slug, c.title, c.final_diagnosis, c.outcome, c.views, c.published_at,
                    c.featured, c.is_demo, c.chief_complaint, s.name as crop_name,
                    s.code as crop_code, s.emoji as crop_emoji, st.name as state_name,
                    u.name as author_name,
                    (select ci.path from case_images ci where ci.case_id = c.id order by ci.sort_order limit 1) as cover_image,
                    (select count(*) from case_comments cc where cc.case_id = c.id and cc.status = 'visible') as comments_count
             from cases c
             join crops s on s.id = c.crop_id
             join users u on u.id = c.author_id
             left join states st on st.code = c.state_code
             where c.status = 'publicado' and c.deleted_at is null
             order by c.published_at desc
             limit $1",
            [$limit]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function byAuthor(int $authorId): array
    {
        return Database::select(
            "select c.*, s.name as crop_name, s.code as crop_code, s.emoji as crop_emoji,
                    (select count(*) from case_comments cc where cc.case_id = c.id and cc.status = 'visible') as comments_count,
                    (select count(*) from case_reviews cr where cr.case_id = c.id) as reviews_count
             from cases c join crops s on s.id = c.crop_id
             where c.author_id = $1 and c.deleted_at is null
             order by c.updated_at desc",
            [$authorId]
        );
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $row = Database::insert(
            'insert into cases (
                folio, slug, author_id, title, crop_id, variety_id, cycle, crop_stage,
                area_ha, incidence_pct, state_code, municipality, system, chief_complaint,
                crop_history, field_inspection, lab_tests, differentials, final_diagnosis,
                reference_code, management, outcome, discussion, references_text,
                producer_consent, anonymized, consent_at, license, status
             ) values (
                $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15,
                $16, $17, $18, $19, $20, $21, $22, $23, $24, $25, $26, $27, $28, $29
             ) returning id',
            [
                $data['folio'], $data['slug'], $data['author_id'], $data['title'],
                $data['crop_id'], $data['variety_id'], $data['cycle'], $data['crop_stage'],
                $data['area_ha'], $data['incidence_pct'], $data['state_code'], $data['municipality'],
                $data['system'], $data['chief_complaint'], $data['crop_history'], $data['field_inspection'],
                $data['lab_tests'], $data['differentials'], $data['final_diagnosis'],
                $data['reference_code'], $data['management'], $data['outcome'], $data['discussion'],
                $data['references_text'], $data['producer_consent'], $data['anonymized'],
                $data['consent_at'], $data['license'], $data['status'],
            ]
        );
        return (int) $row['id'];
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        Database::execute(
            'update cases set
                title = $1, crop_id = $2, variety_id = $3, cycle = $4, crop_stage = $5,
                area_ha = $6, incidence_pct = $7, state_code = $8, municipality = $9,
                system = $10, chief_complaint = $11, crop_history = $12, field_inspection = $13,
                lab_tests = $14, differentials = $15, final_diagnosis = $16,
                reference_code = $17, management = $18, outcome = $19, discussion = $20,
                references_text = $21, producer_consent = $22, anonymized = $23,
                license = $24, consent_at = $25, status = $26
             where id = $27',
            [
                $data['title'], $data['crop_id'], $data['variety_id'], $data['cycle'],
                $data['crop_stage'], $data['area_ha'], $data['incidence_pct'], $data['state_code'],
                $data['municipality'], $data['system'], $data['chief_complaint'], $data['crop_history'], $data['field_inspection'],
                $data['lab_tests'], $data['differentials'], $data['final_diagnosis'],
                $data['reference_code'], $data['management'], $data['outcome'], $data['discussion'],
                $data['references_text'], $data['producer_consent'], $data['anonymized'],
                $data['license'], $data['consent_at'], $data['status'], $id,
            ]
        );
    }

    /** @param array<int, string> $tags */
    public function replaceTags(int $caseId, array $tags): void
    {
        Database::execute('delete from case_tags where case_id = $1', [$caseId]);
        foreach (array_unique(array_slice($tags, 0, 10)) as $tag) {
            $tag = mb_substr(trim($tag), 0, 40);
            if (mb_strlen($tag) >= 2) {
                Database::execute(
                    'insert into case_tags (case_id, tag) values ($1, $2) on conflict do nothing',
                    [$caseId, $tag]
                );
            }
        }
    }

    /** @param array<string, mixed> $image */
    public function addImage(int $caseId, array $image, int $order = 0): void
    {
        Database::execute(
            'insert into case_images (case_id, path, caption, alt_text, mime_type, size_bytes, width, height, sort_order)
             values ($1, $2, $3, $4, $5, $6, $7, $8, $9)',
            [
                $caseId, $image['path'], $image['caption'] ?? null, $image['alt_text'] ?? null,
                $image['mime'], $image['size'], $image['width'], $image['height'], $order,
            ]
        );
    }

    public function clearImages(int $caseId): void
    {
        Database::execute('delete from case_images where case_id = $1', [$caseId]);
    }

    public function deleteImage(int $imageId, int $caseId): void
    {
        Database::execute('delete from case_images where id = $1 and case_id = $2', [$imageId, $caseId]);
    }

    public function submitForReview(int $id): void
    {
        Database::execute("update cases set status = 'en_revision' where id = $1", [$id]);
    }

    public function publish(int $id): void
    {
        Database::execute(
            "update cases set status = 'publicado', published_at = coalesce(published_at, now()) where id = $1",
            [$id]
        );
    }

    public function reject(int $id): void
    {
        Database::execute("update cases set status = 'rechazado' where id = $1", [$id]);
    }

    public function requestChanges(int $id): void
    {
        Database::execute("update cases set status = 'cambios_solicitados' where id = $1", [$id]);
    }

    public function archive(int $id): void
    {
        Database::execute("update cases set status = 'archivado' where id = $1", [$id]);
    }

    public function addReview(int $caseId, int $reviewerId, string $decision, string $comments): void
    {
        Database::execute(
            'insert into case_reviews (case_id, reviewer_id, decision, comments) values ($1, $2, $3, $4)',
            [$caseId, $reviewerId, $decision, $comments]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function reviews(int $caseId): array
    {
        return Database::select(
            'select r.*, u.name as reviewer_name
             from case_reviews r join users u on u.id = r.reviewer_id
             where r.case_id = $1 order by r.created_at desc',
            [$caseId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function comments(int $caseId, bool $includeHidden = false): array
    {
        $where = $includeHidden ? '' : "and c.status = 'visible'";
        return Database::select(
            "select c.*, u.name as author_name, u.role as author_role, u.status as author_status, u.institution, u.avatar_url as author_avatar_url
             from case_comments c join users u on u.id = c.user_id
             where c.case_id = $1 {$where}
             order by c.created_at asc",
            [$caseId]
        );
    }

    public function addComment(int $caseId, int $userId, string $body): int
    {
        $row = Database::insert(
            'insert into case_comments (case_id, user_id, body) values ($1, $2, $3) returning id',
            [$caseId, $userId, $body]
        );
        return (int) $row['id'];
    }

    public function hideComment(int $commentId): void
    {
        Database::execute("update case_comments set status = 'oculto' where id = $1", [$commentId]);
    }

    public function reactionCount(int $caseId): int
    {
        return (int) Database::value('select count(*) from case_reactions where case_id = $1', [$caseId]);
    }

    public function hasReacted(int $caseId, int $userId): bool
    {
        return Database::value(
            'select 1 from case_reactions where case_id = $1 and user_id = $2',
            [$caseId, $userId]
        ) !== null;
    }

    public function toggleReaction(int $caseId, int $userId): bool
    {
        if ($this->hasReacted($caseId, $userId)) {
            Database::execute('delete from case_reactions where case_id = $1 and user_id = $2', [$caseId, $userId]);
            return false;
        }
        Database::execute(
            'insert into case_reactions (case_id, user_id) values ($1, $2) on conflict do nothing',
            [$caseId, $userId]
        );
        return true;
    }

    public function incrementViews(int $caseId): void
    {
        Database::execute('update cases set views = views + 1 where id = $1', [$caseId]);
    }

    /** @return array<int, int> */
    public function years(): array
    {
        return Cache::remember('cases.years', 300, function (): array {
            $rows = Database::select(
            "select distinct extract(year from published_at)::int as year
             from cases where status = 'publicado' and published_at is not null
             order by year desc"
        );
            return array_map(static fn (array $row): int => (int) $row['year'], $rows);
        });
    }

    /** @return array<int, string> */
    public function popularTags(int $limit = 12): array
    {
        return Cache::remember('cases.tags.' . $limit, 300, function () use ($limit): array {
            $rows = Database::select(
            "select ct.tag, count(*) as total
             from case_tags ct join cases c on c.id = ct.case_id
             where c.status = 'publicado' and c.deleted_at is null
             group by ct.tag order by total desc, ct.tag asc limit $1",
            [$limit]
        );
            return array_map(static fn (array $row): string => (string) $row['tag'], $rows);
        });
    }
}

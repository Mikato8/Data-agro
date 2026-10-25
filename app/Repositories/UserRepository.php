<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class UserRepository
{
    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        return Database::first('select * from users where email = $1 and deleted_at is null', [$email]);
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        return Database::first(
            'select u.*, s.name as state_name
             from users u left join states s on s.code = u.state_code
             where u.id = $1 and u.deleted_at is null',
            [$id]
        );
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $row = Database::insert(
            'insert into users (name, email, password_hash, role, status, license_number, institution, state_code, bio,
                license_verified_at, license_verified_name, license_verified_title, license_verified_institution, license_verification_source)
             values ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14)
             returning id',
            [
                $data['name'],
                $data['email'],
                $data['password_hash'],
                $data['role'] ?? 'agronomo',
                $data['status'] ?? 'pendiente',
                $data['license_number'] ?? null,
                $data['institution'] ?? null,
                $data['state_code'] ?? null,
                $data['bio'] ?? null,
                $data['license_verified_at'] ?? null,
                $data['license_verified_name'] ?? null,
                $data['license_verified_title'] ?? null,
                $data['license_verified_institution'] ?? null,
                $data['license_verification_source'] ?? null,
            ]
        );
        return (int) $row['id'];
    }

    /** @param array<string, mixed> $data */
    public function updateProfile(int $id, array $data): void
    {
        Database::execute(
            'update users set name = $1, license_number = $2, institution = $3,
                state_code = $4, bio = $5,
                license_verified_at = case
                    when name is distinct from $1 or license_number is distinct from $2 then null
                    else license_verified_at
                end,
                license_verified_name = case
                    when name is distinct from $1 or license_number is distinct from $2 then null
                    else license_verified_name
                end,
                license_verified_title = case
                    when name is distinct from $1 or license_number is distinct from $2 then null
                    else license_verified_title
                end,
                license_verified_institution = case
                    when name is distinct from $1 or license_number is distinct from $2 then null
                    else license_verified_institution
                end,
                license_verification_source = case
                    when name is distinct from $1 or license_number is distinct from $2 then null
                    else license_verification_source
                end,
                status = case
                    when (name is distinct from $1 or license_number is distinct from $2)
                         and status = \'verificado\' and role = \'agronomo\' then \'pendiente\'
                    else status
                end
             where id = $6',
            [
                $data['name'],
                $data['license_number'],
                $data['institution'],
                $data['state_code'],
                $data['bio'],
                $id,
            ]
        );
    }

    public function updateAvatar(int $id, ?string $url): void
    {
        Database::execute('update users set avatar_url = $1 where id = $2', [$url, $id]);
    }

    public function updatePassword(int $id, string $hash): void
    {
        Database::execute('update users set password_hash = $1 where id = $2', [$hash, $id]);
    }

    /** @return array{items: array<int, array<string, mixed>>, total: int} */
    public function paginate(string $q = '', int $page = 1, int $perPage = 20, string $status = '', string $role = ''): array
    {
        $where = ['u.deleted_at is null'];
        $params = [];
        if ($q !== '') {
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $where[] = '(u.name ILIKE $1 or u.email ILIKE $2 or coalesce(u.license_number, \'\') ILIKE $3)';
        }
        if ($status !== '' && in_array($status, ['pendiente', 'verificado', 'suspendido'], true)) {
            $params[] = $status;
            $where[] = 'u.status = $' . count($params);
        }
        if ($role !== '' && in_array($role, ['agronomo', 'editor', 'admin'], true)) {
            $params[] = $role;
            $where[] = 'u.role = $' . count($params);
        }
        $whereSql = implode(' and ', $where);
        $offset = max(0, ($page - 1) * $perPage);
        $params[] = $perPage;
        $limitIndex = count($params);
        $params[] = $offset;
        $offsetIndex = count($params);

        $items = Database::select(
            "select u.*, s.name as state_name, count(c.id) as cases_count,
                    count(*) over() as total_count
             from users u
             left join states s on s.code = u.state_code
             left join cases c on c.author_id = u.id and c.status = 'publicado' and c.deleted_at is null
             where {$whereSql}
             group by u.id, s.name
             order by u.created_at desc
             limit \${$limitIndex} offset \${$offsetIndex}",
            $params
        );
        $total = $items === [] ? 0 : (int) $items[0]['total_count'];
        return ['items' => $items, 'total' => $total];
    }

    /**
     * Anonimiza la cuenta y la marca como eliminada. Los casos y comentarios
     * publicados permanecen en el archivo, pero sin datos personales del autor.
     */
    public function anonymizeAndDelete(int $id): void
    {
        Database::execute('delete from farm_sales where owner_id = $1', [$id]);
        Database::execute('delete from farm_recommendations where owner_id = $1', [$id]);
        Database::execute('delete from farm_visits where owner_id = $1', [$id]);
        Database::execute('delete from farm_plots where owner_id = $1', [$id]);
        Database::execute('delete from farm_inputs where owner_id = $1', [$id]);
        Database::execute(
            "update users set
                name = 'Cuenta eliminada',
                email = 'eliminado-' || id || '@cuenta.invalid',
                password_hash = $1,
                status = 'suspendido',
                license_number = null,
                institution = null,
                state_code = null,
                bio = null,
                avatar_url = null,
                email_verified_at = null,
                license_verified_at = null,
                license_verified_name = null,
                license_verified_title = null,
                license_verified_institution = null,
                license_verification_source = null,
                deleted_at = now()
             where id = $2 and deleted_at is null",
            [password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $id]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        Database::execute('update users set status = $1 where id = $2', [$status, $id]);
    }

    public function setRole(int $id, string $role): void
    {
        Database::execute('update users set role = $1 where id = $2', [$role, $id]);
    }

    public function count(): int
    {
        return (int) Database::value("select count(*) from users where deleted_at is null and status = 'verificado'");
    }

    public function countPending(): int
    {
        return (int) Database::value("select count(*) from users where status = 'pendiente' and deleted_at is null");
    }

    /** @return array<int, array<string, mixed>> */
    public function recent(int $limit = 6): array
    {
        return Database::select(
            'select u.*, s.name as state_name
             from users u left join states s on s.code = u.state_code
             where u.deleted_at is null
             order by u.created_at desc limit $1',
            [$limit]
        );
    }
}

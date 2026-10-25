<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Core\Database;

final class CatalogRepository
{
    /** @return array<int, array<string, mixed>> */
    public function crops(): array
    {
        return Cache::remember('catalog.crops', 3600, static fn (): array => Database::select('select * from crops order by sort_order, name'));
    }

    /** @return array<int, array<string, mixed>> */
    public function states(): array
    {
        return Cache::remember('catalog.states', 3600, static fn (): array => Database::select('select * from states order by name'));
    }

    /** @return array<int, array<string, mixed>> */
    public function specialties(): array
    {
        return Cache::remember('catalog.specialties', 3600, static fn (): array => Database::select('select * from specialties order by name'));
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public function varietiesByCrops(): array
    {
        return Cache::remember('catalog.varieties_by_crops', 3600, static function (): array {
            $rows = Database::select('select * from varieties order by name');
            $grouped = [];
            foreach ($rows as $row) {
                $grouped[(string) $row['crop_id']][] = $row;
            }
            return $grouped;
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function varieties(int $cropsId): array
    {
        return Cache::remember('catalog.varieties.' . $cropsId, 3600, static fn (): array => Database::select('select * from varieties where crop_id = $1 order by name', [$cropsId]));
    }

    /** @return array<string, mixed>|null */
    public function cropsByCode(string $code): ?array
    {
        foreach ($this->crops() as $item) {
            if ($item['code'] === $code) {
                return $item;
            }
        }
        return null;
    }

    /** @return array<string, mixed>|null */
    public function cropsById(int $id): ?array
    {
        foreach ($this->crops() as $item) {
            if ((int) $item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }

    public function stateName(string $code): string
    {
        return (string) (Database::value('select name from states where code = $1', [$code]) ?? $code);
    }

    public function varietiesForCropsCode(string $code): array
    {
        return Database::select(
            'select b.* from varieties b join crops s on s.id = b.crop_id where s.code = $1 order by b.name',
            [$code]
        );
    }
}

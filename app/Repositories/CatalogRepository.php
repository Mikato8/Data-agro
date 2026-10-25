<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Core\Database;

final class CatalogRepository
{
    /** @return array<int, array<string, mixed>> */
    public function species(): array
    {
        return Cache::remember('catalog.species', 3600, static fn (): array => Database::select('select * from species order by sort_order, name'));
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
    public function breedsBySpecies(): array
    {
        return Cache::remember('catalog.breeds_by_species', 3600, static function (): array {
            $rows = Database::select('select * from breeds order by name');
            $grouped = [];
            foreach ($rows as $row) {
                $grouped[(string) $row['species_id']][] = $row;
            }
            return $grouped;
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function breeds(int $speciesId): array
    {
        return Cache::remember('catalog.breeds.' . $speciesId, 3600, static fn (): array => Database::select('select * from breeds where species_id = $1 order by name', [$speciesId]));
    }

    /** @return array<string, mixed>|null */
    public function speciesByCode(string $code): ?array
    {
        foreach ($this->species() as $item) {
            if ($item['code'] === $code) {
                return $item;
            }
        }
        return null;
    }

    /** @return array<string, mixed>|null */
    public function speciesById(int $id): ?array
    {
        foreach ($this->species() as $item) {
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

    public function breedsForSpeciesCode(string $code): array
    {
        return Database::select(
            'select b.* from breeds b join species s on s.id = b.species_id where s.code = $1 order by b.name',
            [$code]
        );
    }
}

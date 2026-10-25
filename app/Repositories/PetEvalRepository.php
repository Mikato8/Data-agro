<?php

declare(strict_types=1);

namespace App\Repositories;

final class PetEvalRepository
{
    /** @var array<string, mixed>|null */
    private static ?array $catalog = null;

    /** @return array<string, mixed> */
    public function catalog(): array
    {
        if (self::$catalog !== null) {
            return self::$catalog;
        }
        $path = BASE_PATH . '/database/data/peteval.json';
        if (!is_file($path)) {
            self::$catalog = ['records' => [], 'stats' => [], 'icd_labels' => []];
            return self::$catalog;
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        self::$catalog = is_array($decoded) ? $decoded : ['records' => [], 'stats' => [], 'icd_labels' => []];
        return self::$catalog;
    }

    /** @return array<string, mixed> */
    public function stats(): array
    {
        return is_array($this->catalog()['stats'] ?? null) ? $this->catalog()['stats'] : [];
    }

    /** @return array<int, array<string, mixed>> */
    public function icdLabels(): array
    {
        return is_array($this->catalog()['icd_labels'] ?? null) ? $this->catalog()['icd_labels'] : [];
    }

    /** @return array<int, array<string, mixed>> */
    public function topDiseases(): array
    {
        return is_array($this->catalog()['top_diseases'] ?? null) ? $this->catalog()['top_diseases'] : [];
    }

    /** @return array<int, array<string, mixed>> */
    public function anonymisationTypes(): array
    {
        return is_array($this->catalog()['anonymisation_types'] ?? null) ? $this->catalog()['anonymisation_types'] : [];
    }

    public function total(): int
    {
        return count($this->catalog()['records'] ?? []);
    }

    /**
     * @return array{records: array<int, array<string, mixed>>, total: int}
     */
    public function search(string $q = '', string $icd = '', string $content = '', int $page = 1, int $perPage = 10): array
    {
        $q = $this->normalize($q);
        $filtered = [];
        foreach ($this->catalog()['records'] ?? [] as $record) {
            if (!is_array($record)) {
                continue;
            }
            if ($icd !== '' && !in_array($icd, $record['icd_labels'] ?? [], true)) {
                continue;
            }
            $diseases = $record['diseases'] ?? [];
            $anonymisation = $record['anonymisation'] ?? [];
            if ($content === 'disease' && empty($diseases)) {
                continue;
            }
            if ($content === 'anonymisation' && empty($anonymisation)) {
                continue;
            }
            if ($q !== '') {
                $haystack = $this->normalize(implode(' ', [
                    $record['sentence'] ?? '',
                    implode(' ', $record['icd_labels'] ?? []),
                    implode(' ', array_map(static fn (array $entity): string => (string) ($entity['entity'] ?? ''), $diseases)),
                ]));
                if (!str_contains($haystack, $q)) {
                    continue;
                }
            }
            $filtered[] = $record;
        }

        $total = count($filtered);
        $offset = max(0, ($page - 1) * $perPage);
        return ['records' => array_slice($filtered, $offset, $perPage), 'total' => $total];
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        foreach ($this->catalog()['records'] ?? [] as $record) {
            if (is_array($record) && (int) ($record['id'] ?? 0) === $id) {
                return $record;
            }
        }
        return null;
    }

    private function normalize(string $value): string
    {
        $value = trim(mb_strtolower($value));
        if ($value === '') {
            return '';
        }
        if (class_exists('Transliterator')) {
            $converted = transliterator_transliterate('Any-Latin; Latin-ASCII;', $value);
            if (is_string($converted)) {
                $value = $converted;
            }
        }
        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }
}

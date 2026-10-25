<?php

declare(strict_types=1);

namespace App\Repositories;

final class VetDataHubRepository
{
    /** @var array<string, mixed>|null */
    private static ?array $catalog = null;

    /** @return array<string, mixed> */
    public function catalog(): array
    {
        if (self::$catalog !== null) {
            return self::$catalog;
        }

        $path = BASE_PATH . '/database/data/vetdatahub.json';
        if (!is_file($path)) {
            self::$catalog = ['datasets' => [], 'categories' => [], 'source_commit' => 'desconocido', 'imported_at' => ''];
            return self::$catalog;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        self::$catalog = is_array($decoded) ? $decoded : ['datasets' => [], 'categories' => []];
        return self::$catalog;
    }

    /** @return array<int, array<string, mixed>> */
    public function categories(): array
    {
        $categories = $this->catalog()['categories'] ?? [];
        return is_array($categories) ? $categories : [];
    }

    /** @return array<int, string> */
    public function sources(): array
    {
        $sources = [];
        foreach ($this->catalog()['datasets'] ?? [] as $dataset) {
            $source = trim((string) ($dataset['source'] ?? ''));
            if ($source !== '') {
                $sources[$source] = true;
            }
        }
        $sources = array_keys($sources);
        sort($sources, SORT_NATURAL | SORT_FLAG_CASE);
        return $sources;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $q = '', string $category = '', string $source = ''): array
    {
        $q = $this->normalize($q);
        $category = trim($category);
        $source = trim($source);
        $results = [];

        foreach ($this->catalog()['datasets'] ?? [] as $dataset) {
            if (!is_array($dataset)) {
                continue;
            }
            if ($category !== '' && (string) ($dataset['category'] ?? '') !== $category) {
                continue;
            }
            if ($source !== '' && (string) ($dataset['source'] ?? '') !== $source) {
                continue;
            }
            if ($q !== '') {
                $haystack = $this->normalize(implode(' ', [
                    $dataset['title'] ?? '',
                    $dataset['description'] ?? '',
                    $dataset['category_label'] ?? '',
                    $dataset['source'] ?? '',
                ]));
                if (!str_contains($haystack, $q)) {
                    continue;
                }
            }
            $results[] = $dataset;
        }

        usort($results, static fn (array $a, array $b): int => strnatcasecmp((string) $a['title'], (string) $b['title']));
        return $results;
    }

    public function total(): int
    {
        return count($this->catalog()['datasets'] ?? []);
    }

    public function commit(): string
    {
        return (string) ($this->catalog()['source_commit'] ?? 'desconocido');
    }

    public function importedAt(): string
    {
        return (string) ($this->catalog()['imported_at'] ?? '');
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

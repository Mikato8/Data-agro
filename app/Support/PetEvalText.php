<?php

declare(strict_types=1);

namespace App\Support;

final class PetEvalText
{
    /**
     * Resalta entidades de enfermedad y anonimización sin romper el escape HTML.
     * @param array<int, array<string, mixed>> $diseases
     * @param array<int, array<string, mixed>> $anonymisation
     */
    public static function highlight(string $text, array $diseases, array $anonymisation): string
    {
        $spans = [];
        foreach ($diseases as $entity) {
            $spans[] = [
                'start' => max(0, (int) ($entity['start'] ?? 0)),
                'end' => max(0, (int) ($entity['end'] ?? 0)),
                'type' => 'disease',
            ];
        }
        foreach ($anonymisation as $entity) {
            $spans[] = [
                'start' => max(0, (int) ($entity['start'] ?? 0)),
                'end' => max(0, (int) ($entity['end'] ?? 0)),
                'type' => 'anon',
            ];
        }

        usort($spans, static fn (array $a, array $b): int => $a['start'] <=> $b['start']);
        $length = mb_strlen($text);
        $cursor = 0;
        $html = '';
        foreach ($spans as $span) {
            $start = min($span['start'], $length);
            $end = min($span['end'], $length);
            if ($end <= $start || $start < $cursor) {
                continue;
            }
            $html .= nl2br(e(mb_substr($text, $cursor, $start - $cursor)));
            $class = $span['type'] === 'disease' ? 'entity-disease' : 'entity-anon';
            $html .= '<mark class="' . $class . '">' . e(mb_substr($text, $start, $end - $start)) . '</mark>';
            $cursor = $end;
        }
        $html .= nl2br(e(mb_substr($text, $cursor)));

        return $html;
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

final class CasePdf
{
    /** @param array<string, mixed> $case */
    public static function render(array $case): string
    {
        $pdf = new SimplePdf((string) $case['title']);
        $pdf->title((string) $case['title']);

        $pdf->label('Diagnóstico final');
        $pdf->paragraph((string) $case['final_diagnosis'], 14, 18);
        $pdf->small('Folio ' . $case['folio'] . ' · ' . format_date((string) $case['published_at'], 'd/m/Y') . ' · ' . $case['author_name']);
        $pdf->space(6);

        $pdf->heading('Datos del cultivo y la parcela');
        $plot = 'Cultivo: ' . $case['crop_name'];
        if (!empty($case['variety_name'])) {
            $plot .= ' · Variedad: ' . $case['variety_name'];
        }
        $plot .= ' · Ciclo: ' . cycle_label((string) ($case['cycle'] ?? 'no_aplica'));
        $plot .= ' · Etapa: ' . stage_label((string) ($case['crop_stage'] ?? 'no_aplica'));
        if (($case['area_ha'] ?? null) !== null) {
            $plot .= ' · Superficie: ' . $case['area_ha'] . ' ha';
        }
        if (($case['incidence_pct'] ?? null) !== null) {
            $plot .= ' · Incidencia: ' . $case['incidence_pct'] . ' %';
        }
        if (!empty($case['state_name'])) {
            $plot .= ' · Estado: ' . $case['state_name'];
        }
        if (!empty($case['municipality'])) {
            $plot .= ' · Municipio: ' . $case['municipality'];
        }
        $pdf->paragraph($plot);
        $pdf->paragraph('Sistema: ' . system_label((string) ($case['system'] ?? 'campo_abierto')) . ' · Resultado: ' . outcome_label((string) $case['outcome']));
        if (!empty($case['reference_code'])) {
            $pdf->paragraph('Clave de referencia: ' . $case['reference_code']);
        }

        if (!empty($case['images'])) {
            $pdf->heading('Imágenes del caso');
            foreach ($case['images'] as $image) {
                $path = BASE_PATH . '/public/uploads/cases/' . ltrim((string) $image['path'], '/');
                $caption = trim((string) ($image['caption'] ?? ''));
                if ($caption === '') {
                    $caption = trim((string) ($image['alt_text'] ?? ''));
                }
                $pdf->image($path, $caption);
            }
        }

        $pdf->heading('Motivo de consulta');
        $pdf->paragraph((string) $case['chief_complaint']);

        self::section($pdf, 'Historial de manejo', (string) $case['crop_history']);
        self::section($pdf, 'Inspección de campo', (string) $case['field_inspection']);
        self::section($pdf, 'Análisis y diagnóstico', (string) $case['lab_tests']);
        self::section($pdf, 'Diagnóstico diferencial', (string) $case['differentials']);
        self::section($pdf, 'Manejo recomendado', (string) $case['management']);
        self::section($pdf, 'Discusión y aprendizaje', (string) $case['discussion']);
        self::section($pdf, 'Referencias', (string) $case['references_text']);

        if (!empty($case['tags'])) {
            $pdf->heading('Temas del caso');
            $pdf->paragraph(implode(' · ', $case['tags']));
        }

        $pdf->heading('Cómo citar este caso');
        $year = !empty($case['published_at']) ? date('Y', strtotime((string) $case['published_at'])) : date('Y');
        $pdf->paragraph($case['author_name'] . ' (' . $year . '). ' . $case['title'] . '. AgroDiagnóstico. ' . url('/casos/' . $case['slug']));
        $pdf->paragraph('Licencia: ' . $case['license']);

        $pdf->space(8);
        $pdf->small('Documento generado desde AgroDiagnóstico. El contenido es educativo y no sustituye el juicio clínico ni la atención agronómica presencial. Los datos del caso fueron anonimizados por el autor y revisados editorialmente.');
        $pdf->small('Generado el ' . date('d/m/Y H:i') . ' · ' . config('app.url'));

        return $pdf->output();
    }

    private static function section(SimplePdf $pdf, string $title, string $content): void
    {
        $content = trim($content);
        if ($content === '') {
            return;
        }
        $pdf->heading($title);
        $pdf->paragraph($content);
    }
}

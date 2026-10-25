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

        $pdf->heading('Datos del paciente');
        $patient = 'Especie: ' . $case['species_name'];
        if (!empty($case['breed_name'])) {
            $patient .= ' · Raza: ' . $case['breed_name'];
        }
        $patient .= ' · Sexo: ' . sex_label((string) $case['sex']);
        $patient .= ' · Edad: ' . age_label($case['age_value'] !== null ? (int) $case['age_value'] : null, $case['age_unit']);
        if ($case['weight_kg'] !== null) {
            $patient .= ' · Peso: ' . $case['weight_kg'] . ' kg';
        }
        if (!empty($case['state_name'])) {
            $patient .= ' · Estado: ' . $case['state_name'];
        }
        $pdf->paragraph($patient);
        $pdf->paragraph('Entorno: ' . setting_label((string) $case['setting']) . ' · Desenlace: ' . outcome_label((string) $case['outcome']));
        if (!empty($case['icd11_code'])) {
            $pdf->paragraph('Código ICD-11: ' . $case['icd11_code']);
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

        self::section($pdf, 'Anamnesis', (string) $case['anamnesis']);
        self::section($pdf, 'Exploración física', (string) $case['physical_exam']);
        self::section($pdf, 'Estudios y diagnóstico', (string) $case['diagnostics']);
        self::section($pdf, 'Diagnósticos diferenciales', (string) $case['differentials']);
        self::section($pdf, 'Tratamiento y manejo', (string) $case['treatment']);
        self::section($pdf, 'Discusión y aprendizaje', (string) $case['discussion']);
        self::section($pdf, 'Referencias', (string) $case['references_text']);

        if (!empty($case['tags'])) {
            $pdf->heading('Temas del caso');
            $pdf->paragraph(implode(' · ', $case['tags']));
        }

        $pdf->heading('Cómo citar este caso');
        $year = !empty($case['published_at']) ? date('Y', strtotime((string) $case['published_at'])) : date('Y');
        $pdf->paragraph($case['author_name'] . ' (' . $year . '). ' . $case['title'] . '. Diagnóstico Veterinario. ' . url('/casos/' . $case['slug']));
        $pdf->paragraph('Licencia: ' . $case['license']);

        $pdf->space(8);
        $pdf->small('Documento generado desde Diagnóstico Veterinario. El contenido es educativo y no sustituye el juicio clínico ni la atención veterinaria presencial. Los datos del caso fueron anonimizados por el autor y revisados editorialmente.');
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

<?php

declare(strict_types=1);

namespace App\Support;

use ZipArchive;

final class CaseDocx
{
    /** @param array<string, mixed> $case */
    public static function render(array $case): string
    {
        $body = '';
        $body .= self::p((string) $case['title'], 'Title');
        $body .= self::p('Diagnóstico final: ' . $case['final_diagnosis'], 'Subtitle');
        $body .= self::p('Folio ' . $case['folio'] . ' · ' . format_date((string) $case['published_at'], 'd/m/Y') . ' · ' . $case['author_name'], 'Meta');
        $body .= self::p('');

        $body .= self::p('Datos del cultivo y la parcela', 'Heading1');
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
        $body .= self::p($plot);
        $body .= self::p('Sistema: ' . system_label((string) ($case['system'] ?? 'campo_abierto')) . ' · Resultado: ' . outcome_label((string) $case['outcome']));
        if (!empty($case['reference_code'])) {
            $body .= self::p('Clave de referencia: ' . $case['reference_code']);
        }

        foreach ([
            'Motivo de consulta' => (string) $case['chief_complaint'],
            'Historial de manejo' => (string) $case['crop_history'],
            'Inspección de campo' => (string) $case['field_inspection'],
            'Análisis y diagnóstico' => (string) $case['lab_tests'],
            'Diagnóstico diferencial' => (string) $case['differentials'],
            'Manejo recomendado' => (string) $case['management'],
            'Discusión y aprendizaje' => (string) $case['discussion'],
            'Referencias' => (string) $case['references_text'],
        ] as $heading => $content) {
            $content = trim($content);
            if ($content === '') {
                continue;
            }
            $body .= self::p($heading, 'Heading1');
            $body .= self::p($content);
        }

        if (!empty($case['tags'])) {
            $body .= self::p('Temas del caso', 'Heading2');
            $body .= self::p(implode(' · ', $case['tags']));
        }

        $body .= self::p('Cómo citar este caso', 'Heading1');
        $year = !empty($case['published_at']) ? date('Y', strtotime((string) $case['published_at'])) : date('Y');
        $body .= self::p($case['author_name'] . ' (' . $year . '). ' . $case['title'] . '. AgroDiagnóstico. ' . url('/casos/' . $case['slug']));
        $body .= self::p('Licencia: ' . $case['license']);
        if (!empty($case['images'])) {
            $body .= self::p('Las imágenes del caso están disponibles en la versión web: ' . url('/casos/' . $case['slug']), 'Meta');
        }
        $body .= self::p('Documento generado desde AgroDiagnóstico. El contenido es educativo y no sustituye el juicio clínico ni la atención agronómica presencial.', 'Meta');

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . $body
            . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/>'
            . '<w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="709" w:footer="709" w:gutter="0"/>'
            . '</w:sectPr></w:body></w:document>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/><w:color w:val="12211D"/></w:rPr></w:rPrDefault>'
            . '<w:pPrDefault><w:pPr><w:spacing w:after="140" w:line="276" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
            . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:rPr><w:b/><w:color w:val="0D5C4D"/><w:sz w:val="48"/></w:rPr></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:basedOn w:val="Normal"/><w:rPr><w:b/><w:color w:val="0D5C4D"/><w:sz w:val="26"/></w:rPr></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:before="320" w:after="140"/><w:outlineLvl w:val="0"/></w:pPr><w:rPr><w:b/><w:color w:val="0D5C4D"/><w:sz w:val="30"/></w:rPr></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:before="240" w:after="100"/><w:outlineLvl w:val="1"/></w:pPr><w:rPr><w:b/><w:color w:val="0D5C4D"/><w:sz w:val="24"/></w:rPr></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Meta"><w:name w:val="Meta"/><w:basedOn w:val="Normal"/><w:rPr><w:color w:val="64746E"/><w:sz w:val="18"/></w:rPr></w:style>'
            . '</w:styles>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';

        $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $created = date('Y-m-d\TH:i:s\Z');
        $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . self::xml($case['title']) . '</dc:title>'
            . '<dc:creator>AgroDiagnóstico</dc:creator>'
            . '<cp:lastModifiedBy>AgroDiagnóstico</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:modified>'
            . '</cp:coreProperties>';

        $app = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            . 'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>AgroDiagnóstico</Application></Properties>';

        $path = tempnam(sys_get_temp_dir(), 'dv_docx_');
        if ($path === false) {
            throw new \RuntimeException('No fue posible crear el documento Word.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            throw new \RuntimeException('No fue posible generar el documento Word.');
        }
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('word/document.xml', $document);
        $zip->addFromString('word/styles.xml', $styles);
        $zip->addFromString('word/_rels/document.xml.rels', $docRels);
        $zip->addFromString('docProps/core.xml', $core);
        $zip->addFromString('docProps/app.xml', $app);
        $zip->close();

        $bytes = file_get_contents($path);
        @unlink($path);
        if ($bytes === false) {
            throw new \RuntimeException('No fue posible leer el documento Word.');
        }
        return $bytes;
    }

    private static function p(string $text, string $style = 'Normal'): string
    {
        $styleXml = $style !== 'Normal' ? '<w:pPr><w:pStyle w:val="' . self::xml($style) . '"/></w:pPr>' : '';
        $lines = preg_split('/\R/u', $text) ?: [''];
        $runs = '';
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $runs .= '<w:r><w:br/></w:r>';
            }
            $runs .= '<w:r><w:t xml:space="preserve">' . self::xml($line) . '</w:t></w:r>';
        }
        return '<w:p>' . $styleXml . $runs . '</w:p>';
    }

    private static function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}

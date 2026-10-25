<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Generador PDF mínimo, sin dependencias externas.
 * Usa las fuentes estándar Helvetica/Helvetica-Bold y codificación Windows-1252,
 * suficiente para español.
 */
final class SimplePdf
{
    private const PAGE_WIDTH = 595.0;
    private const PAGE_HEIGHT = 842.0;
    private const MARGIN = 46.0;
    private const BOTTOM = 58.0;

    /** @var array<int, string> */
    private array $pages = [];

    private string $buffer = '';
    private float $y = 0.0;
    private int $pageNumber = 0;

    /** @var array<int, array{data: string, width: int, height: int}> */
    private array $images = [];

    public function __construct(private readonly string $documentTitle = 'AgroDiagnóstico')
    {
        $this->addPage();
    }

    public function addPage(): void
    {
        if ($this->buffer !== '') {
            $this->pages[] = $this->buffer;
        }
        $this->buffer = '';
        $this->pageNumber++;
        $this->y = self::PAGE_HEIGHT - self::MARGIN;

        $this->buffer .= $this->textCommand(
            self::MARGIN,
            self::PAGE_HEIGHT - 30,
            'AGRODIAGNÓSTICO',
            8,
            true,
            [0.36, 0.45, 0.42]
        );
        $this->buffer .= $this->textCommand(
            self::PAGE_WIDTH - self::MARGIN - 120,
            self::PAGE_HEIGHT - 30,
            'Documento técnico',
            8,
            false,
            [0.45, 0.52, 0.49]
        );
        $this->buffer .= $this->ruleCommand(self::PAGE_HEIGHT - 38);
        $this->y = self::PAGE_HEIGHT - 56;
    }

    public function title(string $text): void
    {
        $this->ensureSpace(60);
        foreach ($this->wrap($text, 21, self::PAGE_WIDTH - self::MARGIN * 2, true) as $line) {
            $this->buffer .= $this->textCommand(self::MARGIN, $this->y, $line, 21, true, [0.05, 0.30, 0.26]);
            $this->y -= 25;
        }
        $this->y -= 4;
    }

    public function heading(string $text): void
    {
        $this->ensureSpace(50);
        $this->y -= 4;
        foreach ($this->wrap($text, 13, self::PAGE_WIDTH - self::MARGIN * 2, true) as $line) {
            $this->buffer .= $this->textCommand(self::MARGIN, $this->y, $line, 13, true, [0.05, 0.30, 0.26]);
            $this->y -= 17;
        }
        $this->buffer .= $this->ruleCommand($this->y + 4);
        $this->y -= 10;
    }

    public function label(string $text): void
    {
        $this->ensureSpace(22);
        $this->buffer .= $this->textCommand(self::MARGIN, $this->y, mb_strtoupper($text), 7.5, true, [0.42, 0.48, 0.46]);
        $this->y -= 12;
    }

    public function paragraph(string $text, float $size = 10.0, float $leading = 14.5): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }
        foreach (preg_split('/\R/u', $text) ?: [] as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                $this->y -= $leading * 0.6;
                continue;
            }
            foreach ($this->wrap($paragraph, $size, self::PAGE_WIDTH - self::MARGIN * 2, false) as $line) {
                $this->ensureSpace($leading + 2);
                $this->buffer .= $this->textCommand(self::MARGIN, $this->y, $line, $size, false, [0.10, 0.13, 0.12]);
                $this->y -= $leading;
            }
            $this->y -= 3;
        }
    }

    public function small(string $text): void
    {
        $this->paragraph($text, 8.0, 11.0);
    }

    public function space(float $height = 8.0): void
    {
        $this->y -= $height;
    }

    public function image(string $path, string $caption = ''): void
    {
        if (!is_file($path) || !function_exists('imagecreatefromstring')) {
            return;
        }
        $source = @imagecreatefromstring((string) file_get_contents($path));
        if ($source === false) {
            return;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $maxWidth = 1400;
        if ($width > $maxWidth) {
            $ratio = $maxWidth / $width;
            $newWidth = (int) floor($width * $ratio);
            $newHeight = (int) floor($height * $ratio);
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $white);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
            $width = $newWidth;
            $height = $newHeight;
        }

        ob_start();
        imagejpeg($source, null, 84);
        $jpeg = (string) ob_get_clean();
        imagedestroy($source);
        if ($jpeg === '') {
            return;
        }

        $this->images[] = ['data' => $jpeg, 'width' => $width, 'height' => $height];
        $index = count($this->images);
        $drawWidth = self::PAGE_WIDTH - self::MARGIN * 2;
        $drawHeight = $drawWidth * ($height / max(1, $width));
        if ($drawHeight > 300) {
            $drawHeight = 300;
            $drawWidth = $drawHeight * ($width / max(1, $height));
        }
        $x = (self::PAGE_WIDTH - $drawWidth) / 2;
        $this->ensureSpace($drawHeight + 28);
        $this->y -= $drawHeight;
        $this->buffer .= sprintf(
            "q
%.2F 0 0 %.2F %.2F %.2F cm
/Im%d Do
Q
",
            $drawWidth,
            $drawHeight,
            $x,
            $this->y,
            $index
        );
        $this->y -= 12;
        if ($caption !== '') {
            $this->small($caption);
        }
        $this->y -= 4;
    }

    public function output(): string
    {
        if ($this->buffer !== '') {
            $this->pages[] = $this->buffer;
            $this->buffer = '';
        }

        $total = count($this->pages);
        foreach ($this->pages as $index => &$page) {
            $page .= $this->textCommand(
                self::MARGIN,
                32,
                'Documento educativo · AgroDiagnóstico · Página ' . ($index + 1) . ' de ' . $total,
                7.5,
                false,
                [0.45, 0.52, 0.49]
            );
        }
        unset($page);

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        $pageObjects = [];
        $imageBase = 5 + (count($this->pages) * 2);
        $xObjects = '';
        foreach ($this->images as $index => $image) {
            $xObjects .= '/Im' . ($index + 1) . ' ' . ($imageBase + $index) . ' 0 R ';
        }
        $resources = '/Resources << /Font << /F1 3 0 R /F2 4 0 R >>'
            . ($xObjects !== '' ? ' /XObject << ' . $xObjects . '>>' : '')
            . ' >>';
        foreach ($this->pages as $index => $content) {
            $pageId = 5 + ($index * 2);
            $contentId = $pageId + 1;
            $kids[] = $pageId . ' 0 R';
            $pageObjects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] '
                . $resources . ' /Contents ' . $contentId . ' 0 R >>';
            $pageObjects[$contentId] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($this->pages) . ' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        foreach ($pageObjects as $id => $body) {
            $objects[$id] = $body;
        }
        foreach ($this->images as $index => $image) {
            $id = $imageBase + $index;
            $objects[$id] = '<< /Type /XObject /Subtype /Image /Width ' . $image['width']
                . ' /Height ' . $image['height'] . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 '
                . '/Filter /DCTDecode /Length ' . strlen($image['data']) . " >>\nstream\n"
                . $image['data'] . "\nendstream";
        }

        ksort($objects);
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $maxId = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id <= $maxId; $id++) {
            $offset = $offsets[$id] ?? 0;
            $pdf .= sprintf('%010d 00000 n ', $offset) . "\n";
        }
        $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF\n";

        return $pdf;
    }

    private function ensureSpace(float $height): void
    {
        if ($this->y - $height < self::BOTTOM) {
            $this->addPage();
        }
    }

    /** @return array<int, string> */
    private function wrap(string $text, float $size, float $maxWidth, bool $bold): array
    {
        $maxChars = max(8, (int) floor($maxWidth / ($size * ($bold ? 0.66 : 0.62))));
        $lines = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $paragraph) {
            $words = preg_split('/\s+/u', trim($paragraph)) ?: [];
            $line = '';
            foreach ($words as $word) {
                if ($word === '') {
                    continue;
                }
                if (mb_strlen($word) > $maxChars) {
                    if ($line !== '') {
                        $lines[] = $line;
                        $line = '';
                    }
                    while (mb_strlen($word) > $maxChars) {
                        $lines[] = mb_substr($word, 0, $maxChars);
                        $word = mb_substr($word, $maxChars);
                    }
                }
                $candidate = $line === '' ? $word : $line . ' ' . $word;
                if (mb_strlen($candidate) <= $maxChars) {
                    $line = $candidate;
                } else {
                    if ($line !== '') {
                        $lines[] = $line;
                    }
                    $line = $word;
                }
            }
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        return $lines === [] ? [''] : $lines;
    }

    private function textCommand(float $x, float $y, string $text, float $size, bool $bold, array $color): string
    {
        $encoded = $this->encode($text);
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
        $font = $bold ? '/F2' : '/F1';
        return sprintf(
            "BT\n%s %.2F Tf\n%.3F %.3F %.3F rg\n%.2F %.2F Td\n(%s) Tj\nET\n",
            $font,
            $size,
            $color[0],
            $color[1],
            $color[2],
            $x,
            $y,
            $escaped
        );
    }

    private function ruleCommand(float $y): string
    {
        return sprintf(
            "0.84 0.86 0.84 RG\n0.6 w\n%.2F %.2F m %.2F %.2F l S\n",
            self::MARGIN,
            $y,
            self::PAGE_WIDTH - self::MARGIN,
            $y
        );
    }

    private function encode(string $text): string
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? $text;
        $encoded = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
        if ($encoded === false) {
            $encoded = @mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
        }
        return $encoded === false ? preg_replace('/[^\x20-\x7E]/', '?', $text) : $encoded;
    }
}

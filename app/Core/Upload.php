<?php

declare(strict_types=1);

namespace App\Core;

final class Upload
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @param array<string, mixed> $file
     * @return array{path: string, url: string, mime: string, size: int, width: int, height: int}
     */
    public static function image(array $file, string $folder, ?string $root = null, string $urlBase = '/uploads/cases', int $maxDimension = 2400): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(self::errorMessage($error));
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('No se recibió un archivo válido.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > (int) config('app.uploads.max_bytes', 5242880)) {
            throw new \RuntimeException('Cada imagen debe pesar menos de 5 MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmp);
        if (!isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('Formato no permitido. Usa JPG, PNG o WebP.');
        }

        $info = @getimagesize($tmp);
        if ($info === false || $info[0] < 20 || $info[1] < 20) {
            throw new \RuntimeException('La imagen no es válida o es demasiado pequeña.');
        }
        if ($info[0] > 9000 || $info[1] > 9000) {
            throw new \RuntimeException('La imagen excede las dimensiones permitidas.');
        }

        $extension = self::ALLOWED[$mime];
        $folder = trim($folder, '/');
        $directory = rtrim($root ?? (string) config('app.uploads.path'), '/') . '/' . $folder;
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('No fue posible crear el directorio de imágenes.');
        }

        $filename = Str::filename($extension);
        $destination = $directory . '/' . $filename;

        $processed = self::reencode($tmp, $destination, $mime, $maxDimension);
        if (!$processed) {
            if (!@move_uploaded_file($tmp, $destination)) {
                throw new \RuntimeException('No fue posible guardar la imagen.');
            }
        }

        @chmod($destination, 0644);
        $finalSize = (int) (@filesize($destination) ?: $size);
        $finalInfo = @getimagesize($destination) ?: $info;

        return [
            'path' => $folder . '/' . $filename,
            'url' => rtrim($urlBase, '/') . '/' . $folder . '/' . $filename,
            'mime' => $mime,
            'size' => $finalSize,
            'width' => (int) $finalInfo[0],
            'height' => (int) $finalInfo[1],
        ];
    }

    private static function reencode(string $source, string $destination, string $mime, int $maxDimension = 2400): bool
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return false;
        }

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png' => @imagecreatefrompng($source),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
            default => false,
        };
        if (!$image) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $max = $maxDimension;
        if ($width > $max || $height > $max) {
            $ratio = min($max / $width, $max / $height);
            $newWidth = max(1, (int) floor($width * $ratio));
            $newHeight = max(1, (int) floor($height * $ratio));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            if ($mime === 'image/png' || $mime === 'image/webp') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($image, $destination, 85),
            'image/png' => imagepng($image, $destination, 6),
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, $destination, 85) : false,
            default => false,
        };
        imagedestroy($image);
        return (bool) $ok;
    }

    private static function errorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen excede el tamaño máximo permitido.',
            UPLOAD_ERR_PARTIAL => 'La imagen se subió de forma incompleta. Intenta de nuevo.',
            UPLOAD_ERR_NO_FILE => 'No seleccionaste ninguna imagen.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo guardar la imagen.',
            default => 'No fue posible procesar la imagen.',
        };
    }
}

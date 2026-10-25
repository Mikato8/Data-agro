<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Str;
use App\Core\Upload;
use App\Core\Validator;
use App\Core\View;

abstract class BaseController
{
    /** @param array<string, mixed> $data */
    protected function view(string $view, array $data = [], string $layout = 'app'): Response
    {
        $data['title'] = $data['title'] ?? (string) config('app.name');
        $data['metaDescription'] = $data['metaDescription'] ?? 'Portal de casos clínicos veterinarios en México: comparte, consulta y aprende de casos reales revisados por colegas.';
        $data['canonical'] = $data['canonical'] ?? url(current_path());
        return Response::html(View::render($view, $data, $layout));
    }

    protected function redirect(string $path, int $status = 302): Response
    {
        return Response::redirect(url($path), $status);
    }

    protected function back(string $fallback = '/'): Response
    {
        return Response::redirect(Request::capture()->previousUrl() ?: url($fallback));
    }

    protected function notFound(string $message = 'No encontramos lo que buscabas.'): never
    {
        throw new \App\Core\HttpException(404, $message);
    }

    /** @param array<string, mixed> $data
     *  @param array<string, string> $rules
     *  @param array<string, string> $labels
     */
    protected function validator(array $data, array $rules, array $labels = []): Validator
    {
        return new Validator($data, $labels);
    }

    /** @param array<string, string> $errors */
    protected function withErrors(array $errors): void
    {
        Flash::withErrors($errors);
    }

    /** @param array<string, mixed> $data */
    protected function flashOld(array $data): void
    {
        Flash::old($data);
    }

    protected function audit(string $action, ?string $entityType = null, ?string $entityId = null, array $meta = []): void
    {
        try {
            Database::execute(
                'insert into audit_log (user_id, action, entity_type, entity_id, meta, ip)
                 values ($1, $2, $3, $4, $5, $6::inet)',
                [Auth::id(), $action, $entityType, $entityId, json_encode($meta, JSON_UNESCAPED_UNICODE), Auth::ip()]
            );
        } catch (\Throwable $e) {
            \App\Core\Logger::warning('No se pudo escribir auditoría', ['message' => $e->getMessage()]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    protected function uploadedImages(Request $request): array
    {
        $files = $request->file('images');
        if ($files === null || !isset($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $normalized = [];
        foreach ($files['name'] as $index => $name) {
            if ((int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $normalized[] = [
                'name' => $name,
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
                'caption' => trim((string) ($request->post('captions')[$index] ?? '')),
                'alt_text' => trim((string) ($request->post('alt_texts')[$index] ?? '')),
            ];
        }
        return $normalized;
    }

    protected function saveImages(int $caseId, Request $request): int
    {
        $all = $this->uploadedImages($request);
        if ($all === []) {
            return 0;
        }
        $existing = (int) Database::value('select count(*) from case_images where case_id = $1', [$caseId]);
        $remaining = max(0, (int) config('app.uploads.max_files', 6) - $existing);
        $files = array_slice($all, 0, $remaining);
        $saved = 0;
        foreach ($files as $index => $file) {
            try {
                $image = Upload::image($file, 'caso-' . $caseId);
                $image['caption'] = $file['caption'] !== '' ? mb_substr($file['caption'], 0, 240) : null;
                $image['alt_text'] = $file['alt_text'] !== '' ? mb_substr($file['alt_text'], 0, 240) : null;
                (new \App\Repositories\CaseRepository())->addImage($caseId, $image, $index);
                $saved++;
            } catch (\Throwable $e) {
                Flash::error('Una imagen no se pudo guardar: ' . $e->getMessage());
            }
        }
        return $saved;
    }

    protected function caseFolio(): string
    {
        $number = (int) Database::value("select nextval('case_folio_seq')");
        return 'CV-' . date('Y') . '-' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    protected function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title, 70);
        $candidate = $base;
        $suffix = 2;
        while (true) {
            $exists = Database::value(
                'select id from cases where slug = $1' . ($ignoreId !== null ? ' and id <> $2' : ''),
                $ignoreId !== null ? [$candidate, $ignoreId] : [$candidate]
            );
            if ($exists === null) {
                return $candidate;
            }
            $candidate = $base . '-' . $suffix;
            $suffix++;
            if ($suffix > 20) {
                $candidate = $base . '-' . Str::token(4);
            }
        }
    }

    /** @return array<int, string> */
    protected function tagsFromInput(string $value): array
    {
        $parts = preg_split('/[,;\n]+/', $value) ?: [];
        $tags = [];
        foreach ($parts as $part) {
            $tag = mb_substr(trim($part), 0, 40);
            if (mb_strlen($tag) >= 2) {
                $tags[] = $tag;
            }
        }
        return array_values(array_unique(array_slice($tags, 0, 10)));
    }

    /** @param array<string, mixed> $case
     *  @param array<string, mixed> $user
     */
    protected function canEditCase(array $case, array $user): bool
    {
        if (in_array($user['role'], (array) config('app.moderators', []), true)) {
            return true;
        }
        return (int) $case['author_id'] === (int) $user['id']
            && in_array($case['status'], ['borrador', 'cambios_solicitados', 'rechazado'], true);
    }

    /** @return array<string, mixed> */
    protected function caseFormData(): array
    {
        $catalog = new \App\Repositories\CatalogRepository();
        return [
            'species' => $catalog->species(),
            'states' => $catalog->states(),
            'breedsBySpecies' => $catalog->breedsBySpecies(),
        ];
    }
}

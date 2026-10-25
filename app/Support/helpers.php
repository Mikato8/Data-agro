<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Str;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('env_bool')) {
    function env_bool(string $key, bool $default = false): bool
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        $base = rtrim((string) config('app.url', ''), '/');
        if ($path === '' || $path === '/') {
            return $base . '/';
        }
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('current_path')) {
    function current_path(): string
    {
        return \App\Core\Request::capture()->path();
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $file = BASE_PATH . '/public/' . $path;
        $version = is_file($file) ? (string) filemtime($file) : '1';
        return url($path) . '?v=' . $version;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('field_error')) {
    function field_error(string $field): ?string
    {
        return \App\Core\Flash::firstError($field);
    }
}

if (!function_exists('has_errors')) {
    function has_errors(): bool
    {
        return \App\Core\Flash::errors() !== [];
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = Flash::oldInput();
        return $old[$key] ?? $default;
    }
}

if (!function_exists('auth')) {
    function auth(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('can')) {
    function can(string $ability): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }
        return match ($ability) {
            'moderar' => in_array($user['role'], (array) config('app.moderators', []), true),
            'administrar' => $user['role'] === 'admin',
            'publicar' => in_array($user['role'], (array) config('app.moderators', []), true),
            'comentar' => true,
            default => false,
        };
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd M Y'): string
    {
        if ($date === null || $date === '') {
            return '—';
        }
        $months = [
            1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun',
            'jul', 'ago', 'sep', 'oct', 'nov', 'dic',
        ];
        try {
            $dt = (new DateTimeImmutable($date))->setTimezone(new DateTimeZone((string) config('app.timezone', 'America/Mexico_City')));
        } catch (Exception) {
            return '—';
        }
        $formatted = $dt->format($format);
        return str_replace(
            ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            array_values($months),
            $formatted
        );
    }
}

if (!function_exists('time_ago')) {
    function time_ago(?string $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        try {
            $dt = (new DateTimeImmutable($date))->setTimezone(new DateTimeZone((string) config('app.timezone', 'America/Mexico_City')));
        } catch (Exception) {
            return '';
        }
        $diff = time() - $dt->getTimestamp();
        if ($diff < 60) {
            return 'hace un momento';
        }
        if ($diff < 3600) {
            $n = (int) floor($diff / 60);
            return 'hace ' . $n . ' min';
        }
        if ($diff < 86400) {
            $n = (int) floor($diff / 3600);
            return 'hace ' . $n . ' h';
        }
        if ($diff < 86400 * 30) {
            $n = (int) floor($diff / 86400);
            return 'hace ' . $n . ' d';
        }
        return format_date($date, 'd/m/Y');
    }
}

if (!function_exists('excerpt')) {
    function excerpt(?string $text, int $limit = 160): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)) ?? '');
        if (mb_strlen($clean) <= $limit) {
            return $clean;
        }
        return rtrim(mb_substr($clean, 0, $limit - 1)) . '…';
    }
}

if (!function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts));
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        return $letters !== '' ? $letters : 'DV';
    }
}

if (!function_exists('avatar_tag')) {
    function avatar_tag(string $name, ?string $url = null, string $class = ''): string
    {
        $classes = trim('avatar ' . $class);
        if ($url !== null && $url !== '') {
            return '<img class="' . e($classes) . '" src="' . e($url) . '" alt="' . e($name) . '" loading="lazy">';
        }
        return '<span class="' . e($classes) . '" title="' . e($name) . '" aria-hidden="true">' . e(initials($name)) . '</span>';
    }
}

if (!function_exists('species_label')) {
    function species_label(string $code): string
    {
        return [
            'canino' => 'Canino', 'felino' => 'Felino', 'equino' => 'Equino',
            'bovino' => 'Bovino', 'porcino' => 'Porcino', 'ovino' => 'Ovino',
            'caprino' => 'Caprino', 'ave' => 'Ave', 'exotico' => 'Exótico', 'otro' => 'Otro',
        ][$code] ?? ucfirst($code);
    }
}

if (!function_exists('species_icon')) {
    function species_icon(string $code): string
    {
        return in_array($code, ['canino', 'felino', 'equino', 'bovino', 'porcino', 'ovino', 'caprino', 'ave', 'exotico'], true)
            ? $code
            : 'paw';
    }
}

if (!function_exists('status_label')) {
    function status_label(string $status): string
    {
        return [
            'borrador' => 'Borrador',
            'en_revision' => 'En revisión',
            'cambios_solicitados' => 'Cambios solicitados',
            'publicado' => 'Publicado',
            'rechazado' => 'Rechazado',
            'archivado' => 'Archivado',
        ][$status] ?? $status;
    }
}

if (!function_exists('status_tone')) {
    function status_tone(string $status): string
    {
        return [
            'borrador' => 'neutral',
            'en_revision' => 'warning',
            'cambios_solicitados' => 'warning',
            'publicado' => 'success',
            'rechazado' => 'danger',
            'archivado' => 'neutral',
        ][$status] ?? 'neutral';
    }
}

if (!function_exists('outcome_label')) {
    function outcome_label(?string $outcome): string
    {
        return [
            'curado' => 'Resuelto / curado',
            'en_tratamiento' => 'En tratamiento',
            'secuela' => 'Con secuela',
            'fallecido' => 'Fallecido',
            'eutanasia' => 'Eutanasia',
            'desconocido' => 'Sin seguimiento',
        ][(string) $outcome] ?? 'Sin seguimiento';
    }
}

if (!function_exists('age_label')) {
    function age_label(?int $value, ?string $unit): string
    {
        if ($value === null || $unit === null) {
            return 'Edad no registrada';
        }
        $labels = ['dias' => 'día(s)', 'semanas' => 'semana(s)', 'meses' => 'mes(es)', 'anios' => 'año(s)'];
        return $value . ' ' . ($labels[$unit] ?? $unit);
    }
}

if (!function_exists('sex_label')) {
    function sex_label(?string $sex): string
    {
        return ['macho' => 'Macho', 'hembra' => 'Hembra', 'desconocido' => 'No determinado'][(string) $sex] ?? 'No determinado';
    }
}

if (!function_exists('setting_label')) {
    function setting_label(?string $setting): string
    {
        return [
            'clinica' => 'Clínica',
            'hospital' => 'Hospital',
            'campo' => 'Campo',
            'laboratorio' => 'Laboratorio',
            'otro' => 'Otro',
        ][(string) $setting] ?? 'Clínica';
    }
}

if (!function_exists('urgency_label')) {
    function urgency_label(string $urgency): string
    {
        return ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'critica' => 'Crítica'][$urgency] ?? $urgency;
    }
}

if (!function_exists('access_label')) {
    function access_label(string $access): string
    {
        return [
            'abierto' => 'Acceso abierto',
            'solicitud' => 'Por solicitud',
            'pago' => 'De pago',
            'mixto' => 'Acceso mixto',
        ][$access] ?? $access;
    }
}

if (!function_exists('category_label')) {
    function category_label(string $category): string
    {
        return [
            'dataset' => 'Conjunto de datos',
            'api' => 'API',
            'repositorio' => 'Repositorio',
            'vigilancia' => 'Vigilancia',
            'guia' => 'Guía',
            'ml' => 'Aprendizaje automático',
            'institucion' => 'Institución',
            'biblioteca' => 'Biblioteca',
            'directorio' => 'Directorio',
            'revista' => 'Revista',
            'plataforma' => 'Plataforma',
        ][$category] ?? $category;
    }
}

if (!function_exists('is_active')) {
    function is_active(string $prefix): string
    {
        $path = current_path();
        if ($prefix === '/') {
            return $path === '/' ? 'is-active' : '';
        }
        return str_starts_with($path, $prefix) ? 'is-active' : '';
    }
}

if (!function_exists('mask_email')) {
    function mask_email(string $email): string
    {
        return Str::maskEmail($email);
    }
}

if (!function_exists('share_url')) {
    function share_url(string $path): string
    {
        return rawurlencode(url($path));
    }
}

if (!function_exists('pg_array')) {
    /** Convierte una representación textual de PostgreSQL ({a,b}) en lista PHP. */
    function pg_array(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '' || $value === '{}') {
            return [];
        }
        $inner = trim($value);
        if (str_starts_with($inner, '{') && str_ends_with($inner, '}')) {
            $inner = substr($inner, 1, -1);
        }
        if ($inner === '') {
            return [];
        }
        $items = str_getcsv($inner, ',', '"', '\\');
        return array_values(array_filter(array_map(static fn ($item) => trim((string) $item), $items), static fn ($item) => $item !== ''));
    }
}

if (!function_exists('first_name')) {
    function first_name(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts));
        if ($parts === []) {
            return 'colega';
        }
        $titles = ['dra.', 'dr.', 'dra', 'dr', 'mvz', 'mv', 'lic.'];
        if (in_array(mb_strtolower($parts[0]), $titles, true)) {
            array_shift($parts);
        }
        return $parts[0] ?? 'colega';
    }
}

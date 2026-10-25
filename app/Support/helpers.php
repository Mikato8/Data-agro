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
        $cut = mb_substr($clean, 0, $limit - 1);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false && $space > (int) ($limit * 0.6)) {
            $cut = mb_substr($cut, 0, $space);
        }
        return rtrim($cut, " ,;:.") . '…';
    }
}

if (!function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts));
        $titles = ['dra.', 'dr.', 'ing.', 'lic.', 'mtro.', 'mtra.', 'biol.'];
        while ($parts !== [] && in_array(mb_strtolower($parts[0]), $titles, true)) {
            array_shift($parts);
        }
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        return $letters !== '' ? $letters : 'AG';
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

if (!function_exists('crops_label')) {
    function crops_label(string $code): string
    {
        static $names = [
            'maiz' => 'Maíz', 'frijol' => 'Frijol', 'trigo' => 'Trigo', 'sorgo' => 'Sorgo',
            'arroz' => 'Arroz', 'cebada' => 'Cebada', 'avena' => 'Avena', 'cana' => 'Caña de azúcar',
            'cafe' => 'Café', 'agave' => 'Agave', 'aguacate' => 'Aguacate', 'limon' => 'Limón',
            'naranja' => 'Naranja', 'mango' => 'Mango', 'platano' => 'Plátano', 'jitomate' => 'Jitomate',
            'chile' => 'Chile', 'papa' => 'Papa', 'cebolla' => 'Cebolla', 'ajo' => 'Ajo',
            'sandia' => 'Sandía', 'melon' => 'Melón', 'pepino' => 'Pepino', 'calabaza' => 'Calabaza',
            'fresa' => 'Fresa', 'vid' => 'Vid', 'algodon' => 'Algodón', 'soya' => 'Soya',
            'girasol' => 'Girasol', 'ajonjoli' => 'Ajonjolí', 'cacao' => 'Cacao', 'coco' => 'Coco',
            'durazno' => 'Durazno', 'manzana' => 'Manzana', 'nogal' => 'Nogal', 'guayaba' => 'Guayaba',
            'papaya' => 'Papaya', 'pina' => 'Piña', 'brocoli' => 'Brócoli', 'zanahoria' => 'Zanahoria',
            'lechuga' => 'Lechuga', 'garbanzo' => 'Garbanzo', 'cacahuate' => 'Cacahuate',
            'nopal' => 'Nopal', 'arandano' => 'Arándano', 'frambuesa' => 'Frambuesa',
        ];
        return $names[$code] ?? ucfirst(str_replace(['-', '_'], ' ', $code));
    }
}

if (!function_exists('crop_icon')) {
    function crop_icon(string $code): string
    {
        return match (true) {
            in_array($code, ['cafe', 'cacao', 'aguacate', 'mango', 'limon', 'naranja', 'durazno', 'manzana', 'nogal', 'guayaba', 'papaya', 'coco', 'vid', 'arandano', 'frambuesa', 'fresa'], true) => 'tree',
            in_array($code, ['jitomate', 'chile', 'papa', 'sandia', 'melon', 'pepino', 'calabaza', 'brocoli', 'zanahoria', 'lechuga', 'ajo', 'cebolla'], true) => 'sprout',
            default => 'wheat',
        };
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
            'recuperado' => 'Recuperado',
            'en_manejo' => 'En manejo',
            'perdida_parcial' => 'Pérdida parcial',
            'perdida_total' => 'Pérdida total',
            'en_observacion' => 'En observación',
            'desconocido' => 'Sin seguimiento',
        ][(string) $outcome] ?? 'Sin seguimiento';
    }
}

if (!function_exists('cycle_label')) {
    function cycle_label(?string $cycle): string
    {
        return [
            'primavera-verano' => 'Primavera-verano',
            'otono-invierno' => 'Otoño-invierno',
            'perenne' => 'Perenne',
            'continuo' => 'Continuo',
            'no_aplica' => 'No especificado',
        ][(string) $cycle] ?? 'No especificado';
    }
}

if (!function_exists('stage_label')) {
    function stage_label(?string $stage): string
    {
        return [
            'siembra' => 'Siembra',
            'emergencia' => 'Emergencia',
            'vegetativo' => 'Vegetativo',
            'floracion' => 'Floración',
            'fructificacion' => 'Fructificación',
            'llenado' => 'Llenado de grano o fruto',
            'madurez' => 'Madurez',
            'cosecha' => 'Cosecha',
            'postcosecha' => 'Postcosecha',
            'no_aplica' => 'No especificada',
        ][(string) $stage] ?? 'No especificada';
    }
}

if (!function_exists('system_label')) {
    function system_label(?string $system): string
    {
        return [
            'campo_abierto' => 'Campo abierto',
            'invernadero' => 'Invernadero',
            'macrotunel' => 'Macrotúnel',
            'hidroponia' => 'Hidroponía',
            'vivero' => 'Vivero',
            'otro' => 'Otro',
        ][(string) $system] ?? 'Campo abierto';
    }
}

if (!function_exists('irrigation_label')) {
    function irrigation_label(?string $irrigation): string
    {
        return [
            'temporal' => 'Temporal',
            'gravedad' => 'Gravedad',
            'goteo' => 'Goteo',
            'aspersion' => 'Aspersión',
            'microaspersion' => 'Microaspersión',
            'otro' => 'Otro',
        ][(string) $irrigation] ?? 'Temporal';
    }
}

if (!function_exists('risk_label')) {
    function risk_label(string $risk): string
    {
        return ['bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto', 'critico' => 'Crítico'][$risk] ?? $risk;
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
        $titles = ['dra.', 'dr.', 'dra', 'dr', 'ing.', 'ing', 'lic.', 'mtro.', 'mtra.', 'biól.', 'biol.'];
        if (in_array(mb_strtolower($parts[0]), $titles, true)) {
            array_shift($parts);
        }
        return $parts[0] ?? 'colega';
    }
}

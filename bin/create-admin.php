<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Str;

$options = getopt('', ['name:', 'email:', 'password:', 'license:', 'state::', 'force']);

$ask = static function (string $label, string $default = ''): string {
    $suffix = $default !== '' ? ' [' . $default . ']' : '';
    $value = trim((string) readline($label . $suffix . ': '));
    return $value !== '' ? $value : $default;
};

$name = trim((string) ($options['name'] ?? '')) ?: $ask('Nombre completo');
$email = Str::normalizeEmail((string) ($options['email'] ?? '')) ?: Str::normalizeEmail($ask('Correo electrónico'));
$password = (string) ($options['password'] ?? '');
if ($password === '') {
    $password = $ask('Contraseña (mínimo 12 caracteres)');
}
$license = trim((string) ($options['license'] ?? '')) ?: $ask('Cédula profesional', 'ADMIN-' . date('Ymd'));
$state = strtoupper(trim((string) ($options['state'] ?? ''))) ?: 'CMX';
$force = array_key_exists('force', $options);

$errors = [];
if (mb_strlen($name) < 2) {
    $errors[] = 'El nombre debe tener al menos 2 caracteres.';
}
if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    $errors[] = 'El correo electrónico no es válido.';
}
if (mb_strlen($password) < 12) {
    $errors[] = 'La contraseña debe tener al menos 12 caracteres.';
}
if (Database::value('select 1 from states where code = $1', [$state]) === null) {
    $errors[] = 'El estado no existe en el catálogo (ej. CMX, JAL, NLE).';
}
if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

$existing = Database::first('select id, role from users where email = $1', [$email]);
if ($existing !== null && !$force) {
    fwrite(STDERR, "Ya existe una cuenta con ese correo. Usa --force para restablecer su contraseña y rol.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

if ($existing !== null) {
    Database::execute(
        "update users set name = $1, password_hash = $2, role = 'admin', status = 'verificado',
            license_number = $3, state_code = $4, email_verified_at = now()
         where id = $5",
        [$name, $hash, $license, $state, (int) $existing['id']]
    );
    echo "Administrador actualizado: {$email}\n";
} else {
    $duplicate = Database::value('select id from users where license_number = $1', [$license]);
    if ($duplicate !== null) {
        $license .= '-' . Str::token(3);
    }
    Database::execute(
        "insert into users (name, email, password_hash, role, status, license_number, state_code, email_verified_at)
         values ($1, $2, $3, 'admin', 'verificado', $4, $5, now())",
        [$name, $email, $hash, $license, $state]
    );
    echo "Administrador creado: {$email}\n";
}

echo "Accede en " . url('/acceder') . " y cambia la contraseña después del primer inicio de sesión.\n";

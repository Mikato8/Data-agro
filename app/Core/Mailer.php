<?php

declare(strict_types=1);

namespace App\Core;

final class Mailer
{
    public static function send(string $to, string $subject, string $html): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (!config('app.mail.enabled', false)) {
            Logger::info('Correo no enviado (MAIL_ENABLED=false)', ['to' => $to, 'subject' => $subject]);
            return false;
        }

        $from = (string) config('app.mail.from', 'no-reply@agrodiagnostico.mx');
        $fromName = (string) config('app.mail.from_name', 'AgroDiagnóstico');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . self::encodeHeader($fromName) . ' <' . $from . '>',
            'Reply-To: ' . $from,
            'X-Mailer: DiagnosticoAgrónomo/1.0',
        ];

        $ok = @mail($to, self::encodeHeader($subject), $html, implode("\r\n", $headers));
        if (!$ok) {
            Logger::error('No fue posible enviar el correo', ['to' => $to, 'subject' => $subject]);
        }
        return $ok;
    }

    /** @param array<string, mixed> $user */
    public static function passwordReset(array $user, string $token): bool
    {
        $link = url('/recuperar/' . $token);
        $name = e((string) $user['name']);
        $html = <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;color:#14231f">
          <h2 style="color:#2f5a3d">Restablece tu contraseña</h2>
          <p>Hola {$name},</p>
          <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en AgroDiagnóstico.</p>
          <p><a href="{$link}" style="display:inline-block;background:#2f5a3d;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none">Crear nueva contraseña</a></p>
          <p>El enlace vence en 60 minutos. Si no solicitaste este cambio, ignora este correo.</p>
          <p style="color:#5b6b66;font-size:13px">AgroDiagnóstico · Portal de casos agronómicos para ingenieros agrónomos en México</p>
        </div>
        HTML;

        $sent = self::send((string) $user['email'], 'Restablece tu contraseña · AgroDiagnóstico', $html);
        if (!$sent && config('app.debug')) {
            Logger::info('Enlace de recuperación (modo desarrollo)', ['link' => $link]);
        }
        return $sent;
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, string> */
    private array $labels;

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $labels
     */
    public function __construct(private array $data, array $labels = [])
    {
        $this->labels = $labels;
    }

    /**
     * @param array<string, string> $rules
     */
    public function validate(array $rules): bool
    {
        foreach ($rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $ruleList = explode('|', $ruleString);
            $nullable = in_array('nullable', $ruleList, true);

            if ($nullable && ($value === null || $value === '' || $value === [])) {
                continue;
            }

            foreach ($ruleList as $rule) {
                if ($rule === '' || $rule === 'nullable') {
                    continue;
                }
                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
                $this->apply($field, $value, $name, $parameter);
            }
        }
        return $this->errors === [];
    }

    private function apply(string $field, mixed $value, string $rule, ?string $parameter): void
    {
        $label = $this->labels[$field] ?? $this->humanize($field);

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || $value === [] || $value === false) {
                    $this->add($field, 'El campo ' . $label . ' es obligatorio.');
                }
                break;
            case 'accepted':
                if (!in_array($value, ['1', 1, true, 'on', 'si', 'sí'], true)) {
                    $this->add($field, 'Debes aceptar ' . $label . '.');
                }
                break;
            case 'string':
                if ($value !== null && !is_string($value) && !is_numeric($value)) {
                    $this->add($field, 'El campo ' . $label . ' debe ser texto.');
                }
                break;
            case 'integer':
                if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->add($field, 'El campo ' . $label . ' debe ser un número entero.');
                }
                break;
            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->add($field, 'El campo ' . $label . ' debe ser numérico.');
                }
                break;
            case 'email':
                if ($value !== null && $value !== '' && filter_var((string) $value, FILTER_VALIDATE_EMAIL) === false) {
                    $this->add($field, 'Escribe un correo electrónico válido.');
                }
                break;
            case 'url':
                if ($value !== null && $value !== '' && filter_var((string) $value, FILTER_VALIDATE_URL) === false) {
                    $this->add($field, 'Escribe una URL válida (incluye https://).');
                }
                break;
            case 'min':
                $min = (float) $parameter;
                $length = is_array($value) ? count($value) : mb_strlen((string) $value);
                if ($length < $min) {
                    $this->add($field, 'El campo ' . $label . ' debe tener al menos ' . (int) $min . ' caracteres.');
                }
                break;
            case 'max':
                $max = (float) $parameter;
                $length = is_array($value) ? count($value) : mb_strlen((string) $value);
                if ($length > $max) {
                    $this->add($field, 'El campo ' . $label . ' no debe exceder ' . (int) $max . ' caracteres.');
                }
                break;
            case 'between':
                [$min, $max] = array_pad(explode(',', (string) $parameter), 2, null);
                if (!is_numeric($value) || (float) $value < (float) $min || (float) $value > (float) $max) {
                    $this->add($field, 'El campo ' . $label . ' debe estar entre ' . $min . ' y ' . $max . '.');
                }
                break;
            case 'in':
                $allowed = explode(',', (string) $parameter);
                if ($value !== null && !in_array((string) $value, $allowed, true)) {
                    $this->add($field, 'El valor de ' . $label . ' no es válido.');
                }
                break;
            case 'same':
                if ((string) $value !== (string) ($this->data[$parameter] ?? '')) {
                    $this->add($field, 'La confirmación de ' . $label . ' no coincide.');
                }
                break;
            case 'date':
                if ($value !== null && $value !== '' && strtotime((string) $value) === false) {
                    $this->add($field, 'El campo ' . $label . ' debe ser una fecha válida.');
                }
                break;
            case 'regex':
                if ($value !== null && $value !== '' && @preg_match((string) $parameter, (string) $value) !== 1) {
                    $this->add($field, 'El formato de ' . $label . ' no es válido.');
                }
                break;
            case 'boolean':
                if (!in_array($value, [true, false, 0, 1, '0', '1'], true)) {
                    $this->add($field, 'El campo ' . $label . ' debe ser verdadero o falso.');
                }
                break;
        }
    }

    public function add(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    private function humanize(string $field): string
    {
        return str_replace(['_', '-'], ' ', $field);
    }
}

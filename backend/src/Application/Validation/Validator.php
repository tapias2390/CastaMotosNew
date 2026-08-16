<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Exceptions\ValidationException;

/**
 * Validador simple basado en reglas por campo (sección 5: "Validadores").
 * No pretende ser un framework completo, solo cubrir las reglas que la API
 * necesita, de forma legible y sin dependencias externas.
 *
 * Uso:
 *   Validator::make($data, [
 *       'email' => 'required|email',
 *       'password' => 'required|min:8|confirmed',
 *   ])->validate();
 */
final class Validator
{
    private array $errors = [];

    private function __construct(private array $data, private array $rules)
    {
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    /**
     * @throws ValidationException si alguna regla falla.
     */
    public function validate(): array
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        if (!empty($this->errors)) {
            throw new ValidationException('Los datos enviados no son válidos.', $this->errors);
        }

        return $this->data;
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        [$name, $parameter] = str_contains($rule, ':') ? explode(':', $rule, 2) : [$rule, null];

        $isEmpty = $value === null || $value === '';

        switch ($name) {
            case 'required':
                if ($isEmpty) {
                    $this->addError($field, 'Este campo es obligatorio.');
                }
                break;

            case 'email':
                if (!$isEmpty && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'Debe ser un correo electrónico válido.');
                }
                break;

            case 'min':
                if (!$isEmpty && mb_strlen((string) $value) < (int) $parameter) {
                    $this->addError($field, "Debe tener al menos {$parameter} caracteres.");
                }
                break;

            case 'max':
                if (!$isEmpty && mb_strlen((string) $value) > (int) $parameter) {
                    $this->addError($field, "No debe superar los {$parameter} caracteres.");
                }
                break;

            case 'confirmed':
                $confirmationField = $field . '_confirmation';
                if (!$isEmpty && ($this->data[$confirmationField] ?? null) !== $value) {
                    $this->addError($field, 'La confirmación no coincide.');
                }
                break;

            case 'accepted':
                if (!in_array($value, [true, 1, '1', 'true'], true)) {
                    $this->addError($field, 'Debes aceptar este campo para continuar.');
                }
                break;

            case 'boolean':
                if (!$isEmpty && !is_bool($value) && !in_array($value, [0, 1, '0', '1', true, false], true)) {
                    $this->addError($field, 'Debe ser verdadero o falso.');
                }
                break;

            case 'in':
                $allowed = explode(',', (string) $parameter);
                if (!$isEmpty && !in_array((string) $value, $allowed, true)) {
                    $this->addError($field, 'El valor seleccionado no es válido.');
                }
                break;

            case 'numeric':
                if (!$isEmpty && !is_numeric($value)) {
                    $this->addError($field, 'Debe ser un valor numérico.');
                }
                break;

            case 'integer':
                if (!$isEmpty && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, 'Debe ser un número entero.');
                }
                break;

            // Distinto de "min"/"max" (que operan sobre longitud de texto): compara el
            // valor numérico en sí, útil para precio, stock, etc.
            case 'gte':
                if (!$isEmpty && is_numeric($value) && (float) $value < (float) $parameter) {
                    $this->addError($field, "Debe ser mayor o igual a {$parameter}.");
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}

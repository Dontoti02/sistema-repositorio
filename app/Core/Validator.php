<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validador centralizado de datos de entrada.
 * Lanza HttpException 422 con errores por campo.
 */
final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    /** @var array<string,mixed> */
    private array $data;

    /** @var array<string,string> */
    private array $labels;

    /**
     * @param array<string,mixed> $data
     * @param array<string,string> $labels Etiquetas legibles por campo.
     */
    public function __construct(array $data, array $labels = [])
    {
        $this->data = $data;
        $this->labels = $labels;
    }

    /**
     * @param array<string,string> $rules "campo" => "required|email|max:120"
     * @param array<string,string> $labels
     * @return array<string,mixed> datos saneados
     */
    public static function make(array $data, array $rules, array $labels = []): array
    {
        $v = new self($data, $labels);
        return $v->check($rules);
    }

    /**
     * @param array<string,string> $rules
     * @return array<string,mixed>
     */
    public function check(array $rules): array
    {
        $this->errors = [];
        $clean = [];
        foreach ($rules as $field => $ruleString) {
            $ruleList = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            $label = $this->labels[$field] ?? $field;

            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    $value = null;
                }
            }

            $required = in_array('required', $ruleList, true);

            if ($value === null || $value === '') {
                if ($required) {
                    $this->errors[$field] = "El campo «{$label}» es obligatorio.";
                } else {
                    $clean[$field] = $this->applyOptional($value, $ruleList);
                }
                continue;
            }

            $error = $this->applyRules($field, $label, $value, $ruleList);
            if ($error !== null) {
                $this->errors[$field] = $error;
            } else {
                $clean[$field] = $this->cast($value, $ruleList);
            }
        }
        if ($this->errors !== []) {
            throw new HttpException(422, 'Revise los campos marcados.', $this->errors);
        }
        return $clean;
    }

    private function applyOptional(mixed $value, array $ruleList): mixed
    {
        return $this->cast($value, $ruleList);
    }

    private function applyRules(string $field, string $label, mixed $value, array $ruleList): ?string
    {
        $str = is_scalar($value) ? (string) $value : '';
        foreach ($ruleList as $rule) {
            $params = [];
            if (str_contains($rule, ':')) {
                [$rule, $paramStr] = explode(':', $rule, 2);
                $params = explode(',', $paramStr);
            }
            switch ($rule) {
                case 'required':
                    break;
                case 'string':
                    if (!is_scalar($value)) {
                        return "«{$label}» debe ser texto.";
                    }
                    break;
                case 'email':
                    if (!filter_var($str, FILTER_VALIDATE_EMAIL)) {
                        return "«{$label}» debe ser un correo electrónico válido.";
                    }
                    if (mb_strlen($str) > 190) {
                        return "«{$label}» es demasiado largo.";
                    }
                    break;
                case 'min':
                    if (mb_strlen($str) < (int) $params[0]) {
                        return "«{$label}» debe tener al menos {$params[0]} caracteres.";
                    }
                    break;
                case 'max':
                    if (mb_strlen($str) > (int) $params[0]) {
                        return "«{$label}» no debe exceder {$params[0]} caracteres.";
                    }
                    break;
                case 'numeric':
                    if (!is_numeric($value)) {
                        return "«{$label}» debe ser numérico.";
                    }
                    break;
                case 'integer':
                    if (!preg_match('/^-?\d+$/', $str)) {
                        return "«{$label}» debe ser un número entero.";
                    }
                    break;
                case 'date':
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $str) || !strtotime($str)) {
                        return "«{$label}» debe ser una fecha válida (AAAA-MM-DD).";
                    }
                    break;
                case 'datetime':
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/', $str)) {
                        return "«{$label}» debe ser fecha y hora válidas.";
                    }
                    break;
                case 'url':
                    if (!filter_var($str, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $str)) {
                        return "«{$label}» debe ser una URL http(s) válida.";
                    }
                    break;
                case 'alpha_dash':
                    if (!preg_match('/^[A-Za-z0-9_\-\sáéíóúÁÉÍÓÚñÑüÜ\.]+$/', $str)) {
                        return "«{$label}» contiene caracteres no permitidos.";
                    }
                    break;
                case 'slug':
                    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $str)) {
                        return "«{$label}» debe ser un identificador web (minúsculas, números y guiones).";
                    }
                    break;
                case 'in':
                    if (!in_array($str, $params, true)) {
                        return "«{$label}» tiene un valor no permitido.";
                    }
                    break;
                case 'regex':
                    $pattern = '/' . str_replace('/', '\/', $params[0]) . '/';
                    if (!preg_match($pattern, $str)) {
                        return "«{$label}» no tiene el formato requerido.";
                    }
                    break;
                case 'confirmed':
                    $other = $this->data[$field . '_confirmation'] ?? null;
                    if ((string) $value !== (string) $other) {
                        return "«{$label}» y su confirmación no coinciden.";
                    }
                    break;
                case 'orcid':
                    if (!self::validOrcid($str)) {
                        return "«{$label}» no es un ORCID válido (verifique el dígito de control).";
                    }
                    break;
                case 'year':
                    if (!preg_match('/^\d{4}$/', $str) || (int) $str < 1500 || (int) $str > (int) gmdate('Y') + 1) {
                        return "«{$label}» debe ser un año válido.";
                    }
                    break;
            }
        }
        return null;
    }

    private function cast(mixed $value, array $ruleList): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        foreach ($ruleList as $rule) {
            if ($rule === 'integer') {
                return (int) $value;
            }
            if ($rule === 'numeric') {
                return is_numeric($value) ? +$value : $value;
            }
        }
        return $value;
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** Valida dígito de control ORCID (ISO 7064 MOD 11-2). */
    public static function validOrcid(string $orcid): bool
    {
        $orcid = strtolower(trim($orcid));
        if (!preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $orcid)) {
            return false;
        }
        $digits = str_replace('-', '', $orcid);
        $total = 0;
        for ($i = 0; $i < 15; $i++) {
            $digit = $digits[$i] === 'X' ? 10 : (int) $digits[$i];
            $total = ($total + $digit) * 2;
        }
        $remainder = $total % 11;
        $result = (12 - $remainder) % 11;
        $check = $result === 10 ? 'X' : (string) $result;
        return $check === $digits[15];
    }
}

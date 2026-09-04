<?php
/**
 * ============================================
 * VALIDATOR - Validación de Datos
 * ============================================
 * 
 * Clase para validación de datos de entrada.
 * Soporta reglas comunes de validación.
 */

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Validar campo requerido
     */
    public function required(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = "El campo {$label} es obligatorio";
        }
        
        return $this;
    }

    /**
     * Validar longitud mínima
     */
    public function minLength(string $field, int $min, string $label = ''): self
    {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        
        if (strlen($value) < $min) {
            $this->errors[$field] = "El campo {$label} debe tener al menos {$min} caracteres";
        }
        
        return $this;
    }

    /**
     * Validar longitud máxima
     */
    public function maxLength(string $field, int $max, string $label = ''): self
    {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        
        if (strlen($value) > $max) {
            $this->errors[$field] = "El campo {$label} no puede tener más de {$max} caracteres";
        }
        
        return $this;
    }

    /**
     * Validar email
     */
    public function email(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "El campo {$label} no es un email válido";
        }
        
        return $this;
    }

    /**
     * Validar número entero
     */
    public function integer(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field] = "El campo {$label} debe ser un número entero";
        }
        
        return $this;
    }

    /**
     * Validar número decimal
     */
    public function numeric(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field] = "El campo {$label} debe ser un número";
        }
        
        return $this;
    }

    /**
     * Validar valor en lista de opciones
     */
    public function in(string $field, array $options, string $label = ''): self
    {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !in_array($value, $options)) {
            $this->errors[$field] = "El campo {$label} debe ser uno de: " . implode(', ', $options);
        }
        
        return $this;
    }

    /**
     * Validar que el valor existe en una tabla
     */
    public function existsIn(string $field, Database $db, string $table, string $column, string $label = ''): self
    {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        
        if (!empty($value)) {
            $sql = "SELECT COUNT(*) AS total FROM {$table} WHERE {$column} = :value";
            $result = $db->query($sql, ['value' => $value]);
            
            if ((int)($result[0]['total'] ?? 0) === 0) {
                $this->errors[$field] = "El valor del campo {$label} no existe";
            }
        }
        
        return $this;
    }

    /**
     * Validar formato de fecha
     */
    public function date(string $field, string $format = 'Y-m-d', string $label = ''): self
    {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        
        if (!empty($value)) {
            $date = DateTime::createFromFormat($format, $value);
            if (!$date || $date->format($format) !== $value) {
                $this->errors[$field] = "El campo {$label} no tiene un formato de fecha válido";
            }
        }
        
        return $this;
    }

    /**
     * Validar que dos campos coincidan
     */
    public function matches(string $field, string $otherField, string $label = ''): self
    {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        $otherValue = $this->data[$otherField] ?? '';
        
        if ($value !== $otherValue) {
            $this->errors[$field] = "Los campos {$label} y {$otherField} no coinciden";
        }
        
        return $this;
    }

    /**
     * Agregar error personalizado
     */
    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    /**
     * Verificar si hay errores
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Verificar si es válido
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Obtener errores
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtener primer error
     */
    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Obtener errores como string
     */
    public function getErrorsAsString(string $separator = ', '): string
    {
        return implode($separator, $this->errors);
    }

    /**
     * Validar y lanzar excepción si falla
     */
    public function validate(): void
    {
        if ($this->fails()) {
            throw new InvalidArgumentException($this->getFirstError());
        }
    }

    /**
     * Obtener datos validados
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Obtener un valor del dato
     */
    public function getValue(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
}

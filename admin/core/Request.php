<?php
/**
 * ============================================
 * REQUEST - Manejo de Peticiones
 * ============================================
 * 
 * Clase para manejar datos de entrada (GET, POST, JSON).
 * Incluye validación y sanitización.
 */

class Request
{
    private array $data;

    public function __construct()
    {
        $this->data = array_merge($_GET, $_POST);
        
        // Soporte para JSON body
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $this->data = array_merge($this->data, $decoded);
            }
        }
    }

    /**
     * Obtener un parámetro
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Obtener todos los parámetros
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Verificar si existe un parámetro
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]) && $this->data[$key] !== '';
    }

    /**
     * Requerir parámetros obligatorios
     */
    public function required(string ...$keys): void
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                throw new InvalidArgumentException("Parámetro requerido: {$key}");
            }
        }
    }

    /**
     * Obtener parámetro como entero
     */
    public function int(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);
        return (int) $value;
    }

    /**
     * Obtener parámetro como float
     */
    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->get($key, $default);
        return (float) $value;
    }

    /**
     * Obtener parámetro como booleano
     */
    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);
        
        if (is_bool($value)) {
            return $value;
        }
        
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ?? $default;
    }

    /**
     * Obtener parámetro como string sanitizado
     */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);
        return $this->sanitize($value);
    }

    /**
     * Sanitizar valor
     */
    public function sanitize($value): string
    {
        if (!is_string($value)) {
            $value = (string) $value;
        }
        
        $value = trim($value);
        $value = stripslashes($value);
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        return $value;
    }

    /**
     * Obtener parámetro como array
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);
        
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value) && !empty($value)) {
            return array_map('trim', explode(',', $value));
        }
        
        return $default;
    }

    /**
     * Obtener método HTTP
     */
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Verificar si es petición POST
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Verificar si es petición GET
     */
    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    /**
     * Obtener IP del cliente
     */
    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '0.0.0.0';
    }

    /**
     * Obtener URL actual
     */
    public function url(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Obtener parámetros de query string
     */
    public function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Obtener header
     */
    public function header(string $key, $default = null)
    {
        $key = strtoupper(str_replace('-', '_', $key));
        return $_SERVER["HTTP_{$key}"] ?? $default;
    }
}

<?php
/**
 * ============================================
 * DOTENV - CARGADOR DE VARIABLES DE ENTORNO
 * ============================================
 * 
 * Parser ligero para archivos .env sin dependencias externas.
 * Lee el archivo .env desde la raíz del proyecto y carga
 * las variables como constantes PHP y en $_ENV.
 * 
 * Uso:
 *   Dotenv::load(__DIR__ . '/../../.env');
 *   
 *   // Opcional: obtener valor específico
 *   $dbPassword = Dotenv::get('DB_PASSWORD');
 *   $dbPassword = Dotenv::get('DB_PASSWORD', 'default_value');
 */

class Dotenv
{
    /** @var array Variables cargadas */
    private static array $variables = [];

    /**
     * Carga variables desde un archivo .env
     * 
     * @param string $path Ruta al archivo .env
     * @return void
     * @throws RuntimeException Si el archivo no existe o no es legible
     */
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            throw new RuntimeException(
                "Archivo .env no encontrado: {$path}"
            );
        }

        if (!is_readable($path)) {
            throw new RuntimeException(
                "Archivo .env no es legible: {$path}"
            );
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignorar comentarios
            $line = trim($line);
            if (strpos($line, '#') === 0) {
                continue;
            }

            // Buscar separador =
            $position = strpos($line, '=');
            if ($position === false) {
                continue;
            }

            $key = trim(substr($line, 0, $position));
            $value = trim(substr($line, $position + 1));

            // Validar nombre de variable (solo letras, números, guiones bajos)
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
                continue;
            }

            // Procesar valor
            $value = self::parseValue($value);

            // Guardar en variables internas
            self::$variables[$key] = $value;

            // Cargar como constante PHP si no existe
            if (!defined($key)) {
                define($key, $value);
            }

            // Cargar en $_ENV para compatibilidad
            $_ENV[$key] = $value;

            // Cargar en $_SERVER para compatibilidad
            $_SERVER[$key] = $value;
        }
    }

    /**
     * Procesa el valor de una variable .env
     * 
     * Soporta:
     *   - Valores entrecomillados (simples o dobles)
     *   - Variables de entorno anidadas (${VAR})
     *   - Valores por defecto (${VAR:-default})
     *   - Escape de caracteres especiales
     */
    private static function parseValue(string $value): string
    {
        // Valor vacío
        if ($value === '') {
            return '';
        }

        // Valor entrecomillado doble
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
            // Procesar escape de caracteres
            $value = str_replace(
                ['\\n', '\\t', '\\r', '\\"', '\\\\'],
                ["\n", "\t", "\r", '"', '\\'],
                $value
            );
        }
        // Valor entrecomillado simple (sin procesar escapes)
        elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
            $value = $matches[1];
        }
        // Valor sin comillas - puede contener referencias a otras variables
        else {
            // Procesar ${VAR} y ${VAR:-default}
            $value = preg_replace_callback(
                '/\$\{([a-zA-Z_][a-zA-Z0-9_]*)(?::-(.*))?\}/',
                function ($matches) {
                    $varName = $matches[1];
                    $defaultValue = $matches[2] ?? null;

                    if (isset(self::$variables[$varName])) {
                        return self::$variables[$varName];
                    }

                    if (defined($varName)) {
                        return constant($varName);
                    }

                    if ($defaultValue !== null) {
                        return $defaultValue;
                    }

                    return '';
                },
                $value
            );
        }

        return $value;
    }

    /**
     * Obtiene el valor de una variable
     * 
     * @param string $key Nombre de la variable
     * @param mixed $default Valor por defecto si no existe
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return self::$variables[$key] ?? $default;
    }

    /**
     * Obtiene todas las variables cargadas
     * 
     * @return array
     */
    public static function all(): array
    {
        return self::$variables;
    }

    /**
     * Verifica si una variable existe
     * 
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset(self::$variables[$key]);
    }

    /**
     * Obtiene un valor como entero
     * 
     * @param string $key
     * @param int $default
     * @return int
     */
    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key, $default);
        return (int) $value;
    }

    /**
     * Obtiene un valor como booleano
     * 
     * Acepta: true/false, 1/0, yes/no, on/off
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

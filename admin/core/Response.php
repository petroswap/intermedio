<?php
/**
 * ============================================
 * RESPONSE - Formato JSON Estándar
 * ============================================
 * 
 * Clase estática para enviar respuestas JSON
 * con formato consistente en toda la aplicación.
 */

class Response
{
    /**
     * Respuesta de éxito
     */
    public static function success($data = null, string $msg = "Operación exitosa", int $httpCode = 200): void
    {
        // Limpiar buffer de output para enviar headers correctamente
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $json = json_encode([
            'success' => true,
            'data'    => $data,
            'msg'     => $msg,
            'count'   => self::countData($data),
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        
        echo $json;
        
        exit;
    }

    /**
     * Respuesta de error
     */
    public static function error(string $msg = "Error desconocido", int $httpCode = 400): void
    {
        // Limpiar buffer de output para enviar headers correctamente
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode([
            'success' => false,
            'data'    => null,
            'msg'     => $msg,
            'count'   => 0,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        
        exit;
    }

    /**
     * Respuesta paginada
     */
    public static function paginated($data, int $total, int $page, int $perPage): void
    {
        // Limpiar buffer de output para enviar headers correctamente
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode([
            'success'    => true,
            'data'       => $data,
            'msg'        => 'Operación exitosa',
            'count'      => count($data),
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        
        exit;
    }

    /**
     * Respuesta con datos crudos (sin procesar)
     */
    public static function raw(array $data): void
    {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        
        exit;
    }

    /**
     * Contar elementos de datos
     */
    private static function countData($data): int
    {
        if (is_array($data)) {
            return count($data);
        }
        return $data !== null ? 1 : 0;
    }

    /**
     * Respuesta de éxito con código 201 (Created)
     */
    public static function created($data = null, string $msg = "Recurso creado"): void
    {
        self::success($data, $msg, 201);
    }

    /**
     * Respuesta de éxito con código 204 (No Content)
     */
    public static function noContent(string $msg = "Eliminado correctamente"): void
    {
        http_response_code(204);
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode([
            'success' => true,
            'data'    => null,
            'msg'     => $msg,
            'count'   => 0,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        
        exit;
    }

    /**
     * Respuesta de no encontrado (404)
     */
    public static function notFound(string $msg = "Recurso no encontrado"): void
    {
        self::error($msg, 404);
    }

    /**
     * Respuesta de no autorizado (401)
     */
    public static function unauthorized(string $msg = "No autorizado"): void
    {
        self::error($msg, 401);
    }

    /**
     * Respuesta de prohibido (403)
     */
    public static function forbidden(string $msg = "Acceso denegado"): void
    {
        self::error($msg, 403);
    }

    /**
     * Respuesta de error interno (500)
     */
    public static function serverError(string $msg = "Error interno del servidor"): void
    {
        self::error($msg, 500);
    }
}

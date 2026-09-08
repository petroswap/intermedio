<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/clases/Litros.php';
require_once __DIR__ . '/clases/Facturas.php';
require_once __DIR__ . '/clases/Tarifas.php';

// Cargar .env específico de la API (sobreescribe variables si existen)
$apiEnvPath = __DIR__ . '/.env';
if (file_exists($apiEnvPath)) {
    Dotenv::load($apiEnvPath);
}

// ============================================
// MIDDLEWARE AUTH
// ============================================
function validateAuth() {
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    $validKey = Dotenv::get('API_KEY', '');
    if (empty($apiKey) || $apiKey !== $validKey) {
        http_response_code(401);
        echo json_encode(['success' => false, 'msg' => 'No autorizado']);
        exit;
    }
    return true;
}
validateAuth();

function getDb(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $db = Database::getInstance(DB_CONFIG);
        $pdo = $db->getConnection();
    }
    return $pdo;
}

function env(string $key, $default = null) {
    return Dotenv::get($key, $default);
}

function envInt(string $key, int $default = 0): int {
    return (int) Dotenv::get($key, $default);
}

function envArray(string $key, array $default = []): array {
    $val = Dotenv::get($key, '');
    if (empty($val)) return $default;
    return array_map('intval', array_map('trim', explode(',', $val)));
}

function toArray($val): array {
    if (is_array($val)) return $val;
    if (empty($val)) return [];
    return array_map('trim', explode(',', (string) $val));
}

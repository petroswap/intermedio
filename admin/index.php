<?php
/**
 * ============================================
 * FuelOps - Panel de Gestión de Combustibles
 * ============================================
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/core/Request.php';
require_once __DIR__ . '/core/CRUD.php';
require_once __DIR__ . '/core/Validator.php';

// Available modules
$allModules = [
    'dashboard' => [
        'name' => 'Dashboard',
        'description' => 'Resumen general de la base de datos',
        'icon' => '📊',
    ],
    'inspector' => [
        'name' => 'Explorador',
        'description' => 'Navega tablas, datos y esquemas de la base de datos',
        'icon' => '🗃️',
    ],
    'sql' => [
        'name' => 'Consola SQL',
        'description' => 'Ejecuta consultas SQL personalizadas',
        'icon' => '💻',
    ],
    'builder' => [
        'name' => 'Builder',
        'description' => 'Construye consultas SQL de forma visual',
        'icon' => '🔧',
    ],
    'scripts' => [
        'name' => 'Scripts',
        'description' => 'Ejecuta scripts de administración (requiere autenticación)',
        'icon' => '📜',
    ],
];

// Detect requested module
$module = $_GET['module'] ?? 'dashboard';

if (!isset($allModules[$module])) {
    $module = 'dashboard';
}

$modules = $allModules;
$currentModule = $allModules[$module];

// Load layout
include __DIR__ . '/templates/layout.php';

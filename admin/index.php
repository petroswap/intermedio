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
];

// Detect requested module
$module = $_GET['module'] ?? 'inspector';

if (!isset($allModules[$module])) {
    $module = 'inspector';
}

$modules = $allModules;
$currentModule = $allModules[$module];

// Load layout
include __DIR__ . '/templates/layout.php';

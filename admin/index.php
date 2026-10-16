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
    // Módulo original (oculto del nav, pero activable con ?module=inspector)
    'inspector' => [
        'name' => 'Explorador (old)',
        'description' => 'Navega tablas, datos y esquemas de la base de datos',
        'icon' => '🗃️',
        'hidden' => true,
    ],
    'explorer' => [
        'name' => 'Explorador',
        'description' => 'Listado de tablas y contenido de cada tabla en una sola vista',
        'icon' => '🗂️',
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
    'procedures' => [
        'name' => 'Procedures',
        'description' => 'Explora y ejecuta stored procedures de la base de datos',
        'icon' => '⚙️',
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

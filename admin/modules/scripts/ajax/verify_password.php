<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        Response::error('Contraseña requerida');
    }
    
    $storedPassword = defined('SCRIPTS_PASSWORD') ? SCRIPTS_PASSWORD : '';
    
    if (empty($storedPassword)) {
        Response::error('Contraseña no configurada en el servidor');
    }
    
    if ($password === $storedPassword) {
        Response::success(['authenticated' => true], 'Acceso concedido');
    } else {
        Response::error('Contraseña incorrecta');
    }
} catch (Exception $e) {
    Response::error('Error de verificación: ' . $e->getMessage());
}

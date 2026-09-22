<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        Response::error('Contraseña requerida');
    }
    
    $storedPassword = SCRIPTS_PASSWORD;
    
    if (empty($storedPassword)) {
        Response::error('SCRIPTS_PASSWORD no configurado en .env');
    }
    
    if ($password === $storedPassword) {
        $_SESSION['scripts_auth'] = true;
        $_SESSION['scripts_auth_time'] = time();
        Response::success(['authenticated' => true], 'Acceso concedido');
    } else {
        Response::error('Contraseña incorrecta');
    }
} catch (Exception $e) {
    Response::error('Error: ' . $e->getMessage());
}

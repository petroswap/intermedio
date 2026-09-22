<?php
/**
 * Script Guard - Verifica que el script se ejecuta a través de run_script.php
 * 
 * Uso al inicio de cada script:
 *   require_once __DIR__ . '/script_guard.php';
 */

if (!session_id()) {
    session_start();
}

if (empty($_SESSION['scripts_auth']) || empty($_SESSION['scripts_running'])) {
    http_response_code(403);
    die('Acceso no permitido. Este script solo se puede ejecutar a través del panel de Scripts.');
}

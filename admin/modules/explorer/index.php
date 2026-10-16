<?php
/**
 * ============================================
 * Explorer (Unificado) Module - Entry Point
 * ============================================
 * Vista maestro-detallado: listado de tablas + contenido,
 * columnas, filtros y SQL de la tabla seleccionada en una sola pantalla.
 * Reutiliza los endpoints AJAX del módulo inspector.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Request.php';
require_once __DIR__ . '/../../core/CRUD.php';

// Cargar vista principal
include __DIR__ . '/views/explorer.php';

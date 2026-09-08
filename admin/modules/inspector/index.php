<?php
/**
 * ============================================
 * Inspector Module - Entry Point
 * ============================================
 * Navega tablas, datos y esquemas de la BD
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Request.php';
require_once __DIR__ . '/../../core/CRUD.php';

// Cargar vista principal
include __DIR__ . '/views/inspector.php';

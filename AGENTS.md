# AGENTS.md - Contexto del Proyecto

## Descripción General

**FuelOps** (Mini Admin v2) — Panel de administración web para gestionar bases de datos Firebird. Incluye inspector de tablas, consola SQL y visualización de metadatos.

## Arquitectura

```
intermedio/
├── admin/                    # Aplicación principal
│   ├── assets/
│   │   ├── css/admin.css     # Estilos globales (tema claro/oscuro)
│   │   └── js/
│   │       ├── admin.js      # Funciones globales (alertas, loading, temas)
│   │       ├── inspector.js  # Lógica del módulo Inspector
│   │       └── sql.js        # Lógica del módulo Consola SQL
│   ├── config/               # Configuración de endpoints (endpoints_registry.php)
│   ├── core/
│   │   ├── CRUD.php          # Operaciones genéricas sobre tablas
│   │   ├── Database.php      # Conexión PDO (Singleton, Firebird/MySQL)
│   │   ├── Dotenv.php        # Parser de variables de entorno
│   │   └── Response.php      # Respuestas JSON estandarizadas
│   ├── modules/
│   │   ├── inspector/        # Módulo Inspector (tablas, columnas, datos)
│   │   │   ├── ajax/         # Endpoints AJAX
│   │   │   │   ├── test_connection.php  # Test de conexión a BD
│   │   │   │   └── ...
│   │   │   └── views/        # Vistas HTML
│   │   └── sql/              # Módulo Consola SQL
│   │       └── views/        # sql.php (editor + ejemplos)
│   ├── scripts/              # Scripts de testing
│   ├── templates/layout.php  # Layout principal (nav, head, scripts)
│   ├── config.php            # Carga .env y defines globales
│   └── index.php             # Router principal de módulos
├── .env                      # Variables de entorno (NO subir al repo)
└── .gitignore
```

## Stack Tecnológico

- **Backend:** PHP 7.4+, PDO con Firebird (principal) y MySQL (secundario)
- **Frontend:** HTML, CSS (tema claro/oscuro), jQuery
- **BD:** Firebird 5.x (producción), Firebird local (desarrollo)
- **Servidor:** XAMPP (desarrollo), IIS/Apache (producción)

## Módulos Activos

| Módulo | Descripción | Archivos |
|--------|-------------|----------|
| **Inspector** | Explorar tablas, columnas, datos, metadatos | `inspector/` |
| **BD Info** | Info general de la base de datos | Integrado en inspector |
| **Test Conexión** | Verificar conexión a Firebird/MySQL, latencia, versión | `inspector/ajax/test_connection.php` |
| **Consola SQL** | Editor SQL con ejemplos (solo SELECT) | `sql/` |

## Configuración

### Variables de entorno (.env)

```ini
# Base de datos
DB_DRIVER=firebird
DB_SERVER=localhost          # 192.168.4.101 en producción
DB_PATH=C:\Firebird\BO5.FDB # Ruta al .FDB
DB_PORT=3050
DB_USER=SYSDBA
DB_PASSWORD=xxxxx
DB_CHARSET=UTF8

# App
APP_ENV=local
APP_DEBUG=true
APP_BASE_URL=/proyectos/intermedio/admin/
```

### Detección de entorno por IP

El sistema detecta automáticamente el entorno según la IP del cliente:

| IP | Entorno |
|----|---------|
| 127.0.0.1 / ::1 | `local` |
| 192.168.4.201 | `local_network` |
| 81.42.223.28 | `fueltruck_oficina` |
| 37.143.127.188 | `igara_oficina` |
| 37.143.127.164 | `ruta` |

### Firebird PDO DSN (IMPORTANTE)

El driver PDO de Firebird **no soporta** `host=` ni `service=` en el DSN. Usar siempre:

```php
// Local (localhost/127.0.0.1/::1)
$dsn = 'firebird:dbname=C:\path\to\db.fdb;charset=UTF8';

// Remoto
$dsn = 'firebird:dbname=192.168.4.101/3050:C:\path\to\db.fdb;charset=UTF8';
```

**No usar:** `firebird:host=X;service=Y;dbname=Z` → Firebird lo interpreta mal y falla con `host "C"`.

## Convenciones de Código

### PHP
- Patrón Singleton para Database
- Respuestas JSON con `Response::success()` / `Response::error()`
- Queries parameterizadas (prepare + execute)
- Firebird: `FIRST N SKIP N` para paginación, `ROWS m TO n`

### JavaScript
- Namespace `SQL.execute()`, `SQL.renderResults()`, etc.
- Admin.showLoading() / Admin.showAlert() para UI
- AJAX con jQuery

### Seguridad
- Consola SQL: Solo permitidas consultas SELECT (validado server + client)
- .env nunca se sube al repo
- Comentarios SQL eliminados antes de validar queries

## Comandos Útiles

```bash
# Ver estado
git status

# Buscar en el código
grep -r "función" admin/

# Probar conexión Firebird (desde el admin UI)
# Botón "Test Conexión" en el módulo Inspector
```

## Archivos Importantes

- `admin/config.php` — Carga de entorno y configuración
- `admin/core/Database.php` — Conexión y queries (ver nota Firebird DSN más arriba)
- `admin/modules/inspector/ajax/test_connection.php` — Test de conexión a BD
- `admin/modules/inspector/ajax/ejecutar_sql.php` — Endpoint SQL (con validación SELECT)
- `admin/assets/js/sql.js` — Frontend consola SQL
- `.env` — Credenciales (no versionado)

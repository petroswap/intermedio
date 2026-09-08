# AGENTS.md - Contexto del Proyecto

## Descripción General

**FuelOps** (Mini Admin v2) — Panel de administración web para gestionar bases de datos Firebird. Incluye inspector de tablas, consola SQL y visualización de metadatos.

## Arquitectura

```
intermedio/
├── admin/                    # Aplicación principal
│   ├── assets/
│   │   ├── css/admin.css     # Estilos globales (tema claro/oscuro)
│   │   ├── js/
│   │   │   ├── admin.js      # Funciones globales (alertas, loading, temas, helpers)
│   │   │   ├── inspector.js  # Lógica del módulo Inspector
│   │   │   └── sql.js        # Lógica del módulo Consola SQL
│   │   └── lib/              # Librerías externas
│   │       ├── codemirror.min.js          # CodeMirror 5.65.16
│   │       ├── codemirror.min.css         # CodeMirror base CSS
│   │       ├── codemirror-sql.min.js      # CodeMirror SQL mode
│   │       └── codemirror-theme-monokai.min.css  # Custom monokai theme
│   ├── core/
│   │   ├── CRUD.php          # Operaciones genéricas sobre tablas
│   │   ├── Database.php      # Conexión PDO (Singleton, Firebird/MySQL)
│   │   ├── Dotenv.php        # Parser de variables de entorno
│   │   └── Response.php      # Respuestas JSON estandarizadas
│   ├── modules/
│   │   ├── inspector/        # Módulo Inspector (tablas, columnas, datos)
│   │   │   ├── ajax/         # Endpoints AJAX
│   │   │   │   ├── test_connection.php    # Test de conexión a BD
│   │   │   │   ├── listar_tablas.php      # Lista tablas
│   │   │   │   ├── listar_columnas.php    # Columnas + IS_PK
│   │   │   │   ├── obtener_datos.php      # Datos paginados + filtros + BETWEEN
│   │   │   │   ├── obtener_ultimos.php    # Últimos N registros
│   │   │   │   ├── ejecutar_sql.php       # SQL libre (SELECT/WITH)
│   │   │   │   └── contar_tablas.php      # Conteo batch de registros
│   │   │   └── views/
│   │   │       └── inspector.php          # Vista principal
│   │   └── sql/              # Módulo Consola SQL
│   │       └── views/
│   │           └── sql.php                # Editor + ejemplos + historial + favoritos
│   ├── templates/layout.php  # Layout principal (nav, head, scripts, CodeMirror)
│   ├── config.php            # Carga .env y defines globales
│   └── index.php             # Router principal de módulos
├── .env                      # Variables de entorno (NO subir al repo)
└── .gitignore
```

## Stack Tecnológico

- **Backend:** PHP 7.4+, PDO con Firebird (principal) y MySQL (secundario)
- **Frontend:** HTML, CSS (tema claro/oscuro), jQuery, CodeMirror 5.65.16
- **BD:** Firebird 5.x (producción), Firebird local (desarrollo)
- **Servidor:** XAMPP (desarrollo), IIS/Apache (producción)
- **Exportación:** CSV (sin dependencias PHP)

## Módulos Activos

| Módulo | Descripción | Archivos |
|--------|-------------|----------|
| **Inspector** | Explorar tablas, columnas, datos, metadatos | `inspector/` |
| **BD Info** | Info general de la base de datos | Integrado en inspector |
| **Test Conexión** | Verificar conexión a Firebird/MySQL, latencia, versión | `inspector/ajax/test_connection.php` |
| **Consola SQL** | Editor SQL con CodeMirror, ejemplos, historial, favoritos | `sql/` |

## Funcionalidades Implementadas

### Inspector — Carga de Datos

- **Últimos 10**: `loadLastRecords(10)` — carga rápida con `obtener_ultimos.php`
- **Ver todos**: `loadAllRecords()` — carga masiva con `obtener_datos.php` (confirma si >1000 registros)
- **Filtros**: operadores =, !=, >, <, >=, <=, LIKE, BETWEEN, IN; envío como array `filters[0][field]`, `filters[0][operator]`, `filters[0][value]`
- **Columnas seleccionables**: chips de columnas con metadata (tipo + PK 🔑); `IS_PK` viene de `listar_columnas.php` vía `getPrimaryKey()`

### SQL Generado (`#sql-generated`)

- Se actualiza solo al cargar datos (`updateSql()`) o ejecutar SQL desde modal
- **Botón Limpiar**: aparece cuando el SQL difiere del inicial; restaura estado inicial + limpia filtros + recarga datos
- No se actualiza al escribir en el modal ni al hacer clic en sugerencias (solo al ejecutar)

### Modal "Ejecutar SQL"

- Editor CodeMirror con tema monokai, modo SQL
- **Sugerencias** organizadas en 3 grupos:
  - **Básico**: Todos los campos, Primeros 10, Ordenar, Contar
  - **Filtros**: Igual a valor, LIKE, BETWEEN, IN (usa `{COL}` → primera columna de la tabla)
  - **Avanzado**: DISTINCT, GROUP BY, No nulos, ROWS 1 a 100
- Solo ejecuta SELECT/WITH (validado server + client)
- `Ctrl+Enter` ejecuta, `Escape` cierra

### Consola SQL (módulo `sql/`)

- Editor CodeMirror 5.65.16 (tema monokai, `text/x-sql`)
- **Ejemplos SQL Firebird**: acordeón cerrado por defecto, 14 grupos (Básico, Filtros, Orden/Límite, Cadenas, Numéricas, Fechas, CASE/IIF, GROUP BY, JOINs, Subconsultas, UNION, Ventanas, CTEs, Metadatos)
- **Historial**: localStorage, 20 entradas, botón Limpiar
- **Favoritos**: localStorage, 20 entradas, botón Limpiar
- **Atajos**: `Ctrl+Enter` ejecutar, `Ctrl+L` limpiar
- **Exportación**: CSV y JSON con BOM para compatibilidad Excel

### Exportación de Datos

- Botones CSV/JSON en resultados del Inspector y del módulo SQL
- Usa `Blob` + `URL.createObjectURL` (descarga directa desde browser)
- CSV incluye BOM (`\uFEFF`) para que Excel abra con UTF-8

### Conteo de Registros

- Endpoint `contar_tablas.php`: conteo batch de tablas (max 100)
- Cache en localStorage con TTL de 5 minutos
- Badge de conteo al lado del nombre de cada tabla (se carga async)

### CodeMirror

- Versión 5.65.16 (CDN descargado a `assets/lib/`)
- Integrado en: modal SQL del Inspector + editor del módulo SQL
- `initCodeMirror()` en sql.js: lazy-init al abrir módulo
- Inspector: crea/destruye por modal (al abrir, al cerrar destruye)
- CSS custom `codemirror-theme-monokai.min.css` + overrides en admin.css

### Navegación Responsive

- **Hamburger menu** (`#nav-toggle`): visible en `< 480px`, alterna `.nav-tabs.open`
- **SQL module**: `@media (max-width: 768px)` apila editor/resultados en columna única

### Manejo de Errores

- `Admin.logError(context, error, extra)`: log a consola + localStorage (max 50 entradas)
- Todos los `.catch()` en inspector.js usan `Admin.logError()` en vez de `console.error`

### Helpers Globales (`admin.js`)

- `Admin.formatNumber(num, decimals)`: formateo con `Intl.NumberFormat('es-ES')`
- `Admin.formatDate(dateString)` / `Admin.formatDateTime(dateString)`
- `Admin.copyToClipboard(text)`: clipboard API con fallback
- `Admin.logError(context, error, extra)`: logging centralizado

### Validación Server-Side

- `obtener_ultimos.php`: limit capped a max 1000
- `contar_tablas.php`: max 100 tablas por request
- `obtener_datos.php`: per_page capped a 500
- `ejecutar_sql.php`: solo SELECT/WITH, comentarios eliminados antes de validar
- Todos los nombres de tablas/columnas validados con regex `^[A-Za-z0-9_]+$`

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

### Firebird PDO DSN (IMPORTANTE)

```php
// Local
$dsn = 'firebird:dbname=C:\path\to\db.fdb;charset=UTF8';

// Remoto
$dsn = 'firebird:dbname=192.168.4.101/3050:C:\path\to\db.fdb;charset=UTF8';
```

**No usar:** `firebird:host=X;service=Y;dbname=Z`

## Convenciones de Código

### PHP
- Patrón Singleton para Database
- Respuestas JSON con `Response::success()` / `Response::error()`
- Queries parameterizadas (prepare + execute)
- Firebird: `FIRST N SKIP N` para paginación, `ROWS m TO n`

### JavaScript
- `Admin.post()`, `Admin.showLoading()`, `Admin.toastSuccess()`, `Admin.logError()`
- Inspector: namespace `Inspector.init()`, `Inspector.updateSql()`, `Inspector.loadLastRecords()`
- SQL module: namespace `SQL.init()`, `SQL.execute()`, `SQL.renderResults()`
- CodeMirror: `Inspector._cmEditor`, `SQL._cmEditor`
- Filtros: array anidado `filters[0][field]`, `filters[0][operator]`, `filters[0][value]`

### Seguridad
- Consola SQL: Solo permitidas consultas SELECT/WITH (validado server + client)
- .env nunca se sube al repo
- Comentarios SQL eliminados antes de validar queries
- Nombres de tablas/columnas: regex `^[A-Za-z0-9_]+$`

## Archivos Importantes

- `admin/config.php` — Carga de entorno y configuración
- `admin/core/Database.php` — Conexión y queries
- `admin/assets/js/admin.js` — Helpers globales (`formatNumber`, `logError`, `copyToClipboard`)
- `admin/assets/js/inspector.js` — Lógica completa del Inspector
- `admin/assets/js/sql.js` — Lógica del módulo SQL
- `admin/assets/css/admin.css` — Todos los estilos (tema, responsive, CodeMirror, skeleton)
- `admin/modules/inspector/ajax/ejecutar_sql.php` — SQL libre con validación
- `admin/modules/inspector/ajax/contar_tablas.php` — Conteo batch
- `admin/modules/inspector/views/inspector.php` — Vista del Inspector
- `admin/modules/sql/views/sql.php` — Vista del módulo SQL
- `admin/templates/layout.php` — Layout con CodeMirror CSS/JS
- `.env` — Credenciales (no versionado)

## Cambios Recientes (P1-P3)

### P1 — Funcionalidad Core (completado)
- CSV/JSON export en Inspector y SQL module
- Conteo de registros con cache localStorage (5min TTL)
- Columnas con metadata (tipo + PK 🔑)
- Operador BETWEEN en filtros
- CodeMirror 5.65.16 integrado

### P2 — UX (completado)
- Confirmación antes de cargar muchos registros (>1000)
- Query bookmarks/favoritos en SQL module
- Botón limpiar historial SQL
- Responsive SQL module (<768px apilar)
- Hamburger menu móvil (<480px)
- Loading skeleton (ya existía)

### P3 — Calidad (completado)
- Helpers reutilizables: `Admin.formatNumber()`, `Admin.logError()`
- Validación server-side: limit capped, max tablas
- Error handling: `Admin.logError()` con localStorage

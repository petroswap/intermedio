<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= APP_NAME ?> - Gestión de datos y endpoints">
    <title><?= APP_NAME ?> - <?= $currentModule['name'] ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/lib/jquery.dataTables.min.css">
    <link rel="stylesheet" href="assets/lib/select2.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⛽</text></svg>">
    
    <!-- Theme init (before body to prevent FOUC) -->
    <script>
        (function() {
            var saved = localStorage.getItem('admin_theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
</head>
<body>
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>

    <header class="header" role="banner">
        <div class="header-left">
            <span class="header-logo" aria-hidden="true">⛽</span>
            <h1 class="header-title"><?= APP_NAME ?></h1>
        </div>
        <div class="header-right">
            <button id="theme-toggle" class="theme-toggle" title="Cambiar tema" aria-label="Cambiar tema claro/oscuro">
                <span class="theme-icon-light">☀️</span>
                <span class="theme-icon-dark">🌙</span>
            </button>
            <span class="header-version">v<?= APP_VERSION ?></span>
            <span class="header-env <?= APP_ENV === 'local' ? 'env-local' : 'env-production' ?>" 
                  role="status"
                  aria-label="Entorno: <?= ucfirst(APP_ENV) ?>">
                <?= ucfirst(APP_ENV) ?>
            </span>
        </div>
    </header>

    <nav class="nav" role="navigation" aria-label="Navegación principal">
        <ul class="nav-tabs" role="tablist">
            <?php foreach ($modules as $key => $mod): ?>
            <li class="nav-tab <?= $module === $key ? 'active' : '' ?>" role="presentation">
                <a href="?module=<?= $key ?>" 
                   role="tab"
                   aria-selected="<?= $module === $key ? 'true' : 'false' ?>"
                   aria-current="<?= $module === $key ? 'page' : 'false' ?>">
                    <span class="nav-icon" aria-hidden="true"><?= $mod['icon'] ?></span>
                    <?= $mod['name'] ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <main class="content" id="main-content" role="main">
        <div class="content-header">
            <h2 class="content-title">
                <span class="content-icon" aria-hidden="true"><?= $currentModule['icon'] ?></span>
                <?= $currentModule['name'] ?>
            </h2>
            <p class="content-description"><?= $currentModule['description'] ?></p>
        </div>

        <div class="content-body">
            <div class="loading" aria-label="Cargando">
                <div class="spinner" aria-hidden="true"></div>
                <p>Cargando módulo...</p>
            </div>
        </div>
    </main>

    <footer class="footer" role="contentinfo">
        <p><?= APP_NAME ?> v<?= APP_VERSION ?> · <?= APP_ENV === 'local' ? 'Desarrollo' : 'Producción' ?></p>
    </footer>

    <script src="assets/lib/jquery.min.js"></script>
    <script src="assets/lib/jquery.dataTables.min.js"></script>
    <script src="assets/lib/select2.min.js"></script>
    <script src="assets/js/admin.js?v=3.0.0"></script>
    <?php if ($module === 'inspector'): ?>
    <script src="assets/js/inspector.js?v=3.0.0"></script>
    <?php endif; ?>
    <?php if ($module === 'sql'): ?>
    <script src="assets/js/sql.js?v=3.0.0"></script>
    <?php endif; ?>
    <script>
        $(document).on('click', '#theme-toggle', function() {
            var current = document.documentElement.getAttribute('data-theme');
            var next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('admin_theme', next);
        });

        $(document).ready(function() {
            Admin.loadModule('<?= $module ?>');
        });
    </script>
</body>
</html>

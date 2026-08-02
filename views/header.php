<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Air Monitor') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('img/logo.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dark-mode.css') ?>">
    <script>
        const APP_BASE_URL = '<?= BASE_URL ?>';
        const REFRESH_INTERVAL = <?= REFRESH_INTERVAL ?>;
    </script>
</head>
<body>
    <div id="app">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="<?= asset('img/logo.svg') ?>" alt="Air Monitor" class="sidebar-logo">
                <div class="sidebar-title-group">
                    <h1 class="sidebar-title">Air Monitor</h1>
                    <span class="sidebar-subtitle">Calidad del Aire</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="<?= BASE_URL ?>/dashboard" class="nav-item active">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-item" onclick="event.preventDefault(); toggleDarkMode()">
                    <i class="fas fa-moon" id="darkModeIcon"></i>
                    <span>Modo Oscuro</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="status-indicator" id="connectionStatus">
                    <span class="status-dot connected"></span>
                    <span class="status-text">Conectado</span>
                </div>
                <p class="sidebar-version">v1.0.0</p>
            </div>
        </aside>

        <main class="main-content">
            <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>

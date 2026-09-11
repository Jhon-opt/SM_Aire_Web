<?php
// Sitio al que pertenece la página: 'liasp' (portal) o 'simca' (monitoreo).
$nav   = $nav ?? 'dashboard';
$sitio = $sitio ?? ($nav === 'liasp' ? 'liasp' : 'simca');
$info  = sitioInfo($sitio);
$menu  = menuSitio($sitio);
$activo = $navActivo ?? ($nav === 'dashboard' ? 'consolidado' : $nav);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#15803D">
    <title><?= htmlspecialchars($titulo ?? $info['sigla']) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('img/logo.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/docs.css') ?>">
    <script>
        const APP_BASE_URL = '<?= BASE_URL ?>';
        const LIVE_POLL_SECONDS = <?= (int) LIVE_POLL_SECONDS ?>;
        const APP_TIMEZONE = '<?= APP_TIMEZONE ?>';
        // Categorías y límites del ICA (misma tabla que includes/functions.php)
        const ICA = <?= json_encode([
            'categorias' => icaCategorias(),
            'limites'    => array_combine(parametrosTodos(), array_map('icaLimites', parametrosTodos())),
        ], JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body>
    <div id="app">
        <!-- Cabecera: título del sitio + logo Universidad Distrital + menú -->
        <header class="site-header">
            <div class="site-header-top">
                <a href="<?= $info['inicio'] ?>" class="brand">
                    <span class="brand-badge"><?= htmlspecialchars($info['sigla']) ?></span>
                    <span class="brand-text">
                        <span class="brand-name"><?= htmlspecialchars($info['nombre']) ?></span>
                        <span class="brand-sub">Universidad Distrital Francisco José de Caldas</span>
                    </span>
                </a>
                <div class="site-header-right">
                    <?php if ($sitio === 'simca'): ?>
                        <span class="nav-status" id="connectionStatus" title="Conexión con el servidor de datos">
                            <span class="status-dot connected"></span>
                            <span class="status-text">Conectado</span>
                        </span>
                    <?php endif; ?>
                    <a href="https://www.udistrital.edu.co" target="_blank" rel="noopener" class="brand-logo" title="Universidad Distrital Francisco José de Caldas">
                        <img src="<?= asset('img/logo-ud.png') ?>" alt="Universidad Distrital Francisco José de Caldas">
                    </a>
                    <button class="nav-toggle" id="navToggle" aria-label="Menú" aria-expanded="false" onclick="toggleNav()">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>

            <nav class="site-nav" aria-label="Menú principal">
                <div class="site-nav-inner" id="siteNav">
                    <?php foreach ($menu as $item): ?>
                        <?php
                            $tipo = $item['tipo'] ?? 'link';
                            $clases = 'nav-link' . (($item['clave'] === $activo) ? ' active' : '') . (!empty($item['solo_icono']) ? ' nav-icon-only' : '');
                            $etiqueta = '<i class="fas ' . $item['icono'] . '"></i>'
                                . (!empty($item['solo_icono']) ? '<span class="sr-only">' : '<span>')
                                . htmlspecialchars($item['texto']) . '</span>';
                        ?>
                        <?php if (!empty($item['derecha'])): ?>
                            <span class="nav-spacer"></span>
                        <?php endif; ?>

                        <?php if ($tipo === 'dropdown'): ?>
                            <div class="nav-dropdown" id="nav-<?= $item['clave'] ?>">
                                <a href="<?= $item['href'] ?>" class="<?= $clases ?>" aria-haspopup="true" onclick="return toggleDropdown(event, 'nav-<?= $item['clave'] ?>')">
                                    <?= $etiqueta ?> <i class="fas fa-chevron-down nav-caret"></i>
                                </a>
                                <div class="nav-dropdown-menu">
                                    <?php foreach ($item['hijos'] as $hijo): ?>
                                        <a href="<?= $hijo['href'] ?>">
                                            <span class="nav-dd-title"><i class="fas <?= $hijo['icono'] ?>"></i> <?= htmlspecialchars($hijo['texto']) ?></span>
                                            <?php if (!empty($hijo['detalle'])): ?>
                                                <small><?= htmlspecialchars($hijo['detalle']) ?></small>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php elseif ($tipo === 'accion'): ?>
                            <button type="button" class="<?= $clases ?>" onclick="<?= $item['onclick'] ?>"><?= $etiqueta ?></button>
                        <?php elseif ($tipo === 'pronto'): ?>
                            <span class="<?= $clases ?> nav-disabled" title="Próximamente"><?= $etiqueta ?> <span class="nav-badge-soon">Pronto</span></span>
                        <?php else: ?>
                            <a href="<?= $item['href'] ?>" class="<?= $clases ?>" <?= !empty($item['titulo']) ? 'title="' . htmlspecialchars($item['titulo']) . '"' : '' ?>><?= $etiqueta ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </nav>
        </header>

        <main class="main-content">

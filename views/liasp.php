            <div class="container portal">

                <!-- Banner principal ("IMAGEN" en la estructura) con acceso a SIMCA -->
                <section class="portal-hero <?= $portada ? 'has-photo' : '' ?>" id="inicio"
                         <?= $portada ? 'style="background-image: url(\'' . $portada . '\')"' : '' ?>>
                    <div class="portal-hero-content">
                        <span class="hero-kicker">Universidad Distrital Francisco José de Caldas</span>
                        <h1 class="portal-hero-title">Laboratorio Urbano de Inteligencia Ambiental y Salud Pública</h1>
                        <p class="portal-hero-lema"><?= htmlspecialchars($contenido['lema']) ?></p>
                        <div class="portal-hero-actions">
                            <a href="#informacion" class="btn btn-light"><i class="fas fa-circle-info"></i> Conoce el laboratorio</a>
                            <a href="<?= BASE_URL ?>/dashboard" class="btn btn-white"><i class="fas fa-wind"></i> Ir a SIMCA</a>
                        </div>
                    </div>

                    <!-- Tarjeta SIMCA: conecta el portal con el sistema de monitoreo -->
                    <a href="<?= BASE_URL ?>/dashboard" class="simca-card" id="simcaCard">
                        <div class="simca-card-head">
                            <span class="simca-badge">SIMCA</span>
                            <span class="simca-live"><span class="live-dot"></span> Monitoreo en tiempo real</span>
                        </div>
                        <h3>Sistema Inteligente de Monitoreo de Calidad del Aire</h3>
                        <p>Sensores de bajo costo instalados en colegios: PM2.5, PM10 y CO con panel en vivo, estadísticas y exportación.</p>

                        <div class="simca-estado" id="simcaEstado">
                            <span class="simca-estado-icon"><i class="fas fa-satellite-dish"></i></span>
                            <span class="simca-estado-text">
                                <strong>Consultando el estado del aire…</strong>
                                <small>Última lectura de la red de sensores</small>
                            </span>
                        </div>

                        <?php if ($stats): ?>
                            <div class="simca-stats">
                                <span><strong><?= number_format($stats['colegios']) ?></strong> colegios</span>
                                <span><strong><?= number_format($stats['sensores']) ?></strong> sensores activos</span>
                                <span><strong><?= number_format($stats['mediciones']) ?></strong> mediciones</span>
                            </div>
                        <?php endif; ?>

                        <span class="simca-cta">Ver el monitoreo <i class="fas fa-arrow-right"></i></span>
                    </a>
                </section>

                <!-- Líneas de trabajo -->
                <section class="portal-section" id="lineas">
                    <div class="section-header">
                        <h3><i class="fas fa-flask"></i> Líneas de trabajo</h3>
                    </div>
                    <div class="lineas-grid">
                        <?php foreach ($contenido['lineas'] as $linea): ?>
                            <article class="linea-card">
                                <span class="linea-icon"><i class="fas <?= $linea['icono'] ?>"></i></span>
                                <h4><?= htmlspecialchars($linea['titulo']) ?></h4>
                                <p><?= htmlspecialchars($linea['texto']) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Información LIASP-CB -->
                <section class="portal-section" id="informacion">
                    <div class="info-grid">
                        <div class="info-text">
                            <div class="section-header">
                                <h3><i class="fas fa-circle-info"></i> Información LIASP-CB</h3>
                            </div>
                            <?php foreach ($contenido['descripcion'] as $parrafo): ?>
                                <p><?= htmlspecialchars($parrafo) ?></p>
                            <?php endforeach; ?>
                        </div>
                        <div class="info-highlights">
                            <?php foreach ($contenido['destacados'] as $d): ?>
                                <div class="highlight">
                                    <span class="highlight-icon"><i class="fas <?= $d['icono'] ?>"></i></span>
                                    <div>
                                        <strong><?= htmlspecialchars($d['titulo']) ?></strong>
                                        <p><?= htmlspecialchars($d['texto']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <!-- Proyectos -->
                <section class="portal-section" id="proyectos">
                    <div class="section-header">
                        <h3><i class="fas fa-diagram-project"></i> Proyectos</h3>
                    </div>
                    <div class="proyectos-grid">
                        <?php foreach ($contenido['proyectos'] as $p): ?>
                            <a href="<?= $p['href'] ?>" class="proyecto-card">
                                <div class="proyecto-head">
                                    <span class="proyecto-icon"><i class="fas <?= $p['icono'] ?>"></i></span>
                                    <span class="badge badge-info"><?= htmlspecialchars($p['estado']) ?></span>
                                </div>
                                <h4><?= htmlspecialchars($p['sigla']) ?></h4>
                                <span class="proyecto-nombre"><?= htmlspecialchars($p['nombre']) ?></span>
                                <p><?= htmlspecialchars($p['texto']) ?></p>
                                <span class="proyecto-cta">Abrir <i class="fas fa-arrow-right"></i></span>
                            </a>
                        <?php endforeach; ?>
                        <div class="proyecto-card proyecto-placeholder">
                            <span class="proyecto-icon"><i class="fas fa-plus"></i></span>
                            <h4>Próximos proyectos</h4>
                            <p>Este espacio está reservado para los nuevos proyectos del laboratorio.</p>
                        </div>
                    </div>
                </section>

                <!-- Colaboradores -->
                <section class="portal-section" id="colaboradores">
                    <div class="section-header">
                        <h3><i class="fas fa-handshake"></i> Colaboradores</h3>
                    </div>
                    <div class="colaboradores-grid">
                        <?php foreach ($contenido['colaboradores'] as $c): ?>
                            <?php $tag = !empty($c['href']) ? 'a' : 'div'; ?>
                            <<?= $tag ?> class="colaborador-card" <?= !empty($c['href']) ? 'href="' . $c['href'] . '" target="_blank" rel="noopener"' : '' ?>>
                                <?php if (!empty($c['logo'])): ?>
                                    <img src="<?= $c['logo'] ?>" alt="<?= htmlspecialchars($c['nombre']) ?>" class="colaborador-logo">
                                <?php else: ?>
                                    <span class="colaborador-icon"><i class="fas <?= $c['icono'] ?? 'fa-building' ?>"></i></span>
                                <?php endif; ?>
                                <strong><?= htmlspecialchars($c['nombre']) ?></strong>
                                <small><?= htmlspecialchars($c['rol']) ?></small>
                            </<?= $tag ?>>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Contáctanos -->
                <section class="portal-section" id="contacto">
                    <div class="contacto-card">
                        <div class="contacto-text">
                            <div class="section-header">
                                <h3><i class="fas fa-envelope"></i> Contáctanos</h3>
                            </div>
                            <p>¿Quieres vincular tu institución al monitoreo, colaborar con el laboratorio o conocer más sobre nuestros proyectos? Escríbenos.</p>
                            <?php if (!empty($contenido['contacto']['correo'])): ?>
                                <a href="mailto:<?= htmlspecialchars($contenido['contacto']['correo']) ?>" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Escribir al laboratorio
                                </a>
                            <?php endif; ?>
                        </div>
                        <ul class="contacto-lista">
                            <li><i class="fas fa-envelope"></i> <span><?= htmlspecialchars($contenido['contacto']['correo'] ?: 'Correo por definir') ?></span></li>
                            <li><i class="fas fa-phone"></i> <span><?= htmlspecialchars($contenido['contacto']['telefono'] ?: 'Teléfono por definir') ?></span></li>
                            <li><i class="fas fa-location-dot"></i> <span><?= htmlspecialchars($contenido['contacto']['direccion'] ?: 'Dirección por definir') ?></span></li>
                            <li><i class="fas fa-clock"></i> <span><?= htmlspecialchars($contenido['contacto']['horario'] ?: 'Horario de atención por definir') ?></span></li>
                        </ul>
                    </div>
                </section>
            </div>

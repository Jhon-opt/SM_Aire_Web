            <div class="container mapa-page">
                <div class="section-header">
                    <h3><i class="fas fa-map-location-dot"></i> Mapa de colegios
                        <span class="badge badge-info"><?= number_format($totalColegios) ?> colegios</span>
                    </h3>
                    <div class="charts-actions">
                        <button class="btn btn-sm" onclick="centrarMapa()" title="Volver a la vista de Bogotá">
                            <i class="fas fa-city"></i> Bogotá
                        </button>
                        <button class="btn btn-sm" onclick="ajustarMapaColegios()" title="Encuadrar todos los colegios con ubicación">
                            <i class="fas fa-expand"></i> Todos los colegios
                        </button>
                    </div>
                </div>

                <div class="mapa-layout">
                    <!-- Mapa -->
                    <div class="mapa-card">
                        <div class="mapa-container mapa-container-page" id="mapaContainer"
                             data-lat="<?= MAPA_CENTRO_LAT ?>" data-lng="<?= MAPA_CENTRO_LNG ?>" data-zoom="<?= MAPA_ZOOM ?>">
                            <div class="mapa-cargando" id="mapaCargando">
                                <div class="spinner"></div>
                                <p>Cargando mapa…</p>
                            </div>
                        </div>
                        <div class="mapa-pie">
                            <div class="mapa-leyenda">
                                <?php foreach (icaCategorias() as $cat): ?>
                                    <span><span class="legend-dot" style="background: <?= $cat['color'] ?>"></span><?= htmlspecialchars($cat['etiqueta']) ?></span>
                                <?php endforeach; ?>
                                <span><span class="legend-dot" style="background: #9CA3AF"></span>Sin lectura reciente</span>
                            </div>
                            <p class="mapa-nota hidden" id="mapaNota"></p>
                        </div>
                    </div>

                    <!-- Lista de colegios -->
                    <aside class="mapa-lista-card">
                        <div class="mapa-lista-head">
                            <h4><i class="fas fa-school"></i> Colegios</h4>
                            <span class="badge badge-live" id="mapaLive"><span class="live-dot"></span> <span>En vivo</span></span>
                        </div>
                        <p class="mapa-lista-ayuda">Toca un colegio para ubicarlo en el mapa o abre sus estadísticas en el panel.</p>
                        <ul class="mapa-lista" id="mapaLista">
                            <li class="mapa-lista-vacia">Cargando colegios…</li>
                        </ul>
                    </aside>
                </div>
            </div>

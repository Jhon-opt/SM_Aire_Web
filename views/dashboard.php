            <div class="container">
                <!-- Banner de error API -->
                <div class="api-error-banner hidden" id="apiErrorBanner">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="apiErrorText">No se pudo conectar con la API.</span>
                    <button class="btn btn-sm btn-retry" onclick="loadAllData(true)">
                        <i class="fas fa-rotate"></i> Reintentar
                    </button>
                </div>

                <!-- Hero: presentación + estado general del aire -->
                <section class="hero">
                    <div class="hero-main">
                        <div class="hero-content">
                            <span class="hero-kicker"><span class="live-dot"></span> Monitoreo en tiempo real</span>
                            <h2 class="hero-title">Calidad del aire en instituciones educativas</h2>
                            <p class="hero-desc">
                                Sensores IoT instalados en colegios miden material particulado (PM2.5 y PM10)
                                y monóxido de carbono (CO). Los datos se actualizan automáticamente cada minuto.
                            </p>
                        </div>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <span class="hero-stat-value"><?= number_format($totalColegios) ?></span>
                                <span class="hero-stat-label">Colegios</span>
                            </div>
                            <div class="hero-stat">
                                <span class="hero-stat-value"><?= number_format($totalSensores) ?></span>
                                <span class="hero-stat-label">Sensores activos</span>
                            </div>
                            <div class="hero-stat">
                                <span class="hero-stat-value" id="totalMediciones"><?= number_format($totalMediciones) ?></span>
                                <span class="hero-stat-label">Mediciones</span>
                            </div>
                            <div class="hero-stat">
                                <span class="hero-stat-value hero-stat-time" id="ultimaActualizacion">
                                    <?= $ultimaActualizacion ? relativeTime($ultimaActualizacion) : '—' ?>
                                </span>
                                <span class="hero-stat-label">Última lectura</span>
                            </div>
                        </div>
                    </div>
                    <div class="hero-status" id="estadoGeneral">
                        <div class="status-card status-card-empty">
                            <i class="fas fa-satellite-dish"></i>
                            <p>Esperando lecturas…</p>
                        </div>
                    </div>
                </section>

                <!-- Filtros -->
                <section class="filters-section" id="filtros">
                    <div class="filters-header">
                        <h3><i class="fas fa-filter"></i> Filtros <span class="badge badge-info" id="rangoBadge">Últimas 24 horas</span></h3>
                        <button class="btn btn-sm" onclick="resetFilters()">
                            <i class="fas fa-undo"></i> Restablecer
                        </button>
                    </div>
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="filterColegio">Colegio</label>
                            <select id="filterColegio" onchange="onColegioChange()">
                                <option value="">Todos los colegios</option>
                                <?php foreach ($colegios as $c): ?>
                                    <option value="<?= $c['id_colegio'] ?>">
                                        <?= htmlspecialchars($c['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="filterDispositivo">Dispositivo</label>
                            <select id="filterDispositivo" onchange="onFilterChange()">
                                <option value="">Todos los dispositivos</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="filterIntervalo">Intervalo</label>
                            <select id="filterIntervalo" onchange="onIntervaloChange()">
                                <option value="24h" selected>Últimas 24 horas</option>
                                <option value="7d">Últimos 7 días</option>
                                <option value="30d">Últimos 30 días</option>
                                <option value="all">Todo el historial</option>
                                <option value="custom">Personalizado</option>
                            </select>
                        </div>
                        <div class="filter-group filter-date hidden" id="dateRange">
                            <label for="filterFechaInicio">Desde</label>
                            <input type="date" id="filterFechaInicio">
                        </div>
                        <div class="filter-group filter-date hidden" id="dateRangeEnd">
                            <label for="filterFechaFin">Hasta</label>
                            <input type="date" id="filterFechaFin">
                        </div>
                        <div class="filter-group filter-apply hidden" id="applyDateBtn">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary" onclick="applyCustomDate()">
                                <i class="fas fa-check"></i> Aplicar
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Loading -->
                <div class="loading-overlay hidden" id="loadingOverlay">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Cargando datos...</p>
                    </div>
                </div>

                <!-- Tarjetas de estado actual -->
                <section class="cards-section" id="estado">
                    <div class="section-header">
                        <h3><i class="fas fa-gauge-high"></i> Estado actual</h3>
                        <span class="badge badge-live" id="liveBadge" title="Los datos se actualizan solos cuando llega una medición nueva">
                            <span class="live-dot"></span>
                            <span id="liveText">En vivo</span>
                        </span>
                    </div>
                    <div class="cards-grid" id="cardsContainer">
                        <div class="card card-placeholder">
                            <div class="card-empty">
                                <i class="fas fa-microchip"></i>
                                <p>Cargando la última lectura…</p>
                            </div>
                        </div>
                    </div>

                    <!-- Escala de colores del ICA -->
                    <div class="ica-scale" aria-label="Escala del índice de calidad del aire">
                        <?php foreach (icaCategorias() as $cat): ?>
                            <div class="ica-scale-item">
                                <span class="ica-scale-bar" style="background: <?= $cat['color'] ?>"></span>
                                <span class="ica-scale-label"><?= htmlspecialchars($cat['etiqueta']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Gráficas -->
                <section class="charts-section" id="graficas">
                    <div class="section-header">
                        <h3><i class="fas fa-chart-line"></i> Gráficas <span class="badge badge-info hidden" id="bucketBadge"></span></h3>
                        <div class="charts-actions">
                            <button class="btn btn-sm" onclick="toggleAllCharts()">
                                <i class="fas fa-expand"></i> Expandir/Colapsar
                            </button>
                        </div>
                    </div>
                    <div class="charts-grid" id="chartsContainer">
                        <div class="card card-placeholder">
                            <div class="card-empty">
                                <i class="fas fa-chart-line"></i>
                                <p>Cargando gráficas…</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Estadísticas -->
                <section class="stats-section" id="estadisticas">
                    <div class="section-header">
                        <h3><i class="fas fa-calculator"></i> Estadísticas del período</h3>
                        <span class="badge badge-info" id="totalRegistrosBadge">0 registros</span>
                    </div>
                    <div class="table-responsive" id="statsContainer">
                        <div class="card card-placeholder">
                            <div class="card-empty">
                                <i class="fas fa-calculator"></i>
                                <p>Cargando estadísticas…</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Tabla de mediciones -->
                <section class="table-section" id="mediciones">
                    <div class="section-header">
                        <h3><i class="fas fa-table"></i> Mediciones</h3>
                        <div class="table-actions">
                            <button class="btn btn-sm" onclick="exportExcel()">
                                <i class="fas fa-file-excel"></i> Exportar Excel
                            </button>
                            <button class="btn btn-sm" onclick="toggleOrden()">
                                <i class="fas fa-sort"></i> <span id="ordenBtn">↓ Más recientes</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive" id="tableContainer">
                        <div class="card card-placeholder">
                            <div class="card-empty">
                                <i class="fas fa-table"></i>
                                <p>Cargando mediciones…</p>
                            </div>
                        </div>
                    </div>
                    <div class="pagination" id="paginationContainer"></div>
                </section>
            </div>

            <!-- Modal "Información SIMCA" (menú superior) -->
            <div class="info-modal-overlay hidden" id="infoSimcaModal" onclick="if (event.target === this) cerrarInfoSimca()">
                <div class="info-modal" role="dialog" aria-modal="true" aria-labelledby="infoSimcaTitulo">
                    <div class="info-modal-header">
                        <span class="brand-badge">SIMCA</span>
                        <h3 id="infoSimcaTitulo">Sistema Inteligente de Monitoreo de Calidad del Aire basado en sensores de bajo costo</h3>
                        <button class="chart-modal-close" onclick="cerrarInfoSimca()" title="Cerrar"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="info-modal-body">
                        <p>
                            SIMCA es un proyecto del Laboratorio Urbano de Inteligencia Ambiental y Salud Pública (LIASP-CB)
                            de la Universidad Distrital Francisco José de Caldas. Una red de sensores de bajo costo instalados
                            en colegios mide material particulado (PM2.5 y PM10) y monóxido de carbono (CO) y envía los datos
                            a este panel, que se actualiza automáticamente.
                        </p>
                        <div class="info-steps">
                            <div class="info-step"><span class="highlight-icon"><i class="fas fa-microchip"></i></span><div><strong>1. Sensores</strong><p>Cada dispositivo mide el aire y envía una lectura por minuto.</p></div></div>
                            <div class="info-step"><span class="highlight-icon"><i class="fas fa-server"></i></span><div><strong>2. Servidor</strong><p>Las mediciones se validan y almacenan en la base de datos del proyecto.</p></div></div>
                            <div class="info-step"><span class="highlight-icon"><i class="fas fa-gauge-high"></i></span><div><strong>3. Panel</strong><p>Estado actual, gráficas, estadísticas y exportación en hora de Colombia.</p></div></div>
                        </div>
                        <h4>Índice de calidad del aire (ICA · Resolución 2254 de 2017)</h4>
                        <ul class="info-legend">
                            <?php foreach (icaCategorias() as $cat): ?>
                                <li><span class="legend-dot" style="background: <?= $cat['color'] ?>"></span><?= htmlspecialchars($cat['etiqueta']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="container">
                <!-- Hero Section -->
                <section class="hero">
                    <div class="hero-content">
                        <h2 class="hero-title">Monitoreo de Calidad del Aire</h2>
                        <p class="hero-desc">
                            Sistema de monitoreo en tiempo real de la calidad del aire en instituciones educativas.
                            Datos capturados por sensores IoT distribuidos en colegios de todo el país.
                        </p>
                    </div>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <span class="hero-stat-value"><?= number_format($totalColegios) ?></span>
                            <span class="hero-stat-label">Colegios</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat-value"><?= number_format($totalSensores) ?></span>
                            <span class="hero-stat-label">Sensores Activos</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat-value"><?= number_format($totalMediciones) ?></span>
                            <span class="hero-stat-label">Mediciones</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat-value hero-stat-time" id="ultimaActualizacion">
                                <?= $ultimaActualizacion ? relativeTime($ultimaActualizacion) : '—' ?>
                            </span>
                            <span class="hero-stat-label">Última Actualización</span>
                        </div>
                    </div>
                </section>

                <!-- Filtros -->
                <section class="filters-section">
                    <div class="filters-header">
                        <h3><i class="fas fa-filter"></i> Filtros</h3>
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
                                <option value="24h">Últimas 24 horas</option>
                                <option value="7d">Últimos 7 días</option>
                                <option value="30d" selected>Últimos 30 días</option>
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

                <!-- Tarjetas de resumen -->
                <section class="cards-section">
                    <div class="section-header">
                        <h3><i class="fas fa-gauge-high"></i> Estado Actual</h3>
                        <span class="badge badge-auto-refresh" id="autoRefreshBadge">
                            <i class="fas fa-sync"></i> Auto: <span id="refreshCountdown">60</span>s
                        </span>
                    </div>
                    <div class="cards-grid" id="cardsContainer">
                        <div class="card card-placeholder" colspan="8">
                            <div class="card-empty">
                                <i class="fas fa-microchip"></i>
                                <p>Selecciona un dispositivo para ver los datos</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Gráficas -->
                <section class="charts-section">
                    <div class="section-header">
                        <h3><i class="fas fa-chart-line"></i> Gráficas</h3>
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
                                <p>Selecciona un dispositivo para ver las gráficas</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Estadísticas -->
                <section class="stats-section">
                    <div class="section-header">
                        <h3><i class="fas fa-calculator"></i> Estadísticas</h3>
                        <span class="badge badge-info" id="totalRegistrosBadge">0 registros</span>
                    </div>
                    <div class="table-responsive" id="statsContainer">
                        <div class="card card-placeholder">
                            <div class="card-empty">
                                <i class="fas fa-calculator"></i>
                                <p>Selecciona un dispositivo para ver estadísticas</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Tabla de mediciones -->
                <section class="table-section">
                    <div class="section-header">
                        <h3><i class="fas fa-table"></i> Mediciones</h3>
                        <div class="table-actions">
                            <button class="btn btn-sm" onclick="exportCSV()">
                                <i class="fas fa-download"></i> Exportar CSV
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
                                <p>Selecciona un dispositivo para ver los datos</p>
                            </div>
                        </div>
                    </div>
                    <div class="pagination" id="paginationContainer"></div>
                </section>
            </div>

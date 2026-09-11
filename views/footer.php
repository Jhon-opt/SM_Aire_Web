        </main>

        <footer class="site-footer">
            <div class="site-footer-inner">
                <div class="footer-brand">
                    <img src="<?= asset('img/logo-ud.png') ?>" alt="Universidad Distrital Francisco José de Caldas">
                    <div>
                        <strong>LIASP-CB</strong> · Laboratorio Urbano de Inteligencia Ambiental y Salud Pública
                        <br><span>Universidad Distrital Francisco José de Caldas · <?= date('Y') ?></span>
                    </div>
                </div>
                <nav class="footer-links" aria-label="Enlaces del portal">
                    <a href="<?= BASE_URL ?>/">Inicio</a>
                    <a href="<?= BASE_URL ?>/dashboard">SIMCA</a>
                    <a href="<?= BASE_URL ?>/#colaboradores">Colaboradores</a>
                    <a href="<?= BASE_URL ?>/#contacto">Contáctanos</a>
                </nav>
                <div class="footer-note">
                    <i class="fas fa-clock"></i> Hora de Colombia (UTC−5) · v<?= APP_VERSION ?>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js"></script>
    <script src="<?= asset('js/site.js') ?>"></script>
    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/charts.js') ?>"></script>
    <script src="<?= asset('js/mapa.js') ?>"></script>
</body>
</html>

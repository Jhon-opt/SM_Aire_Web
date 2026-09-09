            <div class="container docs-layout">
                <!-- Encabezado -->
                <section class="hero docs-hero">
                    <div class="hero-content">
                        <h2 class="hero-title"><i class="fas fa-book-open"></i> <?= htmlspecialchars($docTitle) ?></h2>
                        <p class="hero-desc">
                            Documento técnico del dispositivo IoT: hardware, firmware, comunicación y arquitectura.
                            Puedes leerlo en línea o descargarlo en PDF.
                        </p>
                    </div>
                    <div class="docs-actions">
                        <a href="#contenido" class="btn btn-light">
                            <i class="fas fa-book-reader"></i> Leer documento
                        </a>
                        <a href="<?= BASE_URL ?>/documentacion/pdf" class="btn btn-light">
                            <i class="fas fa-file-pdf"></i> Descargar PDF
                            <?php if ($pdfSize > 0): ?>
                                <span class="docs-size">(<?= round($pdfSize / 1024) ?> KB)</span>
                            <?php endif; ?>
                        </a>
                    </div>
                </section>

                <div class="docs-body">
                    <!-- Índice -->
                    <?php if (!empty($toc)): ?>
                        <aside class="docs-toc card">
                            <h3><i class="fas fa-list"></i> Índice</h3>
                            <nav>
                                <ul>
                                    <?php foreach ($toc as $item): ?>
                                        <li class="toc-level-<?= (int) $item['level'] ?>">
                                            <a href="#<?= htmlspecialchars($item['id']) ?>">
                                                <?= htmlspecialchars($item['text']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </nav>
                            <a href="<?= BASE_URL ?>/documentacion/pdf" class="btn btn-primary btn-block">
                                <i class="fas fa-download"></i> Descargar PDF
                            </a>
                        </aside>
                    <?php endif; ?>

                    <!-- Contenido -->
                    <article class="docs-content card" id="contenido">
                        <?= $docHtml ?>
                    </article>
                </div>
            </div>

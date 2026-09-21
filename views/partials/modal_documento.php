<?php
/**
 * Ventana emergente con un documento institucional.
 * Espera $doc con: id, sigla, titulo, secciones[] (id, titulo, bloques[]).
 * Opcional: $docExtraHtml (HTML adicional al final del documento).
 */
$docExtraHtml = $docExtraHtml ?? '';
?>
<div class="info-modal-overlay hidden" id="<?= $doc['id'] ?>" onclick="if (event.target === this) cerrarModal('<?= $doc['id'] ?>')">
    <div class="info-modal doc-modal" role="dialog" aria-modal="true" aria-labelledby="<?= $doc['id'] ?>-titulo">
        <div class="info-modal-header">
            <span class="brand-badge"><?= htmlspecialchars($doc['sigla']) ?></span>
            <h3 id="<?= $doc['id'] ?>-titulo"><?= htmlspecialchars($doc['titulo']) ?></h3>
            <button class="chart-modal-close" onclick="cerrarModal('<?= $doc['id'] ?>')" title="Cerrar"><i class="fas fa-times"></i></button>
        </div>

        <nav class="doc-nav" aria-label="Secciones del documento">
            <?php foreach ($doc['secciones'] as $i => $sec): ?>
                <button type="button" class="doc-nav-item <?= $i === 0 ? 'activo' : '' ?>"
                        onclick="irASeccionModal('<?= $doc['id'] ?>', '<?= $doc['id'] ?>-<?= $sec['id'] ?>', this)">
                    <?= htmlspecialchars($sec['titulo']) ?>
                </button>
            <?php endforeach; ?>
        </nav>

        <div class="info-modal-body doc-body">
            <?php foreach ($doc['secciones'] as $sec): ?>
                <section class="doc-seccion" id="<?= $doc['id'] ?>-<?= $sec['id'] ?>">
                    <h3 class="doc-h2"><?= htmlspecialchars($sec['titulo']) ?></h3>
                    <?php foreach ($sec['bloques'] as $bloque): ?>
                        <?php [$tipo, $contenido] = $bloque; ?>
                        <?php if ($tipo === 'p'): ?>
                            <p><?= htmlspecialchars($contenido) ?></p>
                        <?php elseif ($tipo === 'h'): ?>
                            <h4 class="doc-h3"><?= htmlspecialchars($contenido) ?></h4>
                        <?php elseif ($tipo === 'ul' || $tipo === 'ol'): ?>
                            <<?= $tipo ?> class="doc-lista">
                                <?php foreach ($contenido as $item): ?>
                                    <li><?= htmlspecialchars($item) ?></li>
                                <?php endforeach; ?>
                            </<?= $tipo ?>>
                        <?php elseif ($tipo === 'capas'): ?>
                            <ol class="doc-capas">
                                <?php foreach ($contenido as $capa): ?>
                                    <li>
                                        <strong><?= htmlspecialchars($capa['titulo']) ?>.</strong>
                                        <?= htmlspecialchars($capa['texto']) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php elseif ($tipo === 'tabla'): ?>
                            <div class="doc-tabla-wrap">
                                <table class="doc-tabla">
                                    <thead>
                                        <tr>
                                            <?php foreach ($contenido['encabezado'] as $h): ?>
                                                <th><?= htmlspecialchars($h) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contenido['filas'] as $fila): ?>
                                            <tr>
                                                <?php foreach ($fila as $celda): ?>
                                                    <td><?= htmlspecialchars($celda) ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
            <?= $docExtraHtml ?>
        </div>
    </div>
</div>

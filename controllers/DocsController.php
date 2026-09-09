<?php

class DocsController
{
    private const TXT_PATH = BASE_PATH . '/docs/documentacion.txt';
    private const PDF_PATH = BASE_PATH . '/docs/documentacion-tecnica-calidad-aire.pdf';
    private const PDF_NAME = 'documentacion-tecnica-calidad-aire.pdf';

    public function index(): void
    {
        if (!file_exists(self::TXT_PATH)) {
            http_response_code(404);
            view('header', ['titulo' => 'Documentación no encontrada', 'nav' => 'docs']);
            echo '<div class="container"><div class="error-card"><h2>Documento no encontrado</h2>'
                . '<p>No se encontró el archivo de documentación.</p></div></div>';
            view('footer');
            return;
        }

        $markdown = file_get_contents(self::TXT_PATH);
        $doc = markdown_to_html($markdown);
        $pdfSize = file_exists(self::PDF_PATH) ? filesize(self::PDF_PATH) : 0;

        view('header', ['titulo' => $doc['title'] . ' - Air Monitor', 'nav' => 'docs']);
        view('docs', [
            'docHtml'  => $doc['html'],
            'toc'      => $doc['toc'],
            'docTitle' => $doc['title'],
            'pdfSize'  => $pdfSize,
        ]);
        view('footer');
    }

    public function pdf(): void
    {
        if (!file_exists(self::PDF_PATH)) {
            http_response_code(404);
            echo 'PDF no encontrado.';
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . self::PDF_NAME . '"');
        header('Content-Length: ' . filesize(self::PDF_PATH));
        header('Cache-Control: private');
        readfile(self::PDF_PATH);
        exit;
    }
}

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #111827; color: #fff; }
        .error-page { text-align: center; padding: 2rem; }
        .error-page h1 { font-size: 6rem; background: linear-gradient(135deg, #4F46E5, #7C3AED); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .error-page p { font-size: 1.2rem; color: #9CA3AF; margin: 1rem 0 2rem; }
        .error-page a { display: inline-flex; align-items: center; gap: .5rem; padding: .75rem 1.5rem; background: #4F46E5; color: #fff; text-decoration: none; border-radius: .5rem; font-weight: 500; transition: background .2s; }
        .error-page a:hover { background: #7C3AED; }
    </style>
</head>
<body>
    <div class="error-page">
        <h1>404</h1>
        <p>La página que buscas no existe.</p>
        <a href="<?= BASE_URL ?>"><i class="fas fa-arrow-left"></i> Volver al Dashboard</a>
    </div>
</body>
</html>

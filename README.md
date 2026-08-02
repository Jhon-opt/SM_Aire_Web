# SM_Aire_Web

Dashboard web de calidad del aire que consume la API desplegada en Render (`https://calidad-aire-p.onrender.com`).

## Requisitos

- PHP 8.2+ (extensiones `xmlwriter`, `simplexml`, `libxml`)
- Conexión a internet (consume la API remota)

## Configuración

Variables de entorno (opcionales, con defaults seguros):

| Variable    | Default                               | Descripción |
|-------------|---------------------------------------|-------------|
| `API_MODE`  | `true` (cualquier valor distinto de `"false"`) | Usa la API remota en vez de datos falsos |
| `API_URL`   | `https://calidad-aire-p.onrender.com` | URL base de la API |

## Local (Docker)

```bash
docker run --rm -p 8080:80 \
  -v "$(pwd)":/var/www/html \
  php:8.2-apache \
  bash -c "a2enmod rewrite && apache2-foreground"
```

Abrir `http://localhost:8080`.

## Deploy en Render

El archivo `render.yaml` (Blueprint) define el servicio web `sm-aire-web`:

1. En [render.com](https://render.com), **New → Blueprint**.
2. Conecta el repositorio `SM_Aire_Web`.
3. Render detecta `render.yaml` y crea el servicio con Docker (`php:8.2-apache`), puerto 80 y las variables `API_MODE=true` y `API_URL` apuntando a la API.

Alternativa manual: **New → Web Service** → elegir repo → runtime *Docker* → `Dockerfile`.

> Nota: en el plan gratuito de Render, tanto la API como esta web hibernan tras ~15 min sin tráfico; el primer acceso tras el sueño tarda unos 30 s (cold start).

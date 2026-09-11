# SM_Aire_Web — rama `cpanel`

Dashboard web de calidad del aire. En esta rama el modo por defecto es **MySQL local** (deploy en cPanel con `APP_MODE=db`).

> La rama `main` usa la API remota de Render por defecto. Esta rama `cpanel` existe para hosting compartido sin depender de la API.

## Requisitos

- PHP 8.1+ con PDO MySQL (`pdo_mysql`) habilitado
- MySQL/MariaDB local (ver `database/schema-cpanel.sql`)

## Configuración

Variables de entorno (o `SetEnv` en `.htaccess`, ver ejemplo comentado arriba del todo):

| Variable    | Default        | Descripción |
|-------------|----------------|-------------|
| `APP_MODE`  | `db`           | `api` (API remota), `db` (MySQL local) o `fake` (datos de prueba) |
| `API_URL`   | `https://calidad-aire-p.onrender.com` | URL base de la API (solo modo `api`) |
| `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` | `localhost` / `air_monitor` / `root` / `` | Credenciales MySQL (solo modo `db`) |

## Deploy en cPanel (subdominio + MySQL local)

1. Crear el subdominio (ej: `aire.tudominio.com`, raíz `public_html/aire`) y asignarle PHP 8.1+.
2. Crear la BD y el usuario en **MySQL Databases** (anotar credenciales).
3. En **phpMyAdmin**, con la BD seleccionada, importar `database/schema-cpanel.sql`.
4. Subir el ZIP de esta rama a `public_html/aire` y extraerlo (que `index.php` quede en la raíz).
5. Editar el `.htaccess`: descomentar el bloque de arriba y completar `DB_*`.
6. Probar: `/dashboard`, `/documentacion`, `/documentacion/pdf`.

## Estructura del portal (v1.1)

| Ruta | Página |
|------|--------|
| `/` (o `/liasp`) | **Portal LIASP-CB**: inicio del laboratorio con logo UD, menú (Inicio · Información LIASP-CB · Proyectos ▸ SIMCA · Colaboradores · Contáctanos), banner con acceso a SIMCA y estado del aire en vivo, líneas de trabajo, información, proyectos, colaboradores y contacto. Los textos se editan en `controllers/LiaspController.php`. Si existe `assets/img/portada.jpg` se usa como imagen del banner. |
| `/dashboard` (o `/simca`) | **SIMCA**: panel de monitoreo con menú (← Volver al portal · Información SIMCA · Colegios · Consolidado · Mapa · Exportar · Contáctanos). Acepta `?colegio=ID` (y `&dispositivo=ID`) para abrirse ya filtrado. |
| `/mapa` | **Mapa de colegios** (SIMCA): mapa de Bogotá con un marcador por colegio y lista lateral; "Ver estadísticas del colegio" abre `/dashboard?colegio=ID`. |

Los menús de ambos sitios se definen en `menuSitio()` (`includes/functions.php`).

## Interfaz (v1.1)

- **Variables**: PM2.5, PM10 y CO se muestran siempre. CO2, O₃, NO₂, temperatura y humedad
  solo aparecen (tarjetas, gráficas, estadísticas, tabla y Excel) cuando existen datos en el rango.
  Se configuran en `parametrosPrincipales()` / `parametrosOpcionales()` (`includes/functions.php`).
- **Hora**: la base de datos guarda `fecha_hora` en **UTC** (la ingesta usa `gmdate()`); toda la
  presentación (tarjetas, tabla, gráficas y Excel) se convierte a **hora de Colombia**
  (`APP_TIMEZONE = America/Bogota` en `config.php`). Los filtros de fecha personalizados se
  interpretan como días completos en hora de Colombia.
- **Tiempo real**: el navegador consulta `api/ultimas` cada `LIVE_POLL_SECONDS` (10 s). Cuando
  llega una medición nueva actualiza tarjetas, estado general, gráficas, estadísticas y tabla sin
  recargar la página. Las gráficas se promedian por intervalos (`bucketSegundos()`) para que
  ningún rango supere unos cientos de puntos.
- **Estado general y colores**: categorías del ICA colombiano (Resolución 2254 de 2017) con la
  peor categoría entre PM2.5, PM10 y CO. Tema blanco/verde, sin modo oscuro.
- **Mapa de colegios** (`assets/js/mapa.js`): MapLibre GL JS + OpenFreeMap (gratuito, sin clave; estilo
  Positron), con respaldo automático a mosaicos de OpenStreetMap si OpenFreeMap no responde. Vista
  predeterminada: Bogotá (`MAPA_CENTRO_LAT/LNG`, `MAPA_ZOOM` en `config.php`). Un marcador por colegio
  coloreado por su estado ICA con el valor de PM2.5; el popup muestra los valores y enlaza al panel filtrado.
  Requiere las columnas `latitud`/`longitud` en `colegio` (`database/migration-mapa.sql`); los colegios
  sin coordenadas se listan bajo el mapa. Endpoint: `api/mapa`.
- `APP_VERSION` se añade a las URLs de CSS/JS (`?v=`) para invalidar la caché al desplegar.

## Ingesta de sensores (sin API externa)

Los dispositivos envían mediciones con `POST /api/ingest` (solo modo `db`):

1. Importar `database/migration-ingest.sql` en phpMyAdmin (agrega `api_key` a `dispositivo`).
2. Asignar una clave a cada dispositivo:
   ```sql
   UPDATE dispositivo SET api_key='TU_CLAVE' WHERE id_dispositivo=1;
   ```
3. El Arduino hace `POST` JSON a `https://TU_SUBDOMINIO/api/ingest`:
   ```json
   {"device_key": "TU_CLAVE", "pm2_5": 12.5, "pm10": 30.2, "co": 0.4, "temperatura": 24.3, "humedad": 58}
   ```
   Responde `201 {"ok":true,"id":...}`. Sin `device_key` válida → `401`; dato fuera de rango → `400`.

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

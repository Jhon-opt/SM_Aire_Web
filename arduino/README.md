# Firmware de los nodos SIMCA

Programa que ejecutan los nodos de monitoreo: mide PM2.5 y PM10 con un PMS7003 y CO con un
MQ-7, promedia las lecturas de cada minuto y las envía por HTTPS a `POST /api/ingest`.

- Placa: **Arduino UNO R4 WiFi** (paquete *Arduino UNO R4 Boards* ≥ 1.3.0, firmware del
  ESP32-S3 ≥ 0.5.0). No necesita librerías externas: todas vienen con la placa.
- Sketch: [`pms7003_mq7_api/pms7003_mq7_api.ino`](pms7003_mq7_api/pms7003_mq7_api.ino)

## Antes de cargarlo en un nodo

Copiar `secretos-ejemplo.h` como `secretos.h` en la misma carpeta y completar los tres valores:

| Valor | Qué es |
|-------|--------|
| `SECRET_SSID` / `SECRET_PASS` | Red Wi-Fi del sitio donde queda instalado el nodo |
| `DEVICE_KEY` | Clave **de ese nodo** (64 caracteres hexadecimales). Es lo único que le dice al servidor a qué institución pertenece la medición, así que cada nodo lleva la suya |

`secretos.h` no se sube al repositorio (está en `.gitignore`). La clave de cada nodo se genera
al darlo de alta en la base de datos con `database/nodo-nuevo.sql`.

## Conexiones

| Componente | Pin | Arduino |
|------------|-----|---------|
| PMS7003 | TX | D0 (RX1) |
| PMS7003 | VCC / GND | 5V / GND |
| MQ-7 | AOUT | A1 |
| MQ-7 | VCC / GND | 5V / GND |

## Qué hace sin que nadie intervenga

- **Promedia un minuto** de lecturas (unas 60) y envía el resultado, no la última lectura suelta.
- **Calibra el MQ-7 solo**: guarda la resistencia más alta de cada hora y, con las 3 horas más
  limpias de las últimas 24, recalcula R0 (la resistencia sube cuando el aire está más limpio).
  El valor se guarda en la EEPROM, sobrevive a reinicios y compensa el envejecimiento del sensor.
- **Watchdog**: reinicia la placa si el programa deja de responder (desactivable con
  `USAR_WATCHDOG 0` si un nodo con red muy lenta se reiniciara solo).
- **Reconecta el Wi-Fi** y sincroniza la hora por NTP cada 6 horas.
- **Informa su estado en la matriz de LED**: ondas de Wi-Fi al conectar, reloj de arena al
  calentar, nube al operar, flecha al enviar, visto bueno si el servidor aceptó, X si falló y
  triángulo si el sensor de partículas no responde.
- Envía además `n_muestras`, la hora de captura, la versión del firmware y la calibración vigente.

## Comandos por el monitor serie (opcionales)

| Tecla | Efecto |
|-------|--------|
| `c` | Fuerza una calibración inmediata del MQ-7 con el aire del momento. No hace falta usarlo: el ajuste es automático. Solo tiene sentido en aire limpio y tras 20 minutos encendido |

## Nota sobre la medición de CO

El MQ-7 se especifica de 20 a 2000 ppm y el aire ambiente tiene entre 0,5 y 5 ppm, así que su
valor es **orientativo**, no una medición exacta. La calibración automática elimina el sesgo de
fijar el cero con aire contaminado, pero no cambia esa limitación del sensor. Si el nodo se
compara alguna vez con una estación de referencia, el ajuste se hace con la constante
`MQ7_RATIO_AIRE_LIMPIO` (subirla baja las lecturas de CO).

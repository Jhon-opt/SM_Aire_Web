/*
  pms7003_mq7_api.ino
  Arduino UNO R4 WiFi + PMS7003 + MQ7 -> envio de PM y CO a una API

  Conexiones PMS7003:
    PMS7003 TX  -> Arduino RX1 (D0)
    PMS7003 RX  -> Arduino TX1 (D1)
    PMS7003 VCC -> 5V
    PMS7003 GND -> GND

  Conexiones MQ7 (monoxido de carbono):
    MQ7 AOUT -> Arduino A1   (señal analogica; D1 esta ocupado por el PMS7003)
    MQ7 VCC  -> 5V
    MQ7 GND  -> GND

  Requisitos:
    - Board package "Arduino UNO R4 Boards" >= 1.3.0
    - Firmware ESP32-S3 >= 0.5.0

  CONFIGURAR ANTES DE SUBIR (en secretos.h, copiando secretos-ejemplo.h):
    - SECRET_SSID / SECRET_PASS -> red WiFi del sitio
    - DEVICE_KEY                -> clave de ESTE nodo en la API
                                   (cada nodo lleva la suya; es lo que le dice
                                    al servidor a que institucion pertenece)
    - MQ7_RL                    -> resistencia de carga de tu modulo MQ7
                                   (10 kOhm en la mayoria; algunos usan 1 kOhm)

  NOTA MQ7:
    - El sensor se calibra solo al encender: hazlo en aire limpio
      (ventilado, sin humo ni autos cerca).
    - La primera vez dejalo conectado 24-48 h (quemado inicial)
      para que las lecturas se estabilicen.

  CALIBRACION DEL MQ7 (R0) - AUTOMATICA, SIN INTERVENCION:
    - La resistencia del MQ7 SUBE cuando el aire esta mas limpio. El nodo
      guarda el valor mas alto de cada hora y toma como referencia el mayor
      de las ultimas 24 h, que corresponde al aire mas limpio del dia
      (normalmente de madrugada). De ahi calcula R0 = Rs_limpio / 27.5.
    - Se ajusta solo, todos los dias, y asi compensa el envejecimiento del
      sensor. No hay que tocar nada ni conectar un PC.
    - R0 se guarda en la EEPROM (como maximo una vez al dia para no
      desgastarla) y se conserva entre reinicios y cortes de luz.
    - Las lecturas de la primera media hora tras encender no cuentan: el
      sensor todavia se esta estabilizando.
    - Opcional: escribir c en el monitor serie fuerza una calibracion
      inmediata con el aire del momento (solo si ese aire es limpio).
    - MQ7_R0_FIJO permite fijar el valor por codigo e ignorar todo lo anterior.

  DATOS QUE SE ENVIAN ADEMAS DE LAS MEDICIONES:
    - n_muestras ....... lecturas validas usadas en el promedio del minuto
    - measured_at ...... hora de la medicion en el nodo (epoch UTC, por NTP)
    - firmware_version . version de este programa
    - r0_mq7 ........... R0 vigente y fecha de su calibracion

  MATRIZ DE LED (12x8) de la placa - estado sin monitor serie:
    - Ondas de Wi-Fi animadas ... buscando/conectando a la red
    - Reloj de arena ............ calentando sensores (60 s)
    - Reloj ..................... calibrando el MQ7
    - Nube con Wi-Fi ............ funcionando normal (conectado)
    - Flecha hacia arriba ....... enviando datos al servidor
    - Marca de verificacion ..... el servidor acepto los datos (3 s)
    - X ......................... el ultimo envio fallo
    - Triangulo con ! ........... el PMS7003 no entrega datos
*/

#include <WiFiS3.h>
#include <WiFiSSLClient.h>
#include <EEPROM.h>
#include <WDT.h>
#include "Arduino_LED_Matrix.h"
#include "secretos.h"

// Version de este programa. Se envia en cada medicion para poder relacionar
// un comportamiento raro en campo con la version cargada en el nodo.
#define FIRMWARE_VERSION "1.2.0"

#define SERIAL_BAUD 9600
#define PMS_BAUD    9600

// Cada cuanto se envia (ms). Se envia el PROMEDIO de todas las
// lecturas tomadas durante el intervalo, no la ultima lectura.
#define INTERVALO_ENVIO 60000

// Las credenciales (red WiFi y clave del dispositivo) estan en secretos.h,
// que NO se sube al repositorio. Copia secretos-ejemplo.h como secretos.h y
// pon ahi los valores de este nodo.

// API (https)
#define API_HOST "monitor.grupometis.org"
#define API_PORT 443
#define API_PATH "/api/ingest"

// MQ7
#define MQ7_PIN A1

// Resistencia de carga del modulo en kOhm (revisa tu modulo)
#define MQ7_RL 10.0

// Rs/R0 en aire limpio segun datasheet del MQ7. Equivale a suponer que el aire
// mas limpio del dia tiene unos 0,65 ppm de CO. Si se compara el nodo con una
// estacion de referencia y el CO sale sistematicamente alto o bajo, este es el
// valor que hay que ajustar (subirlo baja las lecturas de CO).
#define MQ7_RATIO_AIRE_LIMPIO 27.5

// Curva CO del datasheet: ppm = A * (Rs/R0)^B
#define MQ7_CURVA_A 99.042
#define MQ7_CURVA_B -1.518

// R0 fijado por codigo (kOhm). En 0 se calcula de forma automatica.
#define MQ7_R0_FIJO 0.0

// -- Calibracion automatica por linea base movil --
// Las lecturas de los primeros minutos tras encender no cuentan: el sensor
// aun se esta estabilizando.
#define MQ7_ESTABILIZACION_MS 1800000UL   // 30 min

// Ventana de 24 bloques de 1 hora: se guarda el Rs mas alto de cada bloque.
#define MQ7_BLOQUES    24
#define MQ7_BLOQUE_MS  3600000UL          // 1 hora

// Con menos bloques con datos, R0 se considera provisional.
#define MQ7_BLOQUES_FIABLES 12            // 12 h de historia

// La referencia se toma como el promedio de las N horas mas limpias, no del
// maximo absoluto: un unico minuto raro no debe fijar la calibracion.
#define MQ7_MEJORES_HORAS 3

// Cambio minimo para actualizar R0 (evita reescribir por ruido).
#define MQ7_CAMBIO_MINIMO 0.03            // 3 %

// La EEPROM admite escrituras limitadas: como mucho una al dia.
#define MQ7_GUARDAR_CADA_MS 86400000UL    // 24 h

// Watchdog: reinicia la placa si el programa deja de responder.
// El maximo de la UNO R4 son 5,6 s; se usa 5,5 s.
// Si un nodo con red muy lenta se reiniciara solo (un handshake TLS que tarde
// mas de 5,5 s lo dispararia), poner USAR_WATCHDOG en 0 y volver a cargar.
#define USAR_WATCHDOG  1
#define WDT_TIMEOUT_MS 5500

// Tiempo maximo que WiFi.begin() espera a conectar (ms). Por defecto son
// 10 s, mas que el watchdog; con un valor menor el reintento lo maneja
// conectarWiFi(), que si refresca el watchdog entre intentos.
#define WIFI_TIMEOUT_MS 3000

// Cada cuanto se vuelve a pedir la hora por NTP (ms)
#define NTP_RESINCRONIZAR 21600000UL   // 6 horas

// Epoch minimo aceptable (01/01/2024): descarta una hora invalida del modulo
#define EPOCH_MINIMO 1704067200UL

char ssid[] = SECRET_SSID;
char pass[] = SECRET_PASS;

int status = WL_IDLE_STATUS;

const uint8_t PAQUETE = 32;
uint8_t buffer[PAQUETE];

uint16_t pm1 = 0;
uint16_t pm25 = 0;
uint16_t pm10 = 0;

float coPPM = 0.0;
float mq7R0 = 10.0;
unsigned long mq7CalibradoEn = 0;   // epoch UTC de la calibracion vigente (0 = desconocida)
bool mq7R0Provisional = true;       // true mientras la linea base no tenga suficiente historia

// Linea base movil: Rs mas alto observado en cada una de las ultimas 24 horas
float rsPorHora[MQ7_BLOQUES];
uint8_t bloqueActual = 0;
unsigned long inicioBloque = 0;
unsigned long encendidoEn = 0;
unsigned long ultimoGuardadoR0 = 0;

// Hora del nodo: se pide por NTP al conectar y se mantiene con millis()
unsigned long epochReferencia = 0;
unsigned long msReferencia = 0;
unsigned long ultimaSincronia = 0;

unsigned long ultimoEnvio = 0;
unsigned long ultimaLectura = 0;

// Acumuladores para el promedio del intervalo
uint32_t sumaPm25 = 0;
uint32_t sumaPm10 = 0;
float    sumaCo   = 0.0;
uint16_t nMuestras = 0;

// -- Matriz de LED de la UNO R4 WiFi: estado del dispositivo --
ArduinoLEDMatrix matriz;

// Flecha hacia arriba: enviando datos
uint8_t ICONO_ENVIANDO[8][12] = {
  { 0,0,0,0,0,1,1,0,0,0,0,0 },
  { 0,0,0,0,1,1,1,1,0,0,0,0 },
  { 0,0,0,1,1,1,1,1,1,0,0,0 },
  { 0,0,1,1,0,1,1,0,1,1,0,0 },
  { 0,0,0,0,0,1,1,0,0,0,0,0 },
  { 0,0,0,0,0,1,1,0,0,0,0,0 },
  { 0,0,0,0,0,1,1,0,0,0,0,0 },
  { 0,0,0,0,0,1,1,0,0,0,0,0 },
};

// X: el ultimo envio fallo
uint8_t ICONO_ERROR[8][12] = {
  { 0,0,1,1,0,0,0,0,0,1,1,0 },
  { 0,0,0,1,1,0,0,0,1,1,0,0 },
  { 0,0,0,0,1,1,0,1,1,0,0,0 },
  { 0,0,0,0,0,1,1,1,0,0,0,0 },
  { 0,0,0,0,0,1,1,1,0,0,0,0 },
  { 0,0,0,0,1,1,0,1,1,0,0,0 },
  { 0,0,0,1,1,0,0,0,1,1,0,0 },
  { 0,0,1,1,0,0,0,0,0,1,1,0 },
};

bool envioFallido = false;          // el ultimo envio no fue aceptado (201)
bool pmsSinDatos = false;           // el PMS7003 lleva mas de 10 s sin tramas validas
unsigned long ledTemporalHasta = 0; // hasta cuando se muestra el icono temporal (check)

void ledConectando() { matriz.loadSequence(LEDMATRIX_ANIMATION_WIFI_SEARCH); matriz.play(true); }
void ledCalentando() { matriz.loadSequence(LEDMATRIX_ANIMATION_HOURGLASS);   matriz.play(true); }
void ledCalibrando() { matriz.loadSequence(LEDMATRIX_ANIMATION_LOAD_CLOCK);  matriz.play(true); }
void ledEnviando()   { matriz.renderBitmap(ICONO_ENVIANDO, 8, 12); }

// Icono segun el estado actual (prioridad: sensor sin datos > envio fallido > normal)
void actualizarLed() {
  if (ledTemporalHasta != 0 && millis() < ledTemporalHasta) return;
  ledTemporalHasta = 0;
  if (pmsSinDatos)       matriz.loadFrame(LEDMATRIX_DANGER);
  else if (envioFallido) matriz.renderBitmap(ICONO_ERROR, 8, 12);
  else                   matriz.loadFrame(LEDMATRIX_CLOUD_WIFI);
}

// Marca de verificacion durante 3 s tras un envio aceptado
void ledEnvioOk() {
  matriz.loadSequence(LEDMATRIX_ANIMATION_CHECK);
  matriz.play(false);
  ledTemporalHasta = millis() + 3000;
}

// -- Watchdog -------------------------------------------------
// Espera refrescando el watchdog, para poder usar pausas mas largas que su
// tiempo maximo (5,6 s) sin que la placa se reinicie.
void refrescarWatchdog() {
#if USAR_WATCHDOG
  WDT.refresh();
#endif
}

void esperar(unsigned long ms) {
  unsigned long inicio = millis();
  while (millis() - inicio < ms) {
    refrescarWatchdog();
    delay(50);
  }
}

// -- Hora del nodo (NTP) --------------------------------------
// Devuelve el epoch UTC actual, o 0 si aun no se ha podido obtener la hora.
unsigned long ahoraUTC() {
  if (epochReferencia == 0) return 0;
  return epochReferencia + (millis() - msReferencia) / 1000UL;
}

void sincronizarHora() {
  unsigned long t = WiFi.getTime();

  if (t < EPOCH_MINIMO) {
    Serial.println("Aviso: no se pudo obtener la hora (NTP). Se enviara sin marca de tiempo.");
    return;
  }

  epochReferencia = t;
  msReferencia = millis();
  ultimaSincronia = millis();

  Serial.print("Hora sincronizada (epoch UTC): ");
  Serial.println(t);
}

// -- Calibracion del MQ7 guardada en la EEPROM ----------------
// La EEPROM de la UNO R4 esta emulada sobre flash y admite un numero limitado
// de escrituras: solo se escribe al calibrar, nunca en cada medicion.
#define EEPROM_MARCA 0x4D513701UL   // identifica un registro valido
#define EEPROM_DIR   0

struct CalibracionMQ7 {
  uint32_t marca;
  float    r0;
  uint32_t calibradoEn;
  uint32_t verificacion;
};

uint32_t verificacionDe(uint32_t marca, float r0, uint32_t calibradoEn) {
  uint32_t bits;
  memcpy(&bits, &r0, sizeof(bits));
  return marca ^ bits ^ calibradoEn;
}

bool cargarCalibracion() {
  CalibracionMQ7 c;
  EEPROM.get(EEPROM_DIR, c);

  if (c.marca != EEPROM_MARCA || c.verificacion != verificacionDe(c.marca, c.r0, c.calibradoEn)) return false;
  if (!(c.r0 > 0.01 && c.r0 < 1000.0)) return false;

  mq7R0 = c.r0;
  mq7CalibradoEn = c.calibradoEn;
  mq7R0Provisional = false;
  return true;
}

// -- Linea base movil del MQ7 ---------------------------------
void reiniciarLineaBase() {
  for (uint8_t i = 0; i < MQ7_BLOQUES; i++) rsPorHora[i] = 0;
  bloqueActual = 0;
  inicioBloque = millis();
}

// Anota la lectura actual en el bloque de esta hora (se queda con la mayor)
void anotarLineaBase(float rs) {
  if (millis() - encendidoEn < MQ7_ESTABILIZACION_MS) return;   // aun calentando
  if (rs <= 0 || rs > 10000) return;                            // lectura absurda
  if (rs > rsPorHora[bloqueActual]) rsPorHora[bloqueActual] = rs;
}

void guardarCalibracion() {
  CalibracionMQ7 c;
  c.marca = EEPROM_MARCA;
  c.r0 = mq7R0;
  c.calibradoEn = ahoraUTC();
  c.verificacion = verificacionDe(c.marca, c.r0, c.calibradoEn);

  EEPROM.put(EEPROM_DIR, c);
  mq7CalibradoEn = c.calibradoEn;
  mq7R0Provisional = false;

  Serial.println("Calibracion guardada en la EEPROM.");
}

// Recalcula R0 con el aire mas limpio de las ultimas 24 h.
// Se llama al terminar cada bloque de una hora.
void actualizarLineaBase() {
  // Las MQ7_MEJORES_HORAS resistencias mas altas (aire mas limpio)
  float mejores[MQ7_MEJORES_HORAS];
  for (uint8_t k = 0; k < MQ7_MEJORES_HORAS; k++) mejores[k] = 0;

  uint8_t conDatos = 0;

  for (uint8_t i = 0; i < MQ7_BLOQUES; i++) {
    if (rsPorHora[i] <= 0) continue;
    conDatos++;

    float v = rsPorHora[i];
    for (uint8_t k = 0; k < MQ7_MEJORES_HORAS; k++) {
      if (v > mejores[k]) {                 // insercion ordenada
        for (uint8_t j = MQ7_MEJORES_HORAS - 1; j > k; j--) mejores[j] = mejores[j - 1];
        mejores[k] = v;
        break;
      }
    }
  }

  if (conDatos < 3) return;                 // hace falta algo de historia

  uint8_t usadas = (conDatos < MQ7_MEJORES_HORAS) ? conDatos : MQ7_MEJORES_HORAS;
  float suma = 0;
  for (uint8_t k = 0; k < usadas; k++) suma += mejores[k];
  float referencia = suma / usadas;

  if (referencia <= 0) return;

  float r0Nuevo = referencia / MQ7_RATIO_AIRE_LIMPIO;
  mq7R0Provisional = (conDatos < MQ7_BLOQUES_FIABLES);

  if (mq7R0 > 0 && fabs(r0Nuevo - mq7R0) / mq7R0 < MQ7_CAMBIO_MINIMO) return;

  mq7R0 = r0Nuevo;

  Serial.print("MQ7 R0 ajustado automaticamente: ");
  Serial.print(mq7R0, 3);
  Serial.print(" kOhm (");
  Serial.print(conDatos);
  Serial.print(" h de historia");
  Serial.println(mq7R0Provisional ? ", provisional)" : ")");

  // A la EEPROM como mucho una vez al dia
  if (!mq7R0Provisional &&
      (ultimoGuardadoR0 == 0 || millis() - ultimoGuardadoR0 >= MQ7_GUARDAR_CADA_MS)) {
    guardarCalibracion();
    ultimoGuardadoR0 = millis();
  }
}

bool leerPMS7003() {

  if (Serial1.available() < PAQUETE)
    return false;

  while (Serial1.available()) {

    if (Serial1.read() == 0x42) {

      buffer[0] = 0x42;

      if (Serial1.readBytes(&buffer[1], 1) != 1)
        return false;

      if (buffer[1] != 0x4D)
        continue;

      if (Serial1.readBytes(&buffer[2], 30) != 30)
        return false;

      uint16_t suma = 0;

      for (int i = 0; i < 30; i++) {
        suma += buffer[i];
      }

      uint16_t checksum = ((uint16_t)buffer[30] << 8) | buffer[31];

      if (suma != checksum) {
        Serial.println("Checksum incorrecto.");
        return false;
      }

      pm1  = ((uint16_t)buffer[10] << 8) | buffer[11];
      pm25 = ((uint16_t)buffer[12] << 8) | buffer[13];
      pm10 = ((uint16_t)buffer[14] << 8) | buffer[15];

      return true;
    }
  }

  return false;
}

// Rs del MQ7 en kOhm a partir de la lectura analogica
float leerRsMQ7() {

  int lectura = analogRead(MQ7_PIN);

  if (lectura <= 0)
    lectura = 1;

  float voltaje = lectura * (5.0 / 1023.0);

  if (voltaje >= 4.99)
    voltaje = 4.99;

  return MQ7_RL * (5.0 - voltaje) / voltaje;
}

// Calibrar R0 en aire limpio (promedio de lecturas durante 15 s).
// guardar = true lo escribe en la EEPROM (calibracion definitiva).
void calibrarMQ7(bool guardar) {

  ledCalibrando();
  Serial.println("Calibrando MQ7 en aire limpio (15 s)...");

  float suma = 0;

  for (int i = 0; i < 60; i++) {
    suma += leerRsMQ7();
    refrescarWatchdog();
    delay(250);
  }

  mq7R0 = (suma / 60.0) / MQ7_RATIO_AIRE_LIMPIO;

  Serial.print("MQ7 R0 = ");
  Serial.print(mq7R0);
  Serial.println(" kOhm");

  if (guardar) guardarCalibracion();
  else mq7R0Provisional = true;

  Serial.println();
  actualizarLed();
}

// CO en ppm a partir de una resistencia ya medida (curva del datasheet)
float ppmDesdeRs(float rs) {

  float ratio = rs / mq7R0;

  float ppm = MQ7_CURVA_A * pow(ratio, MQ7_CURVA_B);

  if (ppm < 0)
    ppm = 0;

  return ppm;
}

// CO en ppm tomando una lectura nueva
float leerMQ7() {
  return ppmDesdeRs(leerRsMQ7());
}

void conectarWiFi() {

  ledConectando();

  while (status != WL_CONNECTED) {

    Serial.print("Intentando conectar a: ");
    Serial.println(ssid);

    refrescarWatchdog();
    status = WiFi.begin(ssid, pass);

    esperar(5000);
  }

  Serial.println("Conectado al WiFi.");
  Serial.print("IP      : ");
  Serial.println(WiFi.localIP());
  Serial.println();

  actualizarLed();
}

// Valor como texto JSON, o "null" si esta fuera del rango que acepta el
// servidor. Si se enviara fuera de rango, el servidor rechazaria la medicion
// COMPLETA con 400 (se perderian tambien los demas datos).
void textoJson(char *dest, size_t n, float valor, float maximo, int decimales) {
  if (valor >= 0 && valor <= maximo)
    dtostrf(valor, 1, decimales, dest);
  else
    strncpy(dest, "null", n);
}

void enviarAPi(unsigned int pm25Prom, unsigned int pm10Prom, float coProm, unsigned int muestras) {

  ledEnviando();
  refrescarWatchdog();

  WiFiSSLClient cliente;

  if (!cliente.connect(API_HOST, API_PORT)) {
    Serial.println("Error: no se pudo conectar a la API.");
    envioFallido = true;
    actualizarLed();
    return;
  }

  // Rangos que acepta el servidor (IngestController): pm2_5 0-1000,
  // pm10 0-2000, co 0-1000. Fuera de rango -> null.
  char pm25Texto[12], pm10Texto[12], coTexto[12];
  textoJson(pm25Texto, sizeof(pm25Texto), pm25Prom, 1000, 0);
  textoJson(pm10Texto, sizeof(pm10Texto), pm10Prom, 2000, 0);
  textoJson(coTexto,   sizeof(coTexto),   coProm,   1000, 1);

  // Hora de la medicion en el nodo; si aun no hay hora se envia null y el
  // servidor usa la de recepcion.
  char medidoTexto[16];
  unsigned long medidoEn = ahoraUTC();
  if (medidoEn > 0) snprintf(medidoTexto, sizeof(medidoTexto), "%lu", medidoEn);
  else              strncpy(medidoTexto, "null", sizeof(medidoTexto));

  char calibradoTexto[16];
  if (mq7CalibradoEn > 0) snprintf(calibradoTexto, sizeof(calibradoTexto), "%lu", mq7CalibradoEn);
  else                    strncpy(calibradoTexto, "null", sizeof(calibradoTexto));

  char r0Texto[12];
  dtostrf(mq7R0, 1, 3, r0Texto);

  // Sin sensor de temperatura/humedad: se envian como null (el servidor lo acepta)
  char json[352];
  snprintf(json, sizeof(json),
           "{\"device_key\":\"%s\",\"pm2_5\":%s,\"pm10\":%s,\"co\":%s,"
           "\"temperatura\":null,\"humedad\":null,"
           "\"n_muestras\":%u,\"measured_at\":%s,"
           "\"firmware_version\":\"%s\",\"r0_mq7\":%s,\"calibrado_en\":%s}",
           DEVICE_KEY, pm25Texto, pm10Texto, coTexto,
           muestras, medidoTexto,
           FIRMWARE_VERSION, r0Texto, calibradoTexto);

  Serial.print("Enviando: ");
  Serial.println(json);

  cliente.print("POST ");
  cliente.print(API_PATH);
  cliente.println(" HTTP/1.1");
  cliente.print("Host: ");
  cliente.println(API_HOST);
  // El firewall (ModSecurity) del hosting bloquea con 406 los POST con
  // "application/json". El servidor lee el cuerpo igual, asi que se envia
  // como texto plano.
  cliente.println("Content-Type: text/plain");
  cliente.print("Content-Length: ");
  cliente.println(strlen(json));
  cliente.println("Connection: close");
  cliente.println();
  cliente.print(json);

  unsigned long inicio = millis();

  while (cliente.available() == 0 && millis() - inicio < 5000) {
    refrescarWatchdog();
    delay(10);
  }

  if (cliente.available()) {

    String respuesta = cliente.readString();
    int codigo = respuesta.substring(9, 12).toInt();

    Serial.print("Respuesta HTTP: ");
    Serial.println(codigo);  // 201 = guardado

    envioFallido = (codigo != 201);

    // Mostrar la respuesta del servidor: con 201 indica el dispositivo y el
    // colegio a los que quedo asociada la medicion; si falla, el motivo.
    int cuerpo = respuesta.indexOf("\r\n\r\n");
    if (cuerpo >= 0) {
      Serial.print("Servidor: ");
      Serial.println(respuesta.substring(cuerpo + 4));
    }
  } else {
    Serial.println("Sin respuesta de la API.");
    envioFallido = true;
  }

  cliente.stop();

  if (envioFallido) actualizarLed();
  else              ledEnvioOk();
}

void setup() {

  Serial.begin(SERIAL_BAUD);

  // Esperar al monitor serie como maximo 3 s. En la UNO R4 WiFi "Serial"
  // solo es true con un monitor serie abierto; sin PC (alimentado con un
  // cargador) el sketch debe arrancar igual.
  unsigned long inicioSerial = millis();
  while (!Serial && millis() - inicioSerial < 3000);

  matriz.begin();

  Serial1.begin(PMS_BAUD);
  Serial1.setTimeout(100);

  pinMode(MQ7_PIN, INPUT);

  Serial.println("--------------------------------");
  Serial.println("PMS7003 + MQ7 -> API");
  Serial.print("Nodo (device_key ...");
  Serial.print(&DEVICE_KEY[strlen(DEVICE_KEY) - 6]);
  Serial.println(")");
  Serial.println("--------------------------------");

  if (WiFi.status() == WL_NO_MODULE) {
    Serial.println("Error: fallo de comunicacion con el modulo WiFi.");
    while (true);
  }

  String fv = WiFi.firmwareVersion();

  if (fv < WIFI_FIRMWARE_LATEST_VERSION) {
    Serial.println("Aviso: firmware del WiFi no actualizado.");
    Serial.println();
  }

  // WiFi.begin() espera por defecto 10 s, mas que el watchdog: se acorta
  // para que el reintento lo controle conectarWiFi().
  WiFi.setTimeout(WIFI_TIMEOUT_MS);

  // El watchdog se activa antes de las esperas largas; a partir de aqui todas
  // usan esperar(), que lo refresca.
#if USAR_WATCHDOG
  if (WDT.begin(WDT_TIMEOUT_MS)) Serial.println("Watchdog activo (5,5 s).");
  else                           Serial.println("Aviso: no se pudo activar el watchdog.");
#endif

  conectarWiFi();
  sincronizarHora();

  ledCalentando();
  Serial.println("Calentando sensores (60 s)...");
  esperar(60000);
  Serial.println("Sensores listos.");
  Serial.println();

  // R0 del MQ7: por codigo, de la EEPROM o, si no hay nada, una estimacion
  // inicial que la linea base ira corrigiendo sola durante el primer dia.
  encendidoEn = millis();
  reiniciarLineaBase();

  if (MQ7_R0_FIJO > 0) {
    mq7R0 = MQ7_R0_FIJO;
    mq7R0Provisional = false;
    Serial.print("MQ7 R0 fijado por codigo: ");
    Serial.println(mq7R0);
  } else if (cargarCalibracion()) {
    Serial.print("MQ7 R0 desde EEPROM: ");
    Serial.print(mq7R0, 3);
    Serial.println(" kOhm (se seguira ajustando solo cada hora)");
  } else {
    Serial.println("Sin calibracion guardada: estimacion inicial provisional.");
    Serial.println("Se corregira sola en las proximas 24 h.");
    calibrarMQ7(false);
  }

  actualizarLed();

  ultimaLectura = millis();
  ultimoEnvio   = millis();
}

void loop() {

  refrescarWatchdog();

  // Opcional: escribir c en el monitor serie fuerza una calibracion inmediata
  // con el aire del momento. No hace falta usarlo: el ajuste es automatico.
  if (Serial.available()) {
    char orden = Serial.read();
    if (orden == 'c' || orden == 'C') {
      calibrarMQ7(true);
      reiniciarLineaBase();
      ultimoGuardadoR0 = millis();
    }
  }

  // Volver a pedir la hora cada cierto tiempo (corrige la deriva del reloj)
  if (epochReferencia == 0 || millis() - ultimaSincronia >= NTP_RESINCRONIZAR) {
    if (WiFi.status() == WL_CONNECTED) sincronizarHora();
  }

  // Restaurar el icono de estado cuando termina el icono temporal (check)
  if (ledTemporalHasta != 0 && millis() >= ledTemporalHasta) actualizarLed();

  // Mantener el buffer del sensor siempre actualizado
  if (leerPMS7003()) {

    ultimaLectura = millis();

    if (pmsSinDatos) {
      pmsSinDatos = false;
      actualizarLed();
    }

    // Una sola lectura del sensor sirve para el promedio del minuto y para la
    // calibracion automatica
    float rsActual = leerRsMQ7();
    coPPM = ppmDesdeRs(rsActual);

    // Al terminar cada hora se recalcula R0 con el aire mas limpio de las ultimas 24 h
    anotarLineaBase(rsActual);

    if (millis() - inicioBloque >= MQ7_BLOQUE_MS) {
      bloqueActual = (bloqueActual + 1) % MQ7_BLOQUES;
      rsPorHora[bloqueActual] = 0;
      inicioBloque = millis();
      actualizarLineaBase();
    }

    // Acumular para el promedio del intervalo
    sumaPm25 += pm25;
    sumaPm10 += pm10;
    sumaCo   += coPPM;
    nMuestras++;

    // Mostrar cada lectura en el monitor serie (aprox. 1 por segundo)
    Serial.print("PM1.0: ");
    Serial.print(pm1);
    Serial.print(" ug/m3 | PM2.5: ");
    Serial.print(pm25);
    Serial.print(" ug/m3 | PM10: ");
    Serial.print(pm10);
    Serial.print(" ug/m3 | CO: ");
    Serial.print(coPPM, 1);
    Serial.println(" ppm");

    // Reconectar WiFi si se perdió
    if (WiFi.status() != WL_CONNECTED) {
      Serial.println("WiFi desconectado. Reconectando...");
      status = WL_IDLE_STATUS;
      conectarWiFi();
    }

    // Enviar el promedio del intervalo cada INTERVALO_ENVIO
    if (millis() - ultimoEnvio >= INTERVALO_ENVIO) {

      ultimoEnvio = millis();

      // PM redondeado a entero (resolucion del PMS7003), CO con decimales
      unsigned int pm25Prom = (sumaPm25 + nMuestras / 2) / nMuestras;
      unsigned int pm10Prom = (sumaPm10 + nMuestras / 2) / nMuestras;
      float coProm = sumaCo / nMuestras;

      Serial.print("Promedio de ");
      Serial.print(nMuestras);
      Serial.println(" lecturas:");

      enviarAPi(pm25Prom, pm10Prom, coProm, nMuestras);
      Serial.println();

      sumaPm25 = 0;
      sumaPm10 = 0;
      sumaCo   = 0.0;
      nMuestras = 0;
    }
  }

  // Avisar si el PMS7003 lleva 10 s sin entregar datos validos
  if (millis() - ultimaLectura >= 10000) {

    ultimaLectura = millis();

    Serial.print("Sin datos del PMS7003 (bytes en buffer: ");
    Serial.print(Serial1.available());
    Serial.println("). Revisa: TX del sensor -> D0, RX -> D1, VCC 5V.");

    if (!pmsSinDatos) {
      pmsSinDatos = true;
      actualizarLed();
    }
  }
}

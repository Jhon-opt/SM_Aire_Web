// Plantilla de credenciales. Copiar este archivo como secretos.h y completar.
// secretos.h queda fuera del repositorio (ver .gitignore).
//
//   SECRET_SSID / SECRET_PASS : red WiFi del sitio donde se instala el nodo.
//   DEVICE_KEY                : clave de ESTE nodo (64 caracteres hexadecimales).
//                               Cada nodo lleva la suya: es lo unico que le dice
//                               al servidor a que institucion pertenece la medicion.
//                               Se genera al dar de alta el nodo en la base de datos.

#define SECRET_SSID "NOMBRE_DE_LA_RED"
#define SECRET_PASS "CONTRASENA_DE_LA_RED"
#define DEVICE_KEY  "CLAVE_DE_64_CARACTERES_HEXADECIMALES"

# Tinku

Tinku significa encuentro en quechua. Plataforma para vivir experiencias con personas locales: una cena en su casa, una clase de cocina, un paseo por su pueblo, un taller.

Este repositorio contiene la aplicación **Laravel 13 + PostgreSQL** (en la raíz) y el **prototipo estático** original en `prototipo/`, que sirve como referencia visual.

## Puesta en marcha

Requisitos: PHP 8.3 o superior con `pdo_pgsql`, Composer, Node 22, PostgreSQL 16.

```bash
composer install
npm install
cp .env.example .env            # ajustá DB_HOST, DB_PORT, DB_USERNAME y DB_PASSWORD
php artisan key:generate
createdb tinku                  # o creala desde psql
php artisan migrate --seed      # carga planes, categorías y datos demo (no en producción)
npm run build                   # o npm run dev mientras desarrollás
php artisan serve
```

Cuentas demo (contraseña `password`): `admin@tinku.test` (administradora), `marta@tinku.test` (anfitriona nivel 3), `lucia@tinku.test` (participante nivel 2).

Tests: `php artisan test` (usa la base `tinku_test` en PostgreSQL, configurada en `phpunit.xml`). Formato: `vendor/bin/pint`.

## Qué hay implementado

| Área | Dónde | Qué hace |
| --- | --- | --- |
| Modelo de datos | `database/migrations` | Usuarios con roles y nivel de verificación, verificaciones de identidad, perfiles de anfitrión, planes y suscripciones, categorías, experiencias con fechas y cupos, reservas con desglose congelado, opiniones. Restricción en base para que un cupo nunca se sobrevenda. |
| Verificación de identidad | `app/Services/VerificationService.php` | Niveles acumulativos, proveedor según el país del documento (RENAPER para Argentina, Metamap para el resto), códigos de seis dígitos para email y teléfono, hash del documento para evitar duplicados, aprobación manual del nivel 3 que activa al anfitrión y publica sus experiencias. |
| Reservas | `app/Services/BookingService.php` | Solicitud con bloqueo de fila, cálculo de tarifa de servicio y comisión del plan, confirmar, rechazar, cancelar y completar liberando cupos. Exige nivel 2. |
| Web pública | `app/Http/Controllers`, `resources/views` | Landing con filtros, detalle de experiencia, login y registro con nacionalidad. |
| Livewire | `app/Livewire` | Panel de reserva en el detalle y alta de anfitrión en cuatro pasos. |
| Centro de verificación | `/verificacion` | El usuario ve su nivel y envía cada verificación. Fuera de producción los proveedores externos se simulan. |
| Administración | `/admin/verificaciones` | Cola de verificaciones pendientes con aprobar y rechazar. Solo administradores. |

Los proveedores reales (RENAPER, Metamap, email transaccional, SMS, Mercado Pago) todavía no están conectados: los puntos de integración están marcados en los servicios.

## Prototipo estático

Está en `prototipo/` y se abre directamente en el navegador.

### Páginas

| Archivo | Qué muestra |
| --- | --- |
| `index.html` | Landing: hero, buscador, grilla de experiencias con filtros por categoría, tipos de experiencia, simulador de ingresos del anfitrión por plan, planes Free / Impulso / Pro, testimonios y camino del anfitrión. Diseño con header de vidrio, gradientes y animaciones al hacer scroll. |
| `experiencia.html?id=…` | Detalle de una experiencia: descripción, qué incluye, anfitrión, opiniones, condiciones y panel de reserva con cálculo de total y solicitud simulada. |
| `registro.html?plan=…` | Alta de anfitrión en 4 pasos (Registrate, Verificá, Publicá, Recibí) con validación y resumen previo a publicar. |

Las fotos se cargan desde Unsplash y las tipografías desde Google Fonts, así que hace falta conexión para verlas.

## Modelo de negocio

- El anfitrión define precio y cupos. El participante paga dentro de la plataforma.
- La plataforma descuenta una comisión por reserva según el plan: Free 18 %, Impulso 12 %, Pro 8 %.
- Los planes pagos tienen suscripción mensual y comisión más baja; convienen cuando hay más reservas.
- El participante paga además una tarifa de servicio del 5 % sobre el subtotal.

## Usuarios, roles e identidad

**Una sola cuenta, varios roles.** Una persona puede ser anfitriona y participante a la vez, así que no hay dos tipos de cuenta sino un usuario con roles:

| Rol | Quién es | Qué puede hacer |
| --- | --- | --- |
| Participante | Cualquier persona registrada | Buscar, reservar, pagar, opinar. |
| Anfitrión | Un participante que activó su perfil de anfitrión | Publicar experiencias, confirmar reservas, cobrar. |
| Administrador | El equipo de Tinku | Revisar verificaciones, moderar, resolver disputas, ver métricas. |

**La identidad es el eje de la seguridad.** Cada cuenta tiene un nivel de verificación acumulativo, visible en el perfil, y cada acción exige un nivel mínimo:

| Nivel | Cómo se obtiene | Habilita |
| --- | --- | --- |
| 1 · Contacto | Email y teléfono confirmados con código | Navegar y guardar favoritas. |
| 2 · Documento | Documento de identidad validado más selfie con prueba de vida. Argentinos: DNI contra RENAPER. Extranjeros: pasaporte o documento nacional validado por un proveedor internacional (lectura de la zona MRZ, chip NFC cuando lo tiene, detección de fraude) | Reservar y pagar. Publicar como anfitrión en modo revisión. |
| 3 · Identidad y domicilio | Nivel 2 más comprobante de domicilio del lugar donde recibe, y videollamada o visita si el administrador lo pide | Publicar y cobrar sin restricciones. Insignia en el perfil. |

**Nacionalidades.** Los participantes serán en gran parte turistas, así que la verificación no puede depender de un registro nacional. La regla es: un solo flujo de verificación para el usuario, con el validador elegido según el país del documento.

- Argentina: RENAPER, que devuelve coincidencia de datos y foto.
- Resto del mundo: un proveedor de KYC con cobertura global (por ejemplo Metamap, Sumsub, Veriff u Onfido). Validan más de 200 países, hacen prueba de vida y detectan documentos adulterados.
- Se guarda el país emisor y el tipo de documento, y el nivel alcanzado es el mismo sin importar la nacionalidad.
- Para anfitriones extranjeros residentes, el nivel 3 exige además comprobante de residencia en el país donde reciben.

Reglas complementarias:

- Un administrador aprueba manualmente el paso a nivel 3 y puede bajar el nivel o suspender la cuenta.
- Los pagos salen solo a una cuenta bancaria cuyo titular coincide con el documento validado.
- Las opiniones solo las dejan participantes que asistieron a una reserva pagada.
- Los datos sensibles (documento, selfie, domicilio) se guardan cifrados y con acceso auditado; el prototipo no los recolecta.

Cada anfitrión muestra su insignia de nivel en las tarjetas y en el detalle de la experiencia.

## Marca

El manual de marca está en https://claude.ai/artifact/1W2nbDTmxWopzWG8hiC2PA. En el código:

- Colores, tipografías, radios y espaciado: variables al principio de `resources/css/tinku.css`, con los mismos nombres que el manual.
- Logos: `public/brand/`, copiados sin modificar.
- Íconos: Phosphor Bold en `resources/icons/` (licencia MIT), con el componente `<x-icon name="..." />`.

## Stack

- PHP 8.4 con Laravel 13, vistas Blade con Livewire y Tailwind.
- PostgreSQL 16.
- Panel de administración y del anfitrión hechos a medida con Blade y Livewire.
- Laravel Cashier para las suscripciones de los planes y SDK de Mercado Pago para los cobros por reserva.

## Próximos pasos

1. Conectar RENAPER y un proveedor de KYC internacional, más email transaccional y SMS para los códigos.
2. Pasarela de pagos (Mercado Pago) con autorización al solicitar y captura al confirmar.
3. Panel del anfitrión: reservas pendientes, calendario de fechas, liquidaciones y estadísticas.
4. Panel del participante: mis reservas, cancelación y opiniones.
5. Búsqueda por fecha con disponibilidad real y subida de fotos propias.
6. Reemplazar las fotos de stock de los datos demo por fotos reales de cada anfitrión, como pide el manual de marca.
7. Tema oscuro: el manual ya define sus colores.

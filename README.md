# Tinku · Prototipo

Tinku significa encuentro en quechua. Plataforma para vivir experiencias con personas locales: una cena en su casa, una clase de cocina, un paseo por su pueblo, un taller.

Este repositorio contiene un **prototipo navegable** en HTML, CSS y JavaScript puro, sin dependencias ni build. Los datos (experiencias, planes, opiniones) son demostrativos y viven en `js/data.js`.

## Páginas

| Archivo | Qué muestra |
| --- | --- |
| `index.html` | Landing: hero, buscador, grilla de experiencias con filtros por categoría, tipos de experiencia, simulador de ingresos del anfitrión por plan, planes Free / Impulso / Pro, testimonios y camino del anfitrión. Diseño con header de vidrio, gradientes y animaciones al hacer scroll. |
| `experiencia.html?id=…` | Detalle de una experiencia: descripción, qué incluye, anfitrión, opiniones, condiciones y panel de reserva con cálculo de total y solicitud simulada. |
| `registro.html?plan=…` | Alta de anfitrión en 4 pasos (Registrate, Verificá, Publicá, Recibí) con validación y resumen previo a publicar. |

## Cómo verlo

Abrí `index.html` directamente en el navegador, o serví la carpeta:

```bash
python3 -m http.server 8000
# http://localhost:8000
```

Las fotos se cargan desde Unsplash y las tipografías desde Google Fonts, así que hace falta conexión para verlas.

## Modelo de negocio representado

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

En el prototipo, cada anfitrión muestra su insignia de nivel en las tarjetas y en el detalle de la experiencia.

## Stack propuesto para la versión real

- PHP 8.3 con Laravel 12, vistas Blade con Livewire y Tailwind.
- PostgreSQL 16.
- Panel de administración y del anfitrión hechos a medida con Blade y Livewire.
- Laravel Cashier para las suscripciones de los planes y SDK de Mercado Pago para los cobros por reserva.

## Próximos pasos sugeridos

1. Backend y base de datos para anfitriones, experiencias, fechas y reservas.
2. Autenticación con roles y verificación de identidad por niveles (RENAPER para argentinos, proveedor de KYC internacional para extranjeros, prueba de vida, domicilio).
3. Pasarela de pagos (Mercado Pago) con retención hasta la confirmación del anfitrión.
4. Búsqueda por ciudad y fecha con disponibilidad real.
5. Panel del anfitrión: calendario, reservas, liquidaciones y estadísticas.

# El Anfitrión · Prototipo

Plataforma para vivir experiencias con personas locales: una cena en su casa, una clase de cocina, un paseo por su pueblo, un taller.

Este repositorio contiene un **prototipo navegable** en HTML, CSS y JavaScript puro, sin dependencias ni build. Los datos (experiencias, planes, opiniones) son demostrativos y viven en `js/data.js`.

## Páginas

| Archivo | Qué muestra |
| --- | --- |
| `index.html` | Landing: hero, buscador, grilla de experiencias con filtros por categoría, tipos de experiencia, simulador de ingresos del anfitrión por plan, planes Free / Impulso / Pro, testimonios y camino del anfitrión. |
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

## Próximos pasos sugeridos

1. Backend y base de datos para anfitriones, experiencias, fechas y reservas.
2. Autenticación y verificación de identidad real.
3. Pasarela de pagos (Mercado Pago) con retención hasta la confirmación del anfitrión.
4. Búsqueda por ciudad y fecha con disponibilidad real.
5. Panel del anfitrión: calendario, reservas, liquidaciones y estadísticas.

# PROMPT MAESTRO — CREAR PLATAFORMA E-COMMERCE CASTAMOTO

Quiero desarrollar una plataforma web profesional de comercio electrónico y marketplace llamada **CASTAMOTO**, orientada inicialmente a la venta de productos y servicios relacionados con motocicletas.

La plataforma debe tomar como referencia las funcionalidades y experiencia de usuario de grandes marketplaces como Mercado Libre, pero **NO debe copiar su diseño, código, logotipo, textos ni identidad visual**. Debe tener una identidad propia basada en el logo proporcionado de CASTAMOTO.

## 1. IDENTIDAD VISUAL

Utiliza el logo proporcionado como referencia principal.

La identidad visual debe estar basada en:

- Negro como color principal.
- Amarillo/dorado como color de énfasis.
- Blanco para textos y fondos secundarios.
- Gris oscuro para superficies y componentes secundarios.
- Bordes ligeramente redondeados.
- Sombras modernas y discretas.
- Diseño profesional, tecnológico y relacionado con motocicletas.
- Interfaz moderna y limpia.
- Excelente contraste y accesibilidad.
- Animaciones suaves, sin exagerar.

La plataforma debe transmitir:

- Confianza.
- Seguridad.
- Velocidad.
- Tecnología.
- Profesionalismo.
- Facilidad para comprar.
- Identidad relacionada con motocicletas.

El diseño debe ser completamente responsive:

- Computadores.
- Portátiles.
- Tablets.
- Teléfonos Android.
- iPhone.
- Diferentes tamaños de pantalla.

Mobile-first cuando sea posible.

---

# 2. OBJETIVO PRINCIPAL

Construir una plataforma donde los usuarios puedan:

- Registrarse.
- Iniciar sesión.
- Buscar productos.
- Buscar servicios.
- Ver categorías.
- Ver vendedores.
- Ver tiendas.
- Ver productos.
- Ver servicios.
- Agregar productos al carrito.
- Agregar servicios al carrito cuando corresponda.
- Comprar.
- Seleccionar método de pago.
- Seleccionar dirección de entrega.
- Consultar pedidos.
- Consultar estados de pedidos.
- Marcar productos como favoritos.
- Compartir productos.
- Recibir notificaciones.
- Recibir correos.
- Consultar historial de compras.
- Calificar productos y servicios.
- Comunicarse con vendedores cuando corresponda.
- Consultar el estado de sus pedidos.
- Utilizar una IA para buscar productos y ayudar a realizar pedidos.

La arquitectura debe permitir posteriormente convertir CASTAMOTO en un marketplace donde múltiples vendedores puedan administrar sus propios productos y servicios.

---

# 3. TECNOLOGÍAS

El proyecto debe utilizar principalmente:

## Backend

- PHP 8.2+ o una versión estable moderna.
- MySQL/MariaDB.
- PDO o una capa ORM segura.
- API REST.
- JSON.
- JWT o sistema de autenticación seguro basado en tokens/sesiones.
- Composer.

## Frontend

Utilizar:

- HTML5.
- CSS3.
- JavaScript moderno.
- AJAX / Fetch API.
- Bootstrap, Tailwind CSS o una combinación apropiada.
- Librerías JavaScript únicamente cuando realmente aporten valor.

El frontend debe estar separado completamente del backend.

No mezclar indiscriminadamente HTML, SQL y lógica de negocio dentro de los mismos archivos PHP.

---

# 4. ARQUITECTURA

Utilizar una arquitectura profesional.

Preferiblemente:

**Arquitectura Hexagonal / Clean Architecture + principios MVC**

Separar claramente:

```text
Frontend
    ↓
API REST
    ↓
Controllers
    ↓
Application / Use Cases
    ↓
Domain
    ↓
Repositories
    ↓
Database
```

Organizar el proyecto de manera similar a:

```text
/backend
    /config
    /public
    /routes
    /src
        /Domain
        /Application
        /Infrastructure
        /Presentation
        /Controllers
        /Services
        /Repositories
        /DTO
        /Validators
        /Middleware
        /Exceptions
    /database
        /migrations
        /seeders
    /storage
    /tests

/frontend
    /assets
    /css
    /js
    /components
    /pages
    /services
    /utils
    /views

/docs
```

La estructura puede modificarse si existe una alternativa técnicamente mejor, pero siempre debe mantenerse una separación clara de responsabilidades.

---

# 5. PRINCIPIOS DE PROGRAMACIÓN

Aplicar:

- SOLID.
- DRY.
- KISS cuando corresponda.
- Separation of Concerns.
- Dependency Injection.
- Repository Pattern.
- Service Layer.
- DTO.
- Value Objects cuando sean útiles.
- Validadores.
- Middleware.
- Manejo centralizado de excepciones.
- Logs.
- Configuración mediante variables de entorno.
- Código reutilizable.
- Código legible.
- Código mantenible.

Evitar:

- Código duplicado.
- Consultas SQL mezcladas con HTML.
- Contraseñas dentro del código.
- API keys dentro del frontend.
- Variables globales innecesarias.
- Archivos gigantes con cientos o miles de líneas.
- Funciones que hagan demasiadas cosas.
- Código sin validación.
- SQL construido directamente con datos del usuario.

---

# 6. SEGURIDAD

La seguridad debe ser una prioridad desde el comienzo.

Implementar protección contra:

- SQL Injection.
- XSS.
- CSRF.
- Session Fixation.
- Session Hijacking.
- Brute Force.
- Credential Stuffing.
- Manipulación de parámetros.
- IDOR.
- Subida de archivos maliciosos.
- Robo de tokens.
- Escalamiento de privilegios.
- Acceso no autorizado a endpoints.
- Manipulación de precios.
- Manipulación de stock.
- Manipulación de pedidos.
- Manipulación del estado de pagos.

Utilizar:

- Prepared Statements.
- PDO con parámetros.
- Password hashing con `password_hash()`.
- Verificación con `password_verify()`.
- Validación de entrada en backend.
- Sanitización cuando corresponda.
- Rate limiting.
- CORS correctamente configurado.
- Headers de seguridad.
- Cookies `HttpOnly`.
- Cookies `Secure` cuando se utilice HTTPS.
- `SameSite`.
- Expiración de sesiones/tokens.
- Rotación de tokens cuando corresponda.
- Permisos y roles.
- Middleware de autenticación.
- Middleware de autorización.

Nunca confiar únicamente en las validaciones del frontend.

Todo dato recibido del navegador debe considerarse potencialmente malicioso.

---

# 7. USUARIOS Y AUTENTICACIÓN

Crear sistema completo de usuarios.

Registro:

- Nombre.
- Apellido.
- Correo.
- Teléfono.
- Contraseña.
- Confirmación de contraseña.
- Aceptación de términos.
- Fecha de registro.

Login:

- Correo.
- Contraseña.
- Recordar sesión opcional.
- Cierre de sesión.

Implementar:

- Recuperación de contraseña.
- Cambio de contraseña.
- Verificación de correo.
- Gestión de sesiones.
- Protección contra intentos excesivos.
- Historial de accesos cuando sea necesario.

Preparar el sistema para:

- Cliente.
- Vendedor.
- Administrador.
- Superadministrador.

El sistema debe utilizar autorización basada en roles y permisos.

---

# 8. PERFIL DEL USUARIO

El usuario debe disponer de un dashboard.

Debe poder administrar:

- Información personal.
- Foto.
- Teléfono.
- Correo.
- Contraseña.
- Direcciones.
- Métodos de contacto.
- Favoritos.
- Pedidos.
- Servicios contratados.
- Historial de compras.
- Notificaciones.
- Configuración.

---

# 9. DIRECCIONES

Crear módulo de direcciones.

Campos:

- Nombre del destinatario.
- Teléfono.
- País.
- Departamento/Estado.
- Ciudad.
- Dirección.
- Complemento.
- Código postal.
- Referencia.
- Dirección principal.

Permitir:

- Crear.
- Editar.
- Eliminar.
- Seleccionar dirección principal.

---

# 10. CATÁLOGO

Crear catálogo profesional.

Cada producto debe poder tener:

- Nombre.
- Slug.
- Descripción.
- Descripción corta.
- Categoría.
- Subcategoría.
- Marca.
- SKU.
- Código interno.
- Precio.
- Precio anterior.
- Descuento.
- Impuestos si corresponde.
- Stock.
- Stock mínimo.
- Estado.
- Imágenes.
- Video opcional.
- Peso.
- Dimensiones.
- Variantes.
- Atributos.
- Garantía.
- Información adicional.

---

# 11. PRODUCTOS

Crear página de producto moderna.

Debe incluir:

- Galería de imágenes.
- Zoom de imágenes.
- Nombre.
- Precio.
- Precio anterior.
- Descuento.
- Stock disponible.
- Variantes.
- Descripción.
- Características.
- Especificaciones.
- Garantía.
- Información del vendedor.
- Calificación.
- Opiniones.
- Productos relacionados.
- Productos similares.
- Botón comprar.
- Botón agregar al carrito.
- Botón favorito.
- Botón compartir.

Mostrar claramente:

**Disponible**

**Últimas unidades**

**Agotado**

según corresponda.

---

# 12. SERVICIOS

La plataforma no debe limitarse a productos físicos.

Debe soportar también servicios.

Ejemplos:

- Mantenimiento de motocicletas.
- Cambio de aceite.
- Lavado.
- Reparaciones.
- Instalaciones.
- Diagnóstico.
- Servicios especializados.

Un servicio puede tener:

- Nombre.
- Descripción.
- Precio.
- Duración.
- Categoría.
- Imágenes.
- Profesional.
- Ubicación.
- Horarios.
- Disponibilidad.
- Estado.
- Políticas de cancelación.

Preparar la arquitectura para que posteriormente pueda existir reserva de servicios con calendario.

---

# 13. CATEGORÍAS

Crear sistema jerárquico de categorías.

Ejemplo:

```text
Motocicletas
├── Repuestos
├── Accesorios
├── Cascos
├── Guantes
├── Chaquetas
├── Llantas
├── Lubricantes
├── Electrónica
├── Herramientas
└── Servicios
```

Debe ser administrable desde el panel administrativo.

---

# 14. BÚSQUEDA

Crear buscador profesional.

Debe permitir buscar:

- Productos.
- Servicios.
- Categorías.
- Marcas.
- Vendedores.

Implementar:

- Autocompletado.
- Sugerencias.
- Corrección de términos cuando sea posible.
- Búsqueda por relevancia.
- Filtros.
- Ordenamiento.

Filtros:

- Precio mínimo.
- Precio máximo.
- Categoría.
- Marca.
- Estado.
- Ubicación.
- Calificación.
- Disponibilidad.
- Vendedor.

Ordenar por:

- Relevancia.
- Precio menor.
- Precio mayor.
- Más vendidos.
- Mejor calificados.
- Más recientes.

---

# 15. INTELIGENCIA ARTIFICIAL

Integrar posteriormente una IA para ayudar al usuario.

La IA debe poder entender lenguaje natural.

Ejemplos:

Usuario:

> "Necesito un casco para una moto deportiva por menos de 500 mil pesos."

La IA debe interpretar:

```text
categoría = cascos
tipo = deportivo
precio máximo = 500000
moneda = COP
```

Y consultar el catálogo real.

Otro ejemplo:

> "Necesito aceite para una Yamaha XTZ 250."

La IA debe ayudar a encontrar productos compatibles.

La IA también podría ayudar a:

- Buscar productos.
- Recomendar productos.
- Comparar productos.
- Encontrar alternativas.
- Crear un carrito.
- Consultar un pedido.
- Consultar disponibilidad.
- Responder preguntas frecuentes.

IMPORTANTE:

La IA no debe inventar productos, precios, stock ni estados de pedidos.

Debe consultar la API/backend antes de entregar información relacionada con datos reales.

La arquitectura debe permitir cambiar posteriormente el proveedor de IA sin modificar toda la aplicación.

---

# 16. FAVORITOS

Crear sistema de favoritos.

Permitir:

- Agregar producto.
- Quitar producto.
- Ver favoritos.
- Detectar si ya está guardado.
- Mostrar favoritos en el dashboard.

---

# 17. COMPARTIR

Cada producto debe tener opción para compartir.

Permitir:

- Copiar enlace.
- WhatsApp.
- Facebook.
- X/Twitter.
- Telegram.
- Compartir mediante Web Share API cuando el dispositivo lo soporte.

---

# 18. CARRITO

Crear carrito completo.

Debe permitir:

- Agregar producto.
- Eliminar.
- Cambiar cantidad.
- Actualizar automáticamente subtotal.
- Mostrar stock disponible.
- Evitar cantidades superiores al stock.
- Calcular descuentos.
- Calcular envío.
- Calcular total.
- Guardar carrito para usuarios autenticados.
- Manejar carrito de invitado.

Nunca confiar en el precio enviado por frontend.

Cuando se cree el pedido, el backend debe consultar nuevamente:

- Precio real.
- Stock real.
- Descuentos.
- Impuestos.
- Costos de envío.

---

# 19. CHECKOUT

Crear checkout profesional.

Pasos:

```text
1. Carrito
2. Dirección
3. Método de entrega
4. Método de pago
5. Confirmación
6. Pedido creado
```

Mostrar resumen:

- Productos.
- Cantidades.
- Precio.
- Descuentos.
- Envío.
- Total.

---

# 20. MÉTODOS DE PAGO

Inicialmente soportar:

### Efectivo

Permitir configurar pago en efectivo según las reglas del negocio.

### Transferencia bancaria

Mostrar información de transferencia cuando esté habilitada.

Permitir posteriormente:

- Cargar comprobante.
- Validación del comprobante.
- Estado pendiente de verificación.

### Tarjeta

Preparar integración con una pasarela de pagos.

IMPORTANTE:

La tarjeta debe manejarse mediante una pasarela de pago segura.

Nunca almacenar:

- Número completo de tarjeta.
- CVV.
- Datos sensibles de tarjeta.

La arquitectura debe ser configurable para poder agregar posteriormente diferentes proveedores de pago.

Ejemplo conceptual:

```text
PaymentGateway
      ↓
Stripe
MercadoPago
Wompi
PayU
Otro proveedor
```

El método de pago debe ser configurable desde administración.

---

# 21. CONFIGURACIÓN DE MÉTODOS DE PAGO

Esto es MUY IMPORTANTE.

No quiero que los métodos de pago estén escritos directamente en el código.

Crear una configuración administrativa donde pueda activar/desactivar:

```text
[✓] Efectivo
[✓] Transferencia bancaria
[ ] Tarjeta
```

En el futuro poder activar:

```text
[ ] Wompi
[ ] Mercado Pago
[ ] PayU
[ ] Stripe
```

El checkout solamente debe mostrar los métodos habilitados.

---

# 22. PEDIDOS

Crear sistema completo de pedidos.

Estados sugeridos:

```text
PENDIENTE
CONFIRMADO
PAGO_PENDIENTE
PAGO_CONFIRMADO
PREPARANDO
EN_CAMINO
ENTREGADO
CANCELADO
DEVUELTO
```

El administrador debe poder cambiar el estado dependiendo de los permisos.

Cada cambio debe quedar registrado.

Crear historial:

```text
Pedido creado
↓
Pago confirmado
↓
Preparando pedido
↓
En camino
↓
Entregado
```

---

# 23. NOTIFICACIONES POR CORREO

Implementar sistema de correo.

Enviar correo cuando:

- Usuario se registra.
- Se verifica correo.
- Se recupera contraseña.
- Se crea pedido.
- Se confirma pedido.
- Se confirma pago.
- Pedido entra en preparación.
- Pedido está en camino.
- Pedido es entregado.
- Pedido es cancelado.

Los correos deben tener diseño profesional basado en la identidad visual de CASTAMOTO.

---

# 24. NOTIFICACIONES PUSH

Preparar arquitectura para notificaciones push.

Permitir enviar:

- Nuevo pedido.
- Cambio de estado.
- Promociones.
- Ofertas.
- Recordatorios.
- Mensajes importantes.

Debe existir una tabla o sistema para gestionar dispositivos/tokens de notificación.

---

# 25. STOCK

Crear módulo de inventario.

Debe controlar:

- Stock actual.
- Stock reservado.
- Stock disponible.
- Stock mínimo.
- Entradas.
- Salidas.
- Ajustes.
- Historial.

Ejemplo:

```text
Stock = 20
Reservado = 3
Disponible = 17
```

Evitar vender más unidades de las disponibles.

Las operaciones de inventario deben utilizar transacciones cuando sea necesario para evitar problemas de concurrencia.

---

# 26. RESEÑAS Y CALIFICACIONES

Permitir que los usuarios califiquen:

- Productos.
- Servicios.
- Vendedores.

Sistema:

```text
★★★★★
```

Permitir comentario.

Solo usuarios que realmente hayan comprado/contratado deberían poder realizar determinadas reseñas.

Preparar moderación administrativa.

---

# 27. VENDEDOR / TIENDA

Preparar el sistema para marketplace.

Cada vendedor puede tener:

- Nombre comercial.
- Logo.
- Descripción.
- Teléfono.
- Correo.
- Ubicación.
- Calificación.
- Productos.
- Servicios.
- Estado.

Dashboard del vendedor:

- Ventas.
- Pedidos.
- Productos.
- Stock.
- Servicios.
- Clientes.
- Ingresos.
- Reportes.

---

# 28. ADMINISTRADOR

Crear panel administrativo.

Debe permitir gestionar:

- Usuarios.
- Roles.
- Permisos.
- Vendedores.
- Productos.
- Servicios.
- Categorías.
- Marcas.
- Pedidos.
- Pagos.
- Métodos de pago.
- Stock.
- Inventario.
- Reseñas.
- Cupones.
- Promociones.
- Configuración.
- Notificaciones.
- Correos.
- IA.
- Integraciones.

---

# 29. DASHBOARD

Crear dashboard moderno.

Mostrar tarjetas:

```text
Ventas
Pedidos
Usuarios
Productos
Stock bajo
Pedidos pendientes
Ingresos
```

Agregar gráficos cuando sean necesarios.

---

# 30. PROMOCIONES Y CUPONES

Preparar sistema para:

- Cupones.
- Descuentos porcentuales.
- Descuentos fijos.
- Productos en promoción.
- Categorías en promoción.
- Fechas de inicio.
- Fechas de finalización.
- Límites de uso.
- Compra mínima.

---

# 31. SEO

Preparar las páginas para SEO.

Implementar:

- URLs amigables.
- Slugs.
- Meta title.
- Meta description.
- Open Graph.
- Datos estructurados cuando corresponda.
- Sitemap.
- Robots.txt.

Ejemplo:

```text
/producto/casco-integral-xyz
/categoria/cascos
/servicio/cambio-de-aceite
/tienda/castamoto
```

---

# 32. API REST

Toda funcionalidad importante debe estar disponible mediante API.

Ejemplo:

```text
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout

GET    /api/products
GET    /api/products/{id}
POST   /api/products
PUT    /api/products/{id}
DELETE /api/products/{id}

GET    /api/categories

GET    /api/cart
POST   /api/cart/items
PUT    /api/cart/items/{id}
DELETE /api/cart/items/{id}

POST   /api/orders
GET    /api/orders
GET    /api/orders/{id}

GET    /api/favorites
POST   /api/favorites
DELETE /api/favorites/{id}

GET    /api/notifications

POST   /api/ai/search
POST   /api/ai/order-assistant
```

Utilizar códigos HTTP correctos.

---

# 33. SWAGGER / OPENAPI

El backend debe tener documentación de API utilizando Swagger/OpenAPI.

Documentar:

- Endpoints.
- Parámetros.
- Respuestas.
- Errores.
- Autenticación.
- DTO.
- Ejemplos.

La documentación debe permitir probar los endpoints.

---

# 34. BASE DE DATOS

Diseñar una base de datos normalizada y escalable.

Como mínimo contemplar entidades similares a:

```text
users
roles
permissions
user_roles

addresses

categories
brands

products
product_images
product_variants
product_attributes

services
service_images

stores
store_products
store_services

favorites

carts
cart_items

orders
order_items
order_status_history

payments
payment_methods
payment_transactions

inventory
inventory_movements

reviews

coupons
promotions

notifications
push_tokens

email_logs

ai_conversations
ai_messages
```

Agregar las tablas que sean necesarias.

Crear:

- Primary keys.
- Foreign keys.
- Índices.
- Unique constraints.
- Timestamps.
- Soft delete cuando sea apropiado.

---

# 35. TRANSACCIONES

Las operaciones críticas deben utilizar transacciones.

Especialmente:

- Creación de pedidos.
- Descuento de stock.
- Reserva de stock.
- Pago.
- Cancelación.
- Devolución.
- Cambios críticos del inventario.

No permitir que un pedido quede creado correctamente pero el stock quede incorrecto por una operación parcial.

---

# 36. LOGS Y AUDITORÍA

Crear sistema de logs.

Registrar operaciones importantes:

- Login.
- Cambios de contraseña.
- Cambios de permisos.
- Cambios de precios.
- Cambios de stock.
- Cambios de pedidos.
- Cambios de estados.
- Operaciones administrativas.

No registrar información extremadamente sensible.

---

# 37. MANEJO DE ERRORES

Crear respuestas API consistentes.

Ejemplo:

```json
{
    "success": false,
    "message": "No se pudo completar la operación.",
    "errors": []
}
```

Errores específicos deben manejarse de forma controlada.

Nunca mostrar:

- Stack trace.
- Contraseñas.
- Credenciales.
- SQL.
- Información interna del servidor.

En producción debe existir un modo seguro de errores.

---

# 38. CONFIGURACIÓN

Utilizar variables de entorno:

```text
.env
```

Ejemplo:

```text
APP_ENV=
APP_URL=

DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

JWT_SECRET=

MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=

PAYMENT_PROVIDER=
PAYMENT_PUBLIC_KEY=
PAYMENT_SECRET_KEY=

AI_PROVIDER=
AI_API_KEY=

PUSH_PROVIDER=
```

Nunca subir `.env` al repositorio.

Crear:

```text
.env.example
```

sin credenciales reales.

---

# 39. FRONTEND

El frontend debe estar desacoplado.

Debe comunicarse con el backend exclusivamente mediante API.

Crear servicios JavaScript como:

```text
authService
productService
categoryService
cartService
orderService
paymentService
favoriteService
notificationService
aiService
```

Crear componentes reutilizables.

---

# 40. COMPONENTES VISUALES

Crear componentes como:

- Header.
- Navbar.
- Buscador.
- Menú de categorías.
- ProductCard.
- ServiceCard.
- ProductGallery.
- Rating.
- FavoriteButton.
- ShareButton.
- CartDrawer.
- Modal.
- Toast.
- Pagination.
- Filters.
- Loading.
- EmptyState.
- ErrorState.
- OrderTimeline.
- NotificationCenter.

---

# 41. HOME

La página principal debe ser visualmente impactante.

Incluir:

- Header.
- Logo CASTAMOTO.
- Buscador grande.
- Categorías.
- Productos destacados.
- Ofertas.
- Productos más vendidos.
- Servicios destacados.
- Tiendas/vendedores destacados.
- Banner promocional.
- Sección de confianza.
- Footer.

El diseño debe sentirse como una plataforma comercial profesional.

---

# 42. EXPERIENCIA DE USUARIO

Aplicar buenas prácticas UX.

El usuario debe poder llegar desde:

```text
Inicio
↓
Categoría
↓
Producto
↓
Carrito
↓
Checkout
↓
Pago
↓
Pedido
```

sin procesos innecesariamente complicados.

Utilizar:

- Feedback visual.
- Loading states.
- Skeleton loaders.
- Confirmaciones.
- Mensajes claros.
- Validación inmediata.
- Diseño responsive.

---

# 43. RENDIMIENTO

Optimizar:

- Imágenes.
- Lazy loading.
- JavaScript.
- CSS.
- Consultas SQL.
- Índices.
- Paginación.
- Cache cuando corresponda.
- API.

No cargar cientos de productos simultáneamente.

Utilizar paginación.

---

# 44. IMÁGENES Y ARCHIVOS

Crear sistema seguro de subida.

Validar:

- Extensión.
- MIME type.
- Tamaño.
- Nombre.
- Dimensiones.

No permitir ejecutar archivos subidos.

Separar almacenamiento de archivos del código ejecutable.

---

# 45. RESPONSIVE

La aplicación debe funcionar correctamente desde:

```text
320px
375px
390px
414px
768px
1024px
1280px
1440px+
```

No debe existir scroll horizontal accidental.

Los botones y controles deben ser cómodos para dispositivos táctiles.

---

# 46. ACCESIBILIDAD

Aplicar:

- HTML semántico.
- Labels.
- ARIA cuando sea necesario.
- Contraste adecuado.
- Navegación mediante teclado.
- Focus visible.
- Textos alternativos para imágenes.

---

# 47. TESTING

Preparar pruebas:

- Unitarias.
- Integración.
- API.
- Autenticación.
- Carrito.
- Pedidos.
- Inventario.
- Pagos.
- Permisos.

Especialmente probar:

```text
Usuario no autenticado
Usuario autenticado
Administrador
Vendedor
Producto sin stock
Producto con stock
Pedido duplicado
Pago fallido
Pago exitoso
Intentos de SQL Injection
Intentos de acceso no autorizado
```

---

# 48. README

Crear un README en español que explique:

- Requisitos.
- Instalación.
- Configuración.
- Base de datos.
- Variables `.env`.
- Ejecución.
- API.
- Swagger.
- Testing.
- Producción.
- Seguridad.
- Estructura del proyecto.

---

# 49. COMENTARIOS DEL CÓDIGO

Todos los comentarios importantes deben estar en español.

Los comentarios deben explicar:

- Por qué se realiza una operación.
- Reglas de negocio.
- Seguridad.
- Decisiones arquitectónicas.
- Procesos complejos.

NO llenar el código de comentarios obvios como:

```php
// Sumar uno
$total = $total + 1;
```

Los comentarios deben aportar información real.

---

# 50. CALIDAD DEL CÓDIGO

El código debe ser:

- Legible.
- Modular.
- Mantenible.
- Escalable.
- Seguro.
- Reutilizable.
- Bien organizado.

No generar código improvisado solamente para que "funcione".

Antes de implementar cada módulo analizar cómo encaja dentro de la arquitectura general.

---

# 51. PREPARACIÓN PARA EL FUTURO

La plataforma debe estar preparada para agregar posteriormente:

- Aplicación móvil.
- Android.
- iOS.
- Marketplace multi-vendedor.
- Pasarelas de pago adicionales.
- Facturación electrónica.
- Integración con empresas de envío.
- Geolocalización.
- Tracking de pedidos.
- Chat comprador-vendedor.
- WhatsApp.
- IA avanzada.
- Recomendaciones personalizadas.
- Sistema de puntos.
- Membresías.
- Suscripciones.
- Publicidad.
- Cupones.
- Programa de referidos.

No implementar funcionalidades innecesarias ahora si aumentan demasiado la complejidad, pero dejar una arquitectura que permita agregarlas sin reconstruir todo el proyecto.

---

# 52. REGLA IMPORTANTE SOBRE LOS MÉTODOS DE PAGO

El sistema debe considerar desde el diseño que los métodos de pago son configurables.

Por ejemplo:

```text
Configuración administrativa

Métodos de pago

☑ Efectivo
☑ Transferencia
☐ Tarjeta
```

Si posteriormente se activa tarjeta:

```text
☑ Efectivo
☑ Transferencia
☑ Tarjeta
```

El checkout automáticamente debe mostrar las opciones activadas.

La implementación debe estar desacoplada mediante una interfaz de pago.

---

# 53. REGLA IMPORTANTE SOBRE LA IA

La IA debe ser un módulo independiente.

No colocar toda la lógica de IA dentro de productos, pedidos o usuarios.

Crear una capa similar a:

```text
AIProvider
    ↓
AIService
    ↓
ProductSearchTool
OrderTool
CartTool
ProductRecommendationTool
```

Esto permitirá cambiar el proveedor de IA posteriormente.

---

# 54. REGLA IMPORTANTE SOBRE EL PRECIO

Nunca confiar en:

```text
price
total
discount
stock
```

enviados desde JavaScript.

El backend debe recalcular todo.

Ejemplo:

```text
Frontend:
"Producto = $10.000"

Backend:
Consultar precio real
Consultar descuento
Consultar stock
Calcular subtotal
Calcular envío
Calcular impuestos
Calcular total
Crear pedido
```

---

# 55. ENTREGA DEL PROYECTO

No quiero solamente una demostración visual.

Quiero una aplicación estructurada para producción.

Construir progresivamente:

### FASE 1
Arquitectura + configuración + base de datos.

### FASE 2
Autenticación + usuarios + roles.

### FASE 3
Categorías + productos + servicios.

### FASE 4
Buscador + filtros + favoritos + compartir.

### FASE 5
Carrito + checkout.

### FASE 6
Pedidos + inventario.

### FASE 7
Métodos de pago configurables.

### FASE 8
Correos + notificaciones.

### FASE 9
Dashboard administrador.

### FASE 10
Dashboard vendedor.

### FASE 11
IA.

### FASE 12
Testing + seguridad + optimización.

---

# 56. REGLA PARA LA IMPLEMENTACIÓN

No generar todo el proyecto desordenadamente en un único archivo.

Crear archivos separados según responsabilidad.

Cada módulo debe estar claramente separado.

Antes de crear código:

1. Analizar la arquitectura.
2. Diseñar las entidades.
3. Diseñar las relaciones.
4. Crear migraciones.
5. Crear backend.
6. Crear API.
7. Documentar API.
8. Crear frontend.
9. Integrar frontend y backend.
10. Probar.
11. Revisar seguridad.
12. Optimizar.

Cuando exista una decisión técnica importante, explicar brevemente por qué se eligió.

---

# 57. RESULTADO ESPERADO

El resultado final debe ser una plataforma profesional llamada:

# CASTAMOTO

Con una experiencia de compra moderna, rápida y segura, inspirada en las mejores características de los grandes marketplaces, pero con **diseño e identidad propia**.

Debe sentirse como una plataforma comercial real y preparada para crecer.

El logo proporcionado debe utilizarse como referencia visual principal para construir la identidad.

La plataforma debe estar preparada para vender tanto:

**PRODUCTOS + SERVICIOS**

y debe tener una arquitectura suficientemente sólida para evolucionar posteriormente hacia un marketplace completo.

**IMPORTANTE:** No sacrificar seguridad, arquitectura o mantenibilidad por velocidad de desarrollo. La prioridad es construir una base profesional, escalable y segura.
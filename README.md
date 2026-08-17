# CASTAMOTO

Plataforma e-commerce y marketplace de motocicletas. Backend: Fases 1-6 completas (arquitectura, auth/roles, catálogo, búsqueda/favoritos, carrito/checkout, pedidos/inventario). Además del backend, ya existe un **frontend funcional real** (Home + productos + servicios + carrito + checkout + login/registro + panel administrativo básico) y documentación **Swagger/OpenAPI** interactiva — el prompt maestro no les asigna un número de fase propio (sección 55 solo numera hitos de backend), así que se construyeron en paralelo para que la plataforma sea usable de punta a punta, no solo una colección de endpoints. Se irá ampliando en cada fase siguiente (ver [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) y el documento de especificación `Prompt maestro — Plataforma e-commerce CASTAMOTO...md`).

## Requisitos

- PHP 8.0+ (recomendado 8.2+ — este entorno local corre sobre PHP 8.0.30 de XAMPP).
- MySQL/MariaDB (incluido en XAMPP).
- Composer.
- Servidor Apache con `mod_rewrite` habilitado (incluido en XAMPP).

## Instalación

```bash
cd backend
composer install
```

## Configuración

1. Copiar `backend/.env.example` a `backend/.env` (ya existe un `.env` con valores por defecto para este entorno local; **no se sube al repositorio**).
2. Ajustar credenciales si es necesario (por defecto: `root` sin contraseña, como en una instalación estándar de XAMPP).

Variables principales (ver `backend/.env.example` para el listado completo): `APP_ENV`, `APP_URL`, `DB_*`, `JWT_SECRET`, `AUTH_*` (intentos de login, bloqueo, TTL de verificación/recuperación), `MAIL_*`, `PAYMENT_*`, `AI_*`, `ADMIN_EMAIL`/`ADMIN_PASSWORD`, `CONTACT_WHATSAPP_NUMBER` (número real del negocio, formato internacional sin "+" ni espacios, ej. `573001234567`; vacío por defecto — mientras lo esté, el botón de WhatsApp del frontend permanece oculto en vez de mostrar un número inventado), `MAX_IMAGES_PER_CATALOG_ITEM` (máximo de fotos por producto/servicio, 6 por defecto, validado en el backend).

### Correo en desarrollo local

Si `MAIL_HOST` está vacío (caso por defecto en local), los correos de verificación y recuperación de contraseña **no se envían de verdad**: se escriben como archivos `.html` en `backend/storage/mails/` para poder abrirlos y copiar el enlace/token manualmente. Todo intento (real o simulado) queda además registrado en la tabla `email_logs`.

## Base de datos

No es necesario crear la base de datos manualmente: al ejecutar las migraciones, si `castamoto` no existe, se crea automáticamente.

```bash
cd backend
php bin/console migrate       # crea todas las tablas
php bin/console db:seed       # carga roles, permisos, métodos de pago, admin inicial, categorías y marcas
php bin/console demo:seed     # opcional: productos y servicios de MUESTRA para ver el frontend con datos reales
```

Para revertir el último lote de migraciones:

```bash
php bin/console migrate:rollback
```

`demo:seed` (`backend/database/DemoDataSeeder.php`) es distinto de `db:seed`: carga 15 productos y 5 servicios de ejemplo (con imágenes de marcador de posición generadas en SVG, ya que este entorno no tiene la extensión GD) repartidos en las categorías/marcas reales. Es contenido de demostración, no datos de sistema — por eso vive fuera de `/database/seeders` y es un comando aparte. Es seguro correrlo varias veces (no duplica por `sku`/`name`).

El usuario administrador inicial se crea con el correo/clave definidos en `.env` (`ADMIN_EMAIL` / `ADMIN_PASSWORD`). **Cambiar esta contraseña antes de cualquier entorno que no sea local.**

## Ejecución

Con Apache y MySQL corriendo desde el panel de control de XAMPP, y el proyecto ubicado en `htdocs/proyectos/castamotos`:

- **Home (frontend real):** `http://localhost/proyectos/castamotos/`
- **Documentación interactiva de la API (Swagger UI):** `http://localhost/proyectos/castamotos/api-docs/`
- Estado de la API: `http://localhost/proyectos/castamotos/api/health`

### Navegación del frontend

| Ruta amigable | Página |
|---|---|
| `/` | Home: categorías, productos y servicios destacados |
| `/productos`, `/categoria/{slug}` | Listado de productos con filtros |
| `/producto/{slug}` | Detalle de producto (galería, variantes, favorito, compartir) |
| `/servicios` | Listado de servicios |
| `/servicio/{slug}` | Detalle de servicio |
| `/carrito` | Carrito (funciona sin sesión, vía `X-Cart-Token`) |
| `/checkout` | Dirección → método de entrega → método de pago → confirmar |
| `/pedido/{numero}` | Confirmación del pedido creado |
| `/admin` | Panel con barra lateral (cajón deslizable en móvil): **Resumen** (ingresos, gráficas, top productos), pedidos (un botón por acción válida según la máquina de estados), **Reservas** (servicios agendados, ordenados por fecha), **Clientes** (registrados, con pedidos y total comprado), inventario, **Servicios**, **Productos** (crear/editar/eliminar, hasta 6 fotos c/u con giro 360°, SKU automático si se deja vacío, servicios con latitud/longitud opcional) y **Marcas** (fabricantes, ej. AKT/Bajaj — lo más parecido a "proveedores" en un marketplace). Cada sección requiere su propio permiso (`manage-orders`/`manage-users`/`manage-inventory`/`manage-services`/`manage-products`/`manage-brands`). Un admin que inicia sesión entra directo aquí. No hay pestaña de "Tiendas vendedoras" (gestión de vendedores externos del marketplace) — es la Fase 10 del prompt maestro, sin construir. |

Login/registro están en un modal accesible desde el botón del header en cualquier página (`frontend/js/components/layout.js`). Es HTML/CSS/JS plano sin build step (sección 39: "librerías JS solo cuando aporten valor real"); todas las rutas amigables se resuelven en el `.htaccess` de la raíz hacia los archivos estáticos de `frontend/pages/`.

## API

Todas las respuestas siguen el formato:

```json
{ "success": true, "message": "...", "data": {} }
```

o en caso de error:

```json
{ "success": false, "message": "...", "errors": [] }
```

Las rutas marcadas con 🔒 requieren `Authorization: Bearer <token>` (JWT obtenido en login/registro).

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/health` | Verifica que la API está operativa. |
| POST | `/api/auth/register` | Registro de usuario (rol `cliente` por defecto). |
| POST | `/api/auth/login` | Login. Body admite `remember: true` para sesión larga. |
| GET | `/api/auth/verify-email?token=` | Verifica el correo a partir del enlace enviado. |
| POST | `/api/auth/resend-verification` | Reenvía el correo de verificación. |
| POST | `/api/auth/forgot-password` | Solicita recuperación de contraseña. |
| POST | `/api/auth/reset-password` | Restablece la contraseña con el token recibido. |
| POST 🔒 | `/api/auth/logout` | Cierre de sesión (JWT sin estado: se descarta en el cliente). |
| GET 🔒 | `/api/auth/me` | Datos del usuario autenticado + roles. |
| POST 🔒 | `/api/auth/change-password` | Cambia la contraseña (requiere la actual). |
| GET 🔒 | `/api/profile` | Ver perfil. |
| PUT 🔒 | `/api/profile` | Actualizar nombre/apellido/teléfono. |
| POST 🔒 | `/api/profile/avatar` | Subir foto de perfil (`multipart/form-data`, campo `avatar`). |
| GET | `/api/media/avatars/{archivo}` | Sirve una foto de perfil ya subida. |
| GET 🔒 | `/api/addresses` | Listar direcciones del usuario. |
| POST 🔒 | `/api/addresses` | Crear dirección. |
| PUT 🔒 | `/api/addresses/{id}` | Editar dirección propia. |
| DELETE 🔒 | `/api/addresses/{id}` | Eliminar (soft delete) dirección propia. |
| PUT 🔒 | `/api/addresses/{id}/primary` | Marcar como dirección principal. |
| GET | `/api/categories` | Árbol de categorías (🔒 opcional: con permiso `manage-categories` incluye inactivas). |
| GET | `/api/categories/{slug}` | Detalle de categoría. |
| POST 🔒 | `/api/categories` | Crear categoría (`manage-categories`). |
| PUT 🔒 | `/api/categories/{id}` | Editar categoría (`manage-categories`). |
| DELETE 🔒 | `/api/categories/{id}` | Eliminar categoría (`manage-categories`). |
| GET | `/api/brands` | Listado de marcas (🔒 opcional: con `manage-brands` incluye inactivas). |
| POST/PUT/DELETE 🔒 | `/api/brands[/{id}]` | Gestión de marcas (`manage-brands`). |
| GET | `/api/products` | Listado con filtros `category_id, brand_id, min_price, max_price, search, availability, rating_min, store_id, sort, page, per_page`. |
| GET | `/api/products/{slug}` | Detalle con imágenes, variantes, atributos, `stock_status`, `is_favorite`, `canonical_url` y relacionados. |
| POST/PUT/DELETE 🔒 | `/api/products[/{id}]` | Gestión de productos (`manage-products`). |
| POST 🔒 | `/api/products/{id}/images` | Subir imagen (`multipart/form-data`, campo `image`, `is_primary` opcional). |
| DELETE 🔒 | `/api/products/{id}/images/{imageId}` | Eliminar imagen. |
| PUT 🔒 | `/api/products/{id}/images/{imageId}/primary` | Marcar imagen como principal. |
| PUT 🔒 | `/api/products/{id}/variants` | Reemplaza el conjunto completo de variantes. |
| PUT 🔒 | `/api/products/{id}/attributes` | Reemplaza el conjunto completo de atributos. |
| GET | `/api/services` | Listado con filtros `category_id, search, location, sort, page, per_page`. |
| GET | `/api/services/{slug}` | Detalle con `is_favorite` y `canonical_url`. |
| POST/PUT/DELETE 🔒 | `/api/services[/{id}]` | Gestión de servicios (`manage-services`). |
| POST/DELETE 🔒 | `/api/services/{id}/images[/{imageId}]` | Imágenes de servicio. |
| GET | `/api/media/products/{archivo}`, `/api/media/services/{archivo}` | Sirven imágenes de catálogo ya subidas. |
| GET | `/api/search?q=` | Vista previa de búsqueda global (productos, servicios, categorías, marcas) + `did_you_mean` si no hay resultados. |
| GET | `/api/search/suggestions?q=` | Autocompletado liviano (`type, id, name, slug`). |
| GET 🔒 | `/api/favorites` | Listar favoritos del usuario. |
| POST 🔒 | `/api/favorites` | Agregar favorito (`{ type: product\|service, id }`). |
| DELETE 🔒 | `/api/favorites/{type}/{id}` | Quitar favorito. |
| GET 🔒 | `/api/favorites/check?type=&id=` | `{ is_favorite: bool }`. |
| GET | `/api/cart` | Carrito actual (invitado vía header `X-Cart-Token`, o del usuario si hay JWT). |
| POST | `/api/cart/items` | Agregar `{ product_id\|service_id, quantity, scheduled_at? }`. `scheduled_at` es obligatorio si es `service_id` (sección 12: reserva) — se re-verifica que el horario siga libre recién al confirmar el checkout. |
| PUT | `/api/cart/items/{itemId}` | Cambiar cantidad. |
| DELETE | `/api/cart/items/{itemId}` | Quitar ítem. |
| DELETE | `/api/cart` | Vaciar carrito. |
| POST 🔒 | `/api/checkout` | `{ address_id, payment_method_id, delivery_method, notes? }` → crea el pedido. |
| GET 🔒 | `/api/orders/{orderNumber}` | Confirmación/detalle del pedido (solo el dueño; 404 si no es suyo). |
| GET | `/api/payment-methods` | Métodos de pago habilitados (para que el checkout sepa qué mostrar); nunca incluye `config`. |
| GET 🔒 | `/api/admin/payment-methods` | TODOS los métodos, con `config` (`manage-payment-methods`). |
| PUT 🔒 | `/api/admin/payment-methods/{id}` | `{ is_enabled, config? }` — activar/desactivar y configurar (`manage-payment-methods`, sección 21). |
| GET 🔒 | `/api/admin/orders` | Listar pedidos (`manage-orders`), filtro `status`. |
| GET 🔒 | `/api/admin/orders/{orderNumber}` | Detalle de cualquier pedido (`manage-orders`). |
| PUT 🔒 | `/api/admin/orders/{orderNumber}/status` | Cambiar estado `{ status, comment? }` (`manage-orders`), valida la transición. |
| GET 🔒 | `/api/admin/reservations` | Servicios agendados (`manage-orders`), filtros `date`, `status`, `upcoming_only`. Cambiar su estado usa el endpoint de pedidos de arriba. |
| GET 🔒 | `/api/admin/dashboard/summary` | Resumen del negocio (`manage-orders`): ingresos, pedidos por estado, serie de 14 días, top productos, stock bajo, reservas próximas. |
| GET 🔒 | `/api/admin/inventory` | Stock/reservado/disponible por producto (`manage-inventory`), filtros `search`, `low_stock`. |
| POST 🔒 | `/api/admin/inventory/{productId}/adjust` | Ajuste manual `{ type: in\|out\|adjustment, quantity, reason }` (`manage-inventory`). |
| GET 🔒 | `/api/admin/inventory/movements` | Historial de movimientos, filtro `product_id` opcional (`manage-inventory`). |

`stock_status` se calcula en cada respuesta: `agotado` (stock ≤ 0), `ultimas_unidades` (stock ≤ `min_stock`) o `disponible`. `sort` en productos/servicios acepta `relevancia` (por defecto cuando hay `search`, usa `FULLTEXT`/`MATCH...AGAINST`), `price_asc`, `price_desc`, `name`, `rating` y `best_selling` (este último sigue cayendo a "más recientes": la Fase 6 conecta inventario/reservas, no un contador de ventas).

**Carrito de invitado**: el primer `GET`/`POST` sin `X-Cart-Token` devuelve uno nuevo (`cart_token` en la respuesta) — guárdalo (ej. localStorage) y envíalo en las siguientes peticiones. Si el usuario hace login con ese header presente, el carrito de invitado se fusiona automáticamente al carrito de su cuenta.

`delivery_method` acepta `domicilio` (tarifa plana `SHIPPING_FLAT_RATE`, gratis sobre `SHIPPING_FREE_THRESHOLD`) o `recogida_tienda` (envío siempre `0`). El pedido nace en estado `PENDIENTE` con un pago inicial en `payments` con estado `pending`.

**Ciclo de vida del pedido (Fase 6)**: `PUT /api/admin/orders/{orderNumber}/status` valida la transición contra la máquina de estados de la sección 22 (`Application/Support/OrderStatusTransitions.php`) — solo se puede avanzar por los caminos válidos, nunca saltar estados ni salir de uno terminal (`CANCELADO`/`DEVUELTO`/`ENTREGADO`). Al crear el pedido se reserva stock de verdad (`inventory.stock_reserved`, sección 25); al llegar a un estado terminal la reserva se libera, y si la venta no se concretó (`CANCELADO`/`DEVUELTO`) `products.stock` se restituye — todo registrado en `inventory_movements` para auditoría.

## Swagger / OpenAPI

Documentación interactiva en `http://localhost/proyectos/castamotos/api-docs/` (Swagger UI vía CDN, sin dependencias nuevas de Composer). El spec vive en `api-docs/openapi.json` (OpenAPI 3.0.3) y cubre los ~57 endpoints reales de las Fases 1-5, con esquemas compartidos (`Product`, `Service`, `Address`, `Order`, `Cart`, etc.) y seguridad `bearerAuth` (JWT) donde aplica. Como comparte origen con la API, se puede usar "Try it out" contra el backend real: iniciar sesión en `POST /auth/login`, copiar el `token` de la respuesta y pegarlo en el botón **Authorize** (arriba a la derecha) como `Bearer <token>`.

Al agregar endpoints en las próximas fases, actualizar `api-docs/openapi.json` en el mismo cambio (no queda generado automáticamente desde el código todavía).

## Testing

```bash
cd backend
vendor/bin/phpunit
```

Incluye una prueba de humo (`tests/ArchitectureSmokeTest.php`), pruebas unitarias de `Validator`, `JwtService`, `SlugGenerator`, `DidYouMeanFinder`, `CartPricingCalculator`, `OrderNumberGenerator` y `OrderStatusTransitions`, y una prueba de integración (`AuthFlowIntegrationTest.php`) que ejercita registro → login contra la base de datos local (crea y limpia su propio usuario de prueba).

## Producción

Antes de desplegar a producción:

- Definir `APP_ENV=production` (oculta detalles internos de errores).
- Generar un `JWT_SECRET` aleatorio y largo.
- Configurar `CORS_ALLOWED_ORIGINS` con dominios específicos (no `*`).
- Cambiar la contraseña del usuario administrador inicial.
- Revisar credenciales de base de datos y correo.

## Seguridad

- Contraseñas con `password_hash()` / `password_verify()`.
- Acceso a datos exclusivamente vía PDO con sentencias preparadas.
- El backend nunca confía en precio/stock/descuentos enviados desde el frontend (se recalculan en servidor — ver `docs/ARCHITECTURE.md`).
- Manejo centralizado de errores: en `APP_ENV=production` no se exponen mensajes internos ni stack traces (`backend/src/Infrastructure/Http/Kernel.php`).
- Logs en `backend/storage/logs/` (nunca se registran contraseñas, tokens ni datos de tarjeta).
- Autenticación JWT (`AuthMiddleware`) + autorización por permisos (`RequirePermissionMiddleware`, lista para las fases de paneles admin/vendedor).
- Fuerza bruta: bloqueo temporal tras `AUTH_MAX_LOGIN_ATTEMPTS` intentos fallidos, con historial completo en `login_history`.
- Tokens de verificación de correo y recuperación de contraseña se guardan **hasheados** (`sha256`) y expiran (`EMAIL_VERIFICATION_TTL_HOURS` / `PASSWORD_RESET_TTL_MINUTES`).
- Mensajes de error de login/recuperación son genéricos a propósito, para no revelar si un correo existe en el sistema.
- Subida de avatar/imágenes de catálogo valida extensión + MIME real (`finfo`) + tamaño (`UploadedFileValidator`/`FileStorage`, compartidos), guarda fuera del docroot con nombre generado y se sirve por un endpoint controlado (`MediaController`) que valida el nombre de archivo contra path traversal.
- Los listados/detalles públicos de catálogo (categorías, marcas, productos, servicios) solo devuelven registros activos/no eliminados; solo un usuario autenticado con el permiso de gestión correspondiente ve también borradores/inactivos (`OptionalAuthMiddleware` + `AccessChecker`).
- El checkout nunca confía en precio/stock enviados por el frontend (sección 54): recalcula todo con datos en vivo y, dentro de la transacción, vuelve a bloquear (`SELECT ... FOR UPDATE`) y verificar el stock real antes de descontarlo — evita que compras concurrentes dejen el stock inconsistente (`PdoOrderRepository::createFromCheckout()`).
- La dirección usada en el checkout se valida contra el dueño real (`AddressRepositoryInterface::belongsToUser`) para evitar IDOR, igual que en la Fase 2.

## Estructura del proyecto

```
/backend
  /config        Configuración de la app y base de datos
  /public        Front controller de la API (index.php)
  /routes        Registro de rutas (routes/api.php)
  /src
    /Domain            Entidades (User) y contratos de repositorio (puertos)
    /Application       Casos de uso (Auth, Profile, Address, Catalog, Search, Favorite, Cart, Checkout, Orders), Validator, SlugGenerator, DidYouMeanFinder, CartPricingCalculator, OrderNumberGenerator, OrderStatusTransitions, subida de archivos
    /Infrastructure    Config, base de datos, HTTP, logging, Auth (JWT), Mail, Persistence (adaptadores PDO)
    /Presentation      Controllers y middleware (Auth, OptionalAuth, RequirePermission, Cors)
    /Exceptions        Excepciones de la aplicación
  /database
    /migrations       Una tabla (o alteración) por archivo
    /seeders          Datos iniciales (roles, permisos, admin, categorías, marcas)
    DemoDataSeeder.php  Datos de MUESTRA opcionales (php bin/console demo:seed), no de sistema
  /bin/console   CLI: migrate | migrate:rollback | db:seed | demo:seed
  /storage
    /logs        Logs de la aplicación
    /mails       Correos simulados en desarrollo local (driver "log")
    /uploads     Archivos subidos (avatares, imágenes de catálogo), fuera del docroot público
  /tests         Pruebas (PHPUnit)

/frontend
  /assets/img    Logo y estáticos
  /css/main.css  Sistema de diseño (negro/amarillo, responsive)
  /js
    /services    apiService (fetch + JWT + X-Cart-Token), authService, catalogService, cartService, adminService
    /components  layout (header/nav/modal login/footer), cards (ProductCard/ServiceCard)
    /utils       helpers (moneda, query params, toasts)
    /pages       un script por página (home, productos, producto, servicios, servicio, carrito, checkout, pedido, admin)
  /pages         productos.html, producto.html, servicios.html, servicio.html, carrito.html, checkout.html, pedido.html, admin.html

/api-docs        Swagger UI + openapi.json (estático, fuera del prefijo /api/)

/docs            Documentación técnica (ARCHITECTURE.md)
```

## Próximas fases

~~Fase 7: métodos de pago configurables~~ (lista, ver "Métodos de pago" en `/admin`) · Fase 8: correos + notificaciones (hoy solo hay correo de verificación/recuperación de clave — nada de confirmación de pedido, cambio de estado ni reserva) · Fase 9: resto del dashboard administrador (roles/permisos, cupones, configuración general) · Fase 10: dashboard vendedor (incluye gestión de tiendas y restricción "solo mis productos") · Fase 11: IA · Fase 12: testing + seguridad + optimización formal.

**Notas de alcance:**
- Cualquier usuario con permiso `manage-products`/`manage-services`/`manage-categories`/`manage-brands` puede gestionar todo el catálogo (no hay aún restricción "solo mi tienda") — eso se ajusta en la Fase 10.
- El checkout requiere iniciar sesión (`orders.user_id` no es nulo); se puede armar el carrito como invitado, pero confirmar el pedido no.
- Los cupones (`coupons`) no se aplican todavía en el carrito/checkout: no tienen fase asignada explícita en el prompt maestro.
- El panel `/admin` ya no es básico: Resumen, Pedidos, Reservas, Clientes, Inventario, Servicios, Productos, Marcas y Métodos de pago — falta roles/permisos y cupones/promociones (resto de la Fase 9) y todo lo de vendedor/tiendas (Fase 10).
- Tarjeta/Wompi/Mercado Pago/PayU/Stripe están arquitecturalmente listas (`PaymentGatewayInterface`, ver `docs/ARCHITECTURE.md`) pero sin credenciales reales — activarlas sin configurar sus llaves hace que el checkout las rechace con un mensaje claro en vez de fingir que cobran.

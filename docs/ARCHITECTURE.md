# Arquitectura — CASTAMOTO

## Capas (Hexagonal / Clean + MVC)

```
Frontend (estático, /frontend)
    ↓ fetch/AJAX
API REST (/api/*)
    ↓
Router (Infrastructure/Http/Router.php)
    ↓
Controllers (Presentation/Controllers)
    ↓
Application (casos de uso — se puebla desde la Fase 2 en adelante)
    ↓
Domain (entidades y reglas de negocio puras — se puebla desde la Fase 3)
    ↓
Repositories (Infrastructure — acceso a datos vía PDO)
    ↓
Database (MySQL/MariaDB)
```

## Flujo de una petición a la API

1. El navegador pide `GET /api/health`.
2. El `.htaccess` de la raíz reescribe la URL hacia `backend/public/index.php?__route=api/health` (sin depender del subdirectorio de despliegue).
3. `backend/public/index.php` inicializa `Config`, `Logger`, registra las rutas de `routes/api.php` y delega en `Kernel`.
4. `Kernel` aplica CORS, construye el `Request` desde las superglobales y llama a `Router::dispatch()`.
5. `Router` hace match del patrón (`api/health`) contra la ruta registrada y llama al controlador correspondiente.
6. El controlador responde con `Response::success()`/`Response::error()`, que siempre siguen el formato `{ success, message, data|errors }`.
7. Cualquier excepción no controlada es capturada centralmente por `Kernel`, registrada en `storage/logs` y devuelta como JSON consistente (ocultando detalles internos si `APP_ENV=production`).

## Autenticación y autorización (Fase 2)

```
Request
    ↓
Router::dispatch()
    ↓
middleware[] (en orden, ej. AuthMiddleware)
    ↓ Request enriquecido (withAttribute('auth_user', User))
Controller
```

- `AuthMiddleware` exige `Authorization: Bearer <jwt>`, lo valida con `JwtService` (firebase/php-jwt) y adjunta la entidad `User` autenticada al `Request` (inmutable: `withAttribute()` devuelve una copia).
- El payload del JWT solo lleva `sub` (id) y `roles`; los **permisos** se resuelven consultando `role_permission` en cada petición (vía `RequirePermissionMiddleware`, listo para las Fases 9/10), así un cambio de permisos no depende de que expire el token.
- Los casos de uso (`Application/UseCases`) dependen de interfaces de repositorio (`Domain/Repositories`), no de PDO directamente; los adaptadores concretos viven en `Infrastructure/Persistence`. Esto es lo que hace "hexagonal" a la arquitectura: se podría cambiar MySQL por otro motor implementando la misma interfaz, sin tocar casos de uso ni controllers.
- `Mailer` sigue el mismo patrón "intercambiable" que `AI_PROVIDER`/`PAYMENT_PROVIDER`: en local (`MAIL_HOST` vacío) escribe el correo en `/storage/mails` en vez de enviarlo, para poder probar verificación/recuperación sin SMTP real.

## Catálogo (Fase 3)

- Mismo patrón hexagonal que Auth: `Domain/Repositories/{Category,Brand,Product,Service}RepositoryInterface` → adaptadores PDO en `Infrastructure/Persistence` → UseCases en `Application/UseCases/Catalog` solo donde hay lógica real (slug único, validar categoría/marca/ciclo, subir imagen, reemplazar variantes/atributos) → Controllers.
- **Un solo endpoint público/privado por recurso**: `OptionalAuthMiddleware` intenta adjuntar el usuario si hay un JWT válido pero nunca lanza 401; el controller decide con `AccessChecker::can()` si debe incluir borradores/inactivos. Evita duplicar rutas `GET /admin/...`.
- **Slugs deterministas**: `SlugGenerator::unique()` recalcula el slug a partir del nombre en cada `create`/`update`, excluyendo el propio id de la comprobación de unicidad. Si el nombre no cambió, el slug generado coincide con el actual (no genera saltos de sufijo innecesarios); si cambió, se re-genera con sufijo `-2`, `-3`… solo si hay colisión real.
- **Reemplazo total en vez de CRUD granular** para variantes/atributos de producto (`PUT .../variants`, `PUT .../attributes`): son listas pequeñas que el formulario de edición envía completas, así que un `DELETE + INSERT` transaccional es más simple que sincronizar altas/bajas/ediciones una por una.
- **`stock_status` calculado, no almacenado**: se deriva de `stock`/`min_stock` en el momento de leer (`agotado` / `ultimas_unidades` / `disponible`), para no tener un campo que se pueda desincronizar del stock real.
- **`UploadedFileValidator` + `FileStorage`** (`Application/Support`) son compartidos entre avatares (Fase 2) e imágenes de catálogo (Fase 3): misma validación de extensión/MIME real/tamaño y el mismo patrón de guardado fuera del docroot con nombre aleatorio, sin duplicar la lógica.

## Búsqueda y favoritos (Fase 4)

- **Filtro vs. relevancia son dos cosas separadas**: el `WHERE` que decide qué filas entran usa `LIKE` palabra por palabra (`PdoProductRepository::buildSearchCondition()`) — así "casco deportivo" encuentra "Casco Integral Deportivo" aunque las palabras no sean contiguas. El **orden** cuando se pide relevancia usa `MATCH(...) AGAINST(... IN NATURAL LANGUAGE MODE)` sobre el índice `FULLTEXT` (migración `040`). Se decidió esta combinación porque `MATCH...AGAINST` solo en el `WHERE` fallaría para términos de menos de `innodb_ft_min_token_size` (3 caracteres, autocompletado incluido), y `LIKE` con la frase completa como único filtro es demasiado estricto para multi-palabra.
- **Parámetros nombrados únicos por ocurrencia**: `Connection.php` desactiva `PDO::ATTR_EMULATE_PREPARES`, así que MySQL usa *prepared statements* nativos, que no soportan reutilizar el mismo marcador con nombre más de una vez en la misma consulta (produce `SQLSTATE[HY093]`). Por eso cada palabra/columna de una búsqueda usa su propio nombre de parámetro (`search_0_name`, `search_0_sku`, …) aunque el valor bindeado sea el mismo.
- **`did_you_mean` compara contra palabras sueltas, no nombres completos**: comparar "kasko" contra "Casco Integral Deportivo" completo da una distancia de edición enorme; comparando contra la palabra "Casco" sola, `levenshtein()` la encuentra. `DidYouMeanFinder` es una función pura (sin acceso a BD); `GlobalSearchUseCase` arma el pool de palabras.
- **Favoritos reutilizan el patrón polimórfico** ya definido en la tabla `favorites` desde la Fase 1 (`favoritable_type` + `favoritable_id`); `PdoFavoriteRepository` hace dos consultas (productos y servicios) y las combina en PHP en vez de un `UNION` SQL, porque cada tipo tiene columnas propias (imagen, precio) que un `UNION` obligaría a normalizar artificialmente.

## Carrito y checkout (Fase 5)

- **Precio/stock en vivo, no el snapshot**: `cart_items.unit_price_snapshot` se guarda al agregar el ítem (referencia histórica), pero `PdoCartRepository::itemsWithLiveData()` siempre hace `JOIN` contra `products`/`services` para mostrar y calcular con el precio y el stock **actuales** — el snapshot nunca se usa para calcular totales, solo el checkout es quien decide qué precio se cobra realmente (sección 54).
- **Una sola fuente de verdad para el total**: `CartPricingCalculator::calculate()` es una función pura que usan tanto `GET /api/cart` (vista previa) como `CheckoutUseCase` (cálculo autoritativo). Si el total del carrito y el del pedido se calcularan por separado, un cambio en una de las dos fórmulas los desincronizaría silenciosamente.
- **La transacción del checkout vive en el repositorio, no en el UseCase**: `CheckoutUseCase` valida y arma los datos, pero `PdoOrderRepository::createFromCheckout()` es quien abre la transacción PDO, vuelve a bloquear cada producto con `SELECT ... FOR UPDATE` (re-verificación autoritativa de stock, no solo confía en lo leído antes de la transacción) y hace todos los `INSERT`/`UPDATE` (pedido, ítems, historial, pago, descuento de stock, `inventory_movements`, vaciar carrito). Como todos los repositorios de un mismo request comparten la misma instancia de `PDO` (`Connection::get()` es un registro por request, Fase 1), esto es seguro sin necesitar pasar la conexión explícitamente entre capas.
- **Carrito de invitado vs. autenticado**: se resuelven con la misma interfaz (`CartRepositoryInterface::resolveActiveCart(?userId, ?token)`); el token de invitado viaja en el header `X-Cart-Token` (no cookie, para no acoplarse a un dominio/frontend concreto todavía). `mergeGuestCartIntoUser()` se dispara desde `AuthController::login` (Fase 2, extendido) para no perder el carrito armado antes de iniciar sesión.
- **Envío con tarifa plana**: es un cálculo provisional (`CartPricingCalculator`) hasta que exista integración real con transportadoras (sección 51). Cambiarlo no debería afectar a `CheckoutUseCase` ni al esquema de `orders`.

## Frontend y documentación interactiva

El prompt maestro (sección 55) numera 12 fases pero ninguna se llama explícitamente "construir el frontend" — solo describe sus requisitos (secciones 39-41). Tras la Fase 5 el proyecto tenía una API completa pero ninguna página real la consumía. Se construyó un frontend funcional y Swagger en paralelo a las fases de backend, no como una fase numerada más:

- **Mismo origen, sin CORS**: el frontend se sirve desde la misma raíz que `/api/*` (`http://localhost/proyectos/castamotos/`), así que nunca hizo falta configurar CORS para desarrollo — `fetch` a rutas relativas `/api/...` funciona directo.
- **Vanilla JS, sin build step**: `<script>` clásicos en orden de dependencia (`helpers` → `apiService` → el resto de servicios → `layout`/`cards` → el script de la página). Los `const`/`function` de nivel superior de cada archivo quedan visibles para los `<script>` siguientes en el mismo HTML (mismo ámbito global del documento) — no hace falta un bundler ni módulos ES para este alcance.
- **`apiService.js` es el único que sabe hablar con el backend**: agrega `Authorization`/`X-Cart-Token` automáticamente y traduce la envoltura `{success, message, data|errors}` del backend a un valor de retorno o una excepción con `.fields`/`.status`. Todos los demás servicios (`authService`, `catalogService`, `cartService`) son adaptadores delgados sobre él.
- **URLs amigables resueltas en `.htaccess`, no en el router de PHP**: `/producto/{slug}`, `/categoria/{slug}`, etc. se reescriben a archivos HTML estáticos con querystring (`producto.html?slug=...`), consistentes con el `canonical_url` que la API ya devuelve desde la Fase 4 (sección 31). Los archivos de `frontend/pages` no necesitan saber que existe reescritura: leen el slug de `location.search`.
- **`api-docs/` vive fuera de `/api/`** a propósito: así el `RewriteRule ^api/(.*)$ ...` de la raíz nunca la intercepta y Apache la sirve como estático sin pasar por el front controller del backend.

## Por qué estas decisiones

- **Sin framework pesado (Laravel/Symfony):** el prompt maestro pide PHP "puro" con arquitectura propia. Se construyó un micro-framework interno (Router, Kernel, Migrator) suficiente para las necesidades del proyecto, sin la sobrecarga de una dependencia grande.
- **Migraciones con archivos que devuelven una instancia anónima de `Migration`:** evita depender de convenciones de nombres de clase o autoload adicional fuera de `/src`, y es fácil de leer archivo por archivo.
- **`__route` vía querystring en lugar de `PATH_INFO`:** hace que el proyecto funcione igual dentro de una subcarpeta de XAMPP (`/proyectos/castamotos/`) que en un futuro vhost dedicado, sin tener que calcular un "base path".
- **Tablas `store_products` / `store_services`:** no están en el listado literal de la sección 34 del prompt maestro, pero son necesarias para que, cuando el marketplace permita múltiples vendedores, un mismo producto pueda ofrecerse desde varias tiendas con precio/stock propios.

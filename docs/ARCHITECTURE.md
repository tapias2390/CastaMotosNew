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

## Por qué estas decisiones

- **Sin framework pesado (Laravel/Symfony):** el prompt maestro pide PHP "puro" con arquitectura propia. Se construyó un micro-framework interno (Router, Kernel, Migrator) suficiente para las necesidades del proyecto, sin la sobrecarga de una dependencia grande.
- **Migraciones con archivos que devuelven una instancia anónima de `Migration`:** evita depender de convenciones de nombres de clase o autoload adicional fuera de `/src`, y es fácil de leer archivo por archivo.
- **`__route` vía querystring en lugar de `PATH_INFO`:** hace que el proyecto funcione igual dentro de una subcarpeta de XAMPP (`/proyectos/castamotos/`) que en un futuro vhost dedicado, sin tener que calcular un "base path".
- **Tablas `store_products` / `store_services`:** no están en el listado literal de la sección 34 del prompt maestro, pero son necesarias para que, cuando el marketplace permita múltiples vendedores, un mismo producto pueda ofrecerse desde varias tiendas con precio/stock propios.

# CASTAMOTO

Plataforma e-commerce y marketplace de motocicletas. Este README refleja el estado actual del proyecto tras la **Fase 1 (Arquitectura + Configuración + Base de datos)**. Se irá ampliando en cada fase siguiente (ver [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) y el documento de especificación `Prompt maestro — Plataforma e-commerce CASTAMOTO...md`).

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

Variables principales (ver `backend/.env.example` para el listado completo): `APP_ENV`, `APP_URL`, `DB_*`, `JWT_SECRET`, `MAIL_*`, `PAYMENT_*`, `AI_*`, `ADMIN_EMAIL`/`ADMIN_PASSWORD`.

## Base de datos

No es necesario crear la base de datos manualmente: al ejecutar las migraciones, si `castamoto` no existe, se crea automáticamente.

```bash
cd backend
php bin/console migrate       # crea todas las tablas
php bin/console db:seed       # carga roles, permisos, métodos de pago y el usuario administrador inicial
```

Para revertir el último lote de migraciones:

```bash
php bin/console migrate:rollback
```

El usuario administrador inicial se crea con el correo/clave definidos en `.env` (`ADMIN_EMAIL` / `ADMIN_PASSWORD`). **Cambiar esta contraseña antes de cualquier entorno que no sea local.**

## Ejecución

Con Apache y MySQL corriendo desde el panel de control de XAMPP, y el proyecto ubicado en `htdocs/proyectos/castamotos`:

- Home (placeholder de la Fase 1): `http://localhost/proyectos/castamotos/`
- Estado de la API: `http://localhost/proyectos/castamotos/api/health`

## API

Todas las respuestas siguen el formato:

```json
{ "success": true, "message": "...", "data": {} }
```

o en caso de error:

```json
{ "success": false, "message": "...", "errors": [] }
```

Endpoint disponible en esta fase:

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/health` | Verifica que la API está operativa. |

Los endpoints de negocio (auth, catálogo, carrito, pedidos, etc.) se agregan en las fases siguientes.

## Swagger / OpenAPI

Se documentará a partir de que existan los primeros endpoints de negocio (Fase 2 en adelante).

## Testing

```bash
cd backend
vendor/bin/phpunit
```

Incluye una prueba de humo (`tests/ArchitectureSmokeTest.php`) que valida la carga de configuración y el enrutamiento.

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

## Estructura del proyecto

```
/backend
  /config        Configuración de la app y base de datos
  /public        Front controller de la API (index.php)
  /routes        Registro de rutas (routes/api.php)
  /src
    /Domain            Entidades y reglas de negocio (Fase 3+)
    /Application       Casos de uso (Fase 2+)
    /Infrastructure    Config, base de datos, HTTP, logging
    /Presentation      Controllers y middleware
    /Exceptions        Excepciones de la aplicación
  /database
    /migrations  Una tabla por archivo
    /seeders     Datos iniciales
  /bin/console   CLI: migrate | migrate:rollback | db:seed
  /storage/logs  Logs de la aplicación
  /tests         Pruebas (PHPUnit)

/frontend
  /assets, /css, /js, /components, /pages, /services, /utils, /views

/docs            Documentación técnica (ARCHITECTURE.md)
```

## Próximas fases

Fase 2: autenticación + usuarios + roles · Fase 3: categorías + productos + servicios · Fase 4: buscador + filtros + favoritos · Fase 5: carrito + checkout · Fase 6: pedidos + inventario · Fase 7: métodos de pago configurables · Fase 8: correos + notificaciones · Fase 9: dashboard administrador · Fase 10: dashboard vendedor · Fase 11: IA · Fase 12: testing + seguridad + optimización.

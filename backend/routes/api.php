<?php

declare(strict_types=1);

use App\Infrastructure\Http\Router;
use App\Presentation\Controllers\AddressController;
use App\Presentation\Controllers\AdminInventoryController;
use App\Presentation\Controllers\AdminOrderController;
use App\Presentation\Controllers\AdminReservationController;
use App\Presentation\Controllers\AuthController;
use App\Presentation\Controllers\BrandController;
use App\Presentation\Controllers\CartController;
use App\Presentation\Controllers\CategoryController;
use App\Presentation\Controllers\CheckoutController;
use App\Presentation\Controllers\DashboardController;
use App\Presentation\Controllers\FavoriteController;
use App\Presentation\Controllers\HealthController;
use App\Presentation\Controllers\MediaController;
use App\Presentation\Controllers\PaymentMethodController;
use App\Presentation\Controllers\ProductController;
use App\Presentation\Controllers\ProfileController;
use App\Presentation\Controllers\SearchController;
use App\Presentation\Controllers\ServiceController;
use App\Presentation\Controllers\SettingsController;
use App\Presentation\Middleware\AuthMiddleware;
use App\Presentation\Middleware\OptionalAuthMiddleware;
use App\Presentation\Middleware\RequirePermissionMiddleware;

/**
 * Registro central de rutas de la API. Las rutas de negocio restantes
 * (carrito, pedidos, etc.) se irán agregando aquí en las siguientes fases.
 *
 * @var Router $router
 */

$router->get('api/health', [HealthController::class, 'index']);
$router->get('api/settings/public', [SettingsController::class, 'publicSettings']);

// --- Autenticación (Fase 2) ---
$router->post('api/auth/register', [AuthController::class, 'register']);
$router->post('api/auth/login', [AuthController::class, 'login']);
$router->get('api/auth/verify-email', [AuthController::class, 'verifyEmail']);
$router->post('api/auth/resend-verification', [AuthController::class, 'resendVerification']);
$router->post('api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('api/auth/reset-password', [AuthController::class, 'resetPassword']);

$router->post('api/auth/logout', [AuthController::class, 'logout'], [new AuthMiddleware()]);
$router->get('api/auth/me', [AuthController::class, 'me'], [new AuthMiddleware()]);
$router->post('api/auth/change-password', [AuthController::class, 'changePassword'], [new AuthMiddleware()]);

// --- Perfil (Fase 2 / sección 8) ---
$router->get('api/profile', [ProfileController::class, 'show'], [new AuthMiddleware()]);
$router->put('api/profile', [ProfileController::class, 'update'], [new AuthMiddleware()]);
$router->post('api/profile/avatar', [ProfileController::class, 'uploadAvatar'], [new AuthMiddleware()]);

// --- Direcciones (Fase 2 / sección 9) ---
$router->get('api/addresses', [AddressController::class, 'index'], [new AuthMiddleware()]);
$router->post('api/addresses', [AddressController::class, 'store'], [new AuthMiddleware()]);
$router->put('api/addresses/{id}', [AddressController::class, 'update'], [new AuthMiddleware()]);
$router->delete('api/addresses/{id}', [AddressController::class, 'destroy'], [new AuthMiddleware()]);
$router->put('api/addresses/{id}/primary', [AddressController::class, 'setPrimary'], [new AuthMiddleware()]);

// --- Categorías (Fase 3 / sección 13) ---
$router->get('api/categories', [CategoryController::class, 'index'], [new OptionalAuthMiddleware()]);
$router->get('api/categories/{slug}', [CategoryController::class, 'show'], [new OptionalAuthMiddleware()]);
$router->post('api/categories', [CategoryController::class, 'store'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-categories')]);
$router->put('api/categories/{id}', [CategoryController::class, 'update'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-categories')]);
$router->delete('api/categories/{id}', [CategoryController::class, 'destroy'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-categories')]);

// --- Marcas (Fase 3 / sección 10) ---
$router->get('api/brands', [BrandController::class, 'index'], [new OptionalAuthMiddleware()]);
$router->post('api/brands', [BrandController::class, 'store'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-brands')]);
$router->put('api/brands/{id}', [BrandController::class, 'update'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-brands')]);
$router->delete('api/brands/{id}', [BrandController::class, 'destroy'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-brands')]);

// --- Productos (Fase 3 / secciones 10-11) ---
$router->get('api/products', [ProductController::class, 'index'], [new OptionalAuthMiddleware()]);
$router->get('api/products/{slug}', [ProductController::class, 'show'], [new OptionalAuthMiddleware()]);
$router->post('api/products', [ProductController::class, 'store'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-products')]);
$router->put('api/products/{id}', [ProductController::class, 'update'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-products')]);
$router->delete('api/products/{id}', [ProductController::class, 'destroy'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-products')]);
$router->post('api/products/{id}/images', [ProductController::class, 'uploadImage'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-products')]);
$router->delete('api/products/{id}/images/{imageId}', [ProductController::class, 'deleteImage'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-products')]);
$router->put('api/products/{id}/images/{imageId}/primary', [ProductController::class, 'setPrimaryImage'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-products')]);
$router->put('api/products/{id}/variants', [ProductController::class, 'syncVariants'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-products')]);
$router->put('api/products/{id}/attributes', [ProductController::class, 'syncAttributes'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-products')]);

// --- Servicios (Fase 3 / sección 12) ---
$router->get('api/services', [ServiceController::class, 'index'], [new OptionalAuthMiddleware()]);
$router->get('api/services/{slug}', [ServiceController::class, 'show'], [new OptionalAuthMiddleware()]);
$router->post('api/services', [ServiceController::class, 'store'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-services')]);
$router->put('api/services/{id}', [ServiceController::class, 'update'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-services')]);
$router->delete('api/services/{id}', [ServiceController::class, 'destroy'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-services')]);
$router->post('api/services/{id}/images', [ServiceController::class, 'uploadImage'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-services')]);
$router->delete('api/services/{id}/images/{imageId}', [ServiceController::class, 'deleteImage'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-services')]);

// --- Búsqueda (Fase 4 / sección 14) ---
$router->get('api/search', [SearchController::class, 'search']);
$router->get('api/search/suggestions', [SearchController::class, 'suggestions']);

// --- Favoritos (Fase 4 / sección 16) ---
$router->get('api/favorites', [FavoriteController::class, 'index'], [new AuthMiddleware()]);
$router->post('api/favorites', [FavoriteController::class, 'store'], [new AuthMiddleware()]);
$router->get('api/favorites/check', [FavoriteController::class, 'check'], [new AuthMiddleware()]);
$router->delete('api/favorites/{type}/{id}', [FavoriteController::class, 'destroy'], [new AuthMiddleware()]);

// --- Carrito (Fase 5 / sección 18) — funciona para invitados vía X-Cart-Token ---
$router->get('api/cart', [CartController::class, 'show'], [new OptionalAuthMiddleware()]);
$router->post('api/cart/items', [CartController::class, 'addItem'], [new OptionalAuthMiddleware()]);
$router->put('api/cart/items/{itemId}', [CartController::class, 'updateItem'], [new OptionalAuthMiddleware()]);
$router->delete('api/cart/items/{itemId}', [CartController::class, 'removeItem'], [new OptionalAuthMiddleware()]);
$router->delete('api/cart', [CartController::class, 'clear'], [new OptionalAuthMiddleware()]);

// --- Métodos de pago habilitados (consulta pública para el checkout) ---
$router->get('api/payment-methods', [PaymentMethodController::class, 'index']);

// --- Checkout y pedidos (Fase 5 / sección 19) ---
$router->post('api/checkout', [CheckoutController::class, 'store'], [new AuthMiddleware()]);
$router->get('api/orders/{orderNumber}', [CheckoutController::class, 'show'], [new AuthMiddleware()]);

// --- Administración de pedidos e inventario (Fase 6 / secciones 22, 25) ---
$router->get('api/admin/orders', [AdminOrderController::class, 'index'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-orders')]);
$router->get('api/admin/orders/{orderNumber}', [AdminOrderController::class, 'show'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-orders')]);
$router->put('api/admin/orders/{orderNumber}/status', [AdminOrderController::class, 'updateStatus'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-orders')]);
$router->get('api/admin/reservations', [AdminReservationController::class, 'index'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-orders')]);
$router->get('api/admin/dashboard/summary', [DashboardController::class, 'summary'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-orders')]);

$router->get('api/admin/inventory', [AdminInventoryController::class, 'index'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-inventory')]);
$router->get('api/admin/inventory/movements', [AdminInventoryController::class, 'movements'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-inventory')]);
$router->post('api/admin/inventory/{productId}/adjust', [AdminInventoryController::class, 'adjust'], [new AuthMiddleware(), new RequirePermissionMiddleware('manage-inventory')]);

// --- Archivos servidos (avatares, imágenes de catálogo) ---
$router->get('api/media/avatars/{filename}', [MediaController::class, 'avatar']);
$router->get('api/media/products/{filename}', [MediaController::class, 'productImage']);
$router->get('api/media/services/{filename}', [MediaController::class, 'serviceImage']);

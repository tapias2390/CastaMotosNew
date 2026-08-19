<?php

declare(strict_types=1);

use App\Infrastructure\Database\Seeder;

/**
 * Contenido inicial de site_settings (migración 047). El texto de términos
 * y condiciones es un PUNTO DE PARTIDA genérico, no asesoría legal — CASTAMOTO
 * debería hacerlo revisar por un abogado antes de considerarlo vinculante de
 * verdad (menciones a la Ley 1480 de 2011 de Colombia, retracto, garantías).
 *
 * "ON DUPLICATE KEY UPDATE setting_key = setting_key" es intencional (no-op):
 * si en el futuro alguien edita el valor directo en la base, volver a correr
 * este seeder no debe pisarlo.
 */
return new class extends Seeder {
    public function run(PDO $connection): void
    {
        $terms = <<<TEXT
        Términos y Condiciones de CASTAMOTO

        Última actualización: 2026

        1. Aceptación de los términos
        Al registrarte o realizar una compra en CASTAMOTO aceptas estos Términos y Condiciones. Si no estás de acuerdo, por favor no utilices la plataforma.

        2. Productos y servicios
        CASTAMOTO ofrece repuestos, accesorios y servicios especializados para motocicletas. Los precios, la disponibilidad de stock y las descripciones se muestran en tiempo real y pueden cambiar sin previo aviso.

        3. Pedidos y pagos
        Al confirmar un pedido, el total se recalcula con los precios y el stock vigentes al momento del pago — nunca con datos enviados por tu navegador. Los medios de pago disponibles se muestran en el checkout.

        4. Envíos y entrega
        Puedes elegir entrega a domicilio o recogida en tienda. Los tiempos de entrega son estimados y pueden variar según tu ubicación y el transportador.

        5. Devoluciones y garantías
        De acuerdo con la Ley 1480 de 2011 (Estatuto del Consumidor de Colombia), tienes derecho de retracto dentro de los plazos legales para compras a distancia, salvo las excepciones que la ley contempla. Los productos con falla de fábrica cuentan con la garantía legal correspondiente.

        6. Cuenta de usuario
        Eres responsable de mantener la confidencialidad de tu contraseña y de toda actividad realizada desde tu cuenta.

        7. Reseñas
        Solo los usuarios que compraron un producto o servicio pueden dejar una reseña sobre él. Las reseñas deben ser honestas y respetuosas.

        8. Contacto
        Para dudas sobre estos términos, escríbenos por los canales de contacto publicados en el sitio.

        Este documento es una plantilla inicial y debe ser revisado por un asesor legal antes de considerarse definitivo.
        TEXT;

        $stmt = $connection->prepare(
            'INSERT INTO site_settings (setting_key, value) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_key = setting_key'
        );
        $stmt->execute(['key' => 'terms_and_conditions', 'value' => $terms]);
    }
};

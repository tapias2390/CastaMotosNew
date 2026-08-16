<?php

declare(strict_types=1);

namespace Tests;

use App\Application\UseCases\Auth\LoginUseCase;
use App\Application\UseCases\Auth\RegisterUserUseCase;
use App\Exceptions\UnauthorizedException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Persistence\PdoLoginHistoryRepository;
use App\Infrastructure\Persistence\PdoUserRepository;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Prueba de integración contra la base de datos local: valida el flujo
 * completo registro → login (y el rechazo de una contraseña incorrecta)
 * usando los repositorios PDO reales, no dobles de prueba.
 */
final class AuthFlowIntegrationTest extends TestCase
{
    private PDO $connection;
    private string $email;

    protected function setUp(): void
    {
        $this->connection = Connection::get();
        $this->email = 'phpunit_' . bin2hex(random_bytes(4)) . '@castamoto.local';
    }

    protected function tearDown(): void
    {
        $this->connection->prepare('DELETE FROM users WHERE email = :email')->execute(['email' => $this->email]);
        $this->connection->prepare('DELETE FROM login_history WHERE email_attempted = :email')->execute(['email' => $this->email]);
    }

    public function test_flujo_completo_de_registro_y_login(): void
    {
        $users = new PdoUserRepository($this->connection);
        $loginHistory = new PdoLoginHistoryRepository($this->connection);

        $registerResult = (new RegisterUserUseCase($users))->handle([
            'name' => 'Piloto',
            'last_name' => 'De Prueba',
            'email' => $this->email,
            'phone' => null,
            'password' => 'ClaveSegura123',
        ]);

        $this->assertNotEmpty($registerResult['token']);
        $this->assertSame($this->email, $registerResult['user']->email);
        $this->assertTrue($registerResult['user']->hasRole('cliente'));

        $loginResult = (new LoginUseCase($users, $loginHistory))->handle(
            $this->email,
            'ClaveSegura123',
            false,
            '127.0.0.1',
            'PHPUnit'
        );

        $this->assertNotEmpty($loginResult['token']);
    }

    public function test_login_rechaza_contrasena_incorrecta(): void
    {
        $users = new PdoUserRepository($this->connection);
        $loginHistory = new PdoLoginHistoryRepository($this->connection);

        (new RegisterUserUseCase($users))->handle([
            'name' => 'Piloto',
            'last_name' => 'De Prueba',
            'email' => $this->email,
            'phone' => null,
            'password' => 'ClaveSegura123',
        ]);

        $this->expectException(UnauthorizedException::class);

        (new LoginUseCase($users, $loginHistory))->handle($this->email, 'clave-incorrecta', false, '127.0.0.1', 'PHPUnit');
    }
}

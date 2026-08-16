<?php

declare(strict_types=1);

namespace Tests;

use App\Exceptions\UnauthorizedException;
use App\Infrastructure\Auth\JwtService;
use PHPUnit\Framework\TestCase;

final class JwtServiceTest extends TestCase
{
    public function test_issue_y_verify_hacen_round_trip_correctamente(): void
    {
        $token = JwtService::issue(42, ['cliente']);
        $claims = JwtService::verify($token);

        $this->assertSame(42, $claims['sub']);
        $this->assertSame(['cliente'], $claims['roles']);
    }

    public function test_verify_rechaza_un_token_invalido(): void
    {
        $this->expectException(UnauthorizedException::class);

        JwtService::verify('esto-no-es-un-jwt-valido');
    }
}

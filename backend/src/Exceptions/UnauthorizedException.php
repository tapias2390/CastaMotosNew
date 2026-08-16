<?php

declare(strict_types=1);

namespace App\Exceptions;

class UnauthorizedException extends AppException
{
    protected int $statusCode = 401;
}

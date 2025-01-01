<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Exceptions;

final class InvalidUserOperationException extends \LogicException
{
    private function __construct(
        string $message,
        int $code,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function operationOnResetUserPassword(): self
    {
        return new self(
            'users.operationOnResetUserPassword',
            401,
        );
    }
}

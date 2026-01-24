<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Exceptions;

final class UserNotFound extends \Exception
{
    public const string MESSAGE = 'users.notFound';

    public function __construct(
        string $message = self::MESSAGE,
        int $code = 404,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

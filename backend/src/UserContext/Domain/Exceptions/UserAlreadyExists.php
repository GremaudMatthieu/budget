<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Exceptions;

final class UserAlreadyExists extends \LogicException
{
    public const string MESSAGE = 'users.userAlreadyExists';

    public function __construct(
        string $message = self::MESSAGE,
        int $code = 400,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Exceptions;

final class UserIsNotOwnedByUser extends \LogicException
{
    public const string MESSAGE = 'users.notOwner';

    public function __construct(
        string $message = self::MESSAGE,
        int $code = 400,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Anonymizer\Exceptions;

final class UserEncryption extends \RuntimeException
{
    private function __construct(
        string $message = 'User encryption error.',
        int $code = 500,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromGetKeyFailure(): self
    {
        return new self('Failure on get key.', 500);
    }

    public static function fromEncryptFailure(): self
    {
        return new self('Failure on encrypt.', 500);
    }

    public static function fromDecryptFailure(): self
    {
        return new self('Failure on decrypt.', 500);
    }
}

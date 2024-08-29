<?php

declare(strict_types=1);

namespace App\Domain\Envelope\Exception;

class ChildrenTargetBudgetsExceedsEnvelopeTargetBudgetException extends \Exception
{
    public const MESSAGE = 'Total target budget of children envelopes exceeds envelope\'s target budget';

    public function __construct(
        string $message,
        int $code,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
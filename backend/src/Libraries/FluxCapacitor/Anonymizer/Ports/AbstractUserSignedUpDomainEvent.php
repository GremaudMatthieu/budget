<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Anonymizer\Ports;

interface AbstractUserSignedUpDomainEvent
{
    public function toArray(): array;

    public static function fromArray(array $data): self;
}

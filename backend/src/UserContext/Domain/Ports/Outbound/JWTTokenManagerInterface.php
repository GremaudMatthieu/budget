<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Outbound;

use App\Gateway\User\Views\UserView;

interface JWTTokenManagerInterface
{
    public function create(UserView $userView): string;
}

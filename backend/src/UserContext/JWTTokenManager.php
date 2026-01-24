<?php

declare(strict_types=1);

namespace App\UserContext;

use App\Presentation\User\UserView;

interface JWTTokenManager
{
    public function create(UserView $userView): string;
}

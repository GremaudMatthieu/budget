<?php

namespace App\UserManagement\Infrastructure\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
final class RefreshToken extends BaseRefreshToken
{
}
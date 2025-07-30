<?php

namespace App\UserContext\Infrastructure\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
#[ORM\AttributeOverrides([
    new ORM\AttributeOverride(name: 'id', column: new ORM\Column(type: 'integer')),
])]
class RefreshToken extends BaseRefreshToken
{
}

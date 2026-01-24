<?php

declare(strict_types=1);

namespace App\Presentation\User\HTTP\Controllers;

use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/me', name: 'app_user_show', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class GetAUserController
{
    public function __construct()
    {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(#[CurrentUser] UserView $currentUser): JsonResponse
    {
        return new JsonResponse($currentUser, Response::HTTP_OK);
    }
}

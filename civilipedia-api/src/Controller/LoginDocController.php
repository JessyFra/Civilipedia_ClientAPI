<?php
// src/Controller/LoginDocController.php
namespace App\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[OA\Tag(name: 'Authentification')]
class LoginDocController extends AbstractController
{
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Connexion — retourne un token JWT',
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'admin'),
                    new OA\Property(property: 'password', type: 'string', example: 'Admin1234!'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token JWT retourné'),
            new OA\Response(response: 401, description: 'Identifiants invalides'),
        ]
    )]
    public function login(): void {}
}

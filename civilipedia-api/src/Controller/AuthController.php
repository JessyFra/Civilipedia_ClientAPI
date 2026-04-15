<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/auth')]
#[OA\Tag(name: 'Authentification')]
class AuthController extends AbstractController
{
    #[Route('/api/auth/register', name: 'auth_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Crée un nouveau compte utilisateur',
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'jean_dupont'),
                    new OA\Property(property: 'password', type: 'string', example: 'motdepasse123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Compte créé'),
            new OA\Response(response: 400, description: 'Champs manquants ou username déjà pris'),
        ]
    )]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['username']) || empty($data['password'])) {
            return $this->json([
                'error' => [
                    'code' => 'MISSING_FIELDS',
                    'message' => 'Username et password sont requis.'
                ]
            ], 400);
        }

        $existing = $em->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if ($existing) {
            return $this->json([
                'error' => [
                    'code' => 'USERNAME_TAKEN',
                    'message' => 'Ce nom d\'utilisateur est déjà pris.'
                ]
            ], 409);
        }

        $user = new User();
        $user->setUsername(trim($data['username']));
        $user->setPassword($hasher->hashPassword($user, $data['password']));
        $user->setRole('user');

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Compte créé avec succès.',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'role' => $user->getRole()
            ]
        ], 201);
    }
}

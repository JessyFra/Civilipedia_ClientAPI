<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/users')]
#[OA\Tag(name: 'Utilisateur')]
class UserController extends AbstractController
{
    #[Route('/me', name: 'user_me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/me',
        summary: 'Retourne le profil de l\'utilisateur connecté',
        responses: [
            new OA\Response(response: 200, description: 'Profil retourné'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        return $this->json([
            'id'       => $user->getId(),
            'username' => $user->getUsername(),
            'role'     => $user->getRole(),
            'avatar'   => $user->getAvatar(),
        ]);
    }

    #[Route('/me/password', name: 'user_password', methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/users/me/password',
        summary: 'Modifie le mot de passe',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['current_password', 'new_password'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string'),
                    new OA\Property(property: 'new_password', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Mot de passe mis à jour'),
            new OA\Response(response: 400, description: 'Mot de passe actuel incorrect'),
        ]
    )]
    public function updatePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (empty($data['current_password']) || empty($data['new_password'])) {
            return $this->json(['error' => ['code' => 'MISSING_FIELDS', 'message' => 'Champs requis.']], 400);
        }

        if (!$hasher->isPasswordValid($user, $data['current_password'])) {
            return $this->json(['error' => ['code' => 'INVALID_PASSWORD', 'message' => 'Mot de passe actuel incorrect.']], 400);
        }

        if (strlen($data['new_password']) < 6) {
            return $this->json(['error' => ['code' => 'PASSWORD_TOO_SHORT', 'message' => 'Le mot de passe doit faire au moins 6 caractères.']], 400);
        }

        $user->setPassword($hasher->hashPassword($user, $data['new_password']));
        $em->flush();

        return $this->json(['message' => 'Mot de passe mis à jour.']);
    }


    #[Route('/me/avatar', name: 'user_avatar', methods: ['POST'])]
    #[OA\Post(
        path: '/api/users/me/avatar',
        summary: 'Upload un avatar',
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [new OA\Property(property: 'avatar', type: 'string', format: 'binary')]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Avatar mis à jour'),
            new OA\Response(response: 400, description: 'Fichier invalide'),
        ]
    )]
    public function updateAvatar(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        $file = $request->files->get('avatar');

        if (!$file) {
            return $this->json(['error' => ['code' => 'NO_FILE', 'message' => 'Aucun fichier envoyé.']], 400);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return $this->json(['error' => ['code' => 'INVALID_TYPE', 'message' => 'Format autorisé : jpg, png, webp.']], 400);
        }

        // Limite portée à 10 Mo
        if ($file->getSize() > 10 * 1024 * 1024) {
            return $this->json(['error' => ['code' => 'FILE_TOO_LARGE', 'message' => 'Taille maximale : 10 Mo.']], 400);
        }

        $uploadDir = dirname(__DIR__, 2) . '/private/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Supprime l'ancien avatar si existant
        if ($user->getAvatar()) {
            $old = $uploadDir . '/' . $user->getAvatar();
            if (file_exists($old)) {
                unlink($old);
            }
        }

        $filename = uniqid('avatar_') . '.' . $file->guessExtension();
        $file->move($uploadDir, $filename);

        $user->setAvatar($filename);
        $em->flush();

        return $this->json(['message' => 'Avatar mis à jour.', 'avatar' => $filename]);
    }
}

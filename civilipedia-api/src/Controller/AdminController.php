<?php

namespace App\Controller;

use App\Entity\Ban;
use App\Entity\User;
use App\Repository\BanRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/admin')]
#[OA\Tag(name: 'Administration')]
class AdminController extends AbstractController
{
    // Liste tous les utilisateurs avec leur statut de ban
    #[Route('/users', name: 'admin_users', methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/users',
        summary: 'Liste tous les utilisateurs avec leur statut de ban',
        responses: [
            new OA\Response(response: 200, description: 'Liste retournée'),
            new OA\Response(response: 403, description: 'Accès réservé à l\'admin'),
        ]
    )]
    public function users(UserRepository $userRepo, BanRepository $banRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepo->findAll();

        $data = array_map(function (User $u) use ($banRepo) {
            $activeBan = $banRepo->findOneBy(['user' => $u, 'is_active' => true]);

            return [
                'id'          => $u->getId(),
                'username'    => $u->getUsername(),
                'role'        => $u->getRole(),
                'is_banned'   => $activeBan !== null,
                'ban_reason'  => $activeBan?->getReason(),
                'ban_end_date' => $activeBan?->getEndDate()?->format('Y-m-d'),
            ];
        }, $users);

        return $this->json(['data' => $data]);
    }

    // Bannir un utilisateur
    #[Route('/users/{id}/ban', name: 'admin_ban', methods: ['POST'])]
    #[OA\Post(
        path: '/api/admin/users/{id}/ban',
        summary: 'Bannit un utilisateur (temporaire ou définitif)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['reason'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string', example: 'Contenu inapproprié'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2025-12-31', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Utilisateur banni'),
            new OA\Response(response: 403, description: 'Accès refusé'),
        ]
    )]
    public function ban(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        BanRepository $banRepo
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();

        if ($user->getId() === $admin->getId()) {
            return $this->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Vous ne pouvez pas vous bannir vous-même.']], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['reason'])) {
            return $this->json(['error' => ['code' => 'MISSING_FIELDS', 'message' => 'La raison du ban est requise.']], 400);
        }

        // Désactive les bans actifs existants
        $existingBans = $banRepo->findBy(['user' => $user, 'is_active' => true]);
        foreach ($existingBans as $existingBan) {
            $existingBan->setIsActive(false);
        }

        $ban = new Ban();
        $ban->setUser($user);
        $ban->setReason($data['reason']);
        $ban->setStartDate(new \DateTime());
        $ban->setIsActive(true);

        if (!empty($data['end_date'])) {
            $ban->setEndDate(new \DateTime($data['end_date']));
        }

        $em->persist($ban);
        $em->flush();

        return $this->json([
            'message' => 'Utilisateur banni.',
            'ban' => [
                'user'     => $user->getUsername(),
                'reason'   => $ban->getReason(),
                'end_date' => $ban->getEndDate()?->format('Y-m-d') ?? 'définitif',
            ]
        ], 201);
    }

    // Débannir un utilisateur
    #[Route('/users/{id}/unban', name: 'admin_unban', methods: ['POST'])]
    #[OA\Post(
        path: '/api/admin/users/{id}/unban',
        summary: 'Lève le bannissement d\'un utilisateur',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Utilisateur débanni'),
            new OA\Response(response: 400, description: 'L\'utilisateur n\'est pas banni'),
        ]
    )]
    public function unban(
        User $user,
        EntityManagerInterface $em,
        BanRepository $banRepo
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $activeBans = $banRepo->findBy(['user' => $user, 'is_active' => true]);

        if (empty($activeBans)) {
            return $this->json(['error' => ['code' => 'NOT_BANNED', 'message' => 'Cet utilisateur n\'est pas banni.']], 400);
        }

        foreach ($activeBans as $ban) {
            $ban->setIsActive(false);
        }

        $em->flush();

        return $this->json(['message' => 'Utilisateur débanni.']);
    }

    // Supprimer n'importe quel article
    #[Route('/articles/{id}', name: 'admin_delete_article', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/admin/articles/{id}',
        summary: 'Supprime n\'importe quel article (admin uniquement)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Article supprimé'),
            new OA\Response(response: 403, description: 'Accès refusé'),
        ]
    )]
    public function deleteArticle(
        \App\Entity\Article $article,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em->remove($article);
        $em->flush();

        return $this->json(['message' => 'Article supprimé par l\'admin.']);
    }
}

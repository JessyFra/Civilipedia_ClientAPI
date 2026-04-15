<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleVersion;
use App\Repository\ArticleRepository;
use App\Repository\ArticleVersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/articles')]
#[OA\Tag(name: 'Articles')]
class ArticleController extends AbstractController
{
    // GET /api/articles
    #[Route('', name: 'articles_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/articles',
        summary: 'Liste tous les articles',
        security: [],
        responses: [
            new OA\Response(response: 200, description: 'Liste des articles retournée avec succès'),
        ]
    )]
    public function list(ArticleRepository $repo, Request $request): JsonResponse
    {
        $articles = $repo->findAll();

        $data = array_map(fn(Article $a) => [
            'id'         => $a->getId(),
            'title'      => $a->getTitle(),
            'content'    => $a->getContent(),
            'image_url'  => $this->imageUrl($a, $request),
            'created_at' => $a->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $a->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'author'     => $a->getAuthor()->getUsername(),
        ], $articles);

        return $this->json(['data' => $data, 'total' => count($data)]);
    }

    // GET /api/articles/{id}

    #[Route('/{id}', name: 'article_show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/articles/{id}',
        summary: 'Retourne un article par son id',
        security: [],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Article trouvé'),
            new OA\Response(response: 404, description: 'Article introuvable'),
        ]
    )]
    public function show(Article $article, Request $request): JsonResponse
    {
        return $this->json([
            'id'          => $article->getId(),
            'title'       => $article->getTitle(),
            'content'     => $article->getContent(),
            'image_url'   => $this->imageUrl($article, $request),
            'created_at'  => $article->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at'  => $article->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'author'      => $article->getAuthor()->getUsername(),
            'firstAuthor' => $article->getFirstAuthor()->getUsername(),
        ]);
    }

    // GET /api/articles/{id}/history
    #[Route('/{id}/history', name: 'article_history', methods: ['GET'])]
    #[OA\Get(
        path: '/api/articles/{id}/history',
        summary: 'Historique des versions d\'un article',
        security: [],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Historique retourné')]
    )]
    public function history(Article $article, ArticleVersionRepository $repo): JsonResponse
    {
        $versions = $repo->findBy(['article' => $article], ['created_at' => 'DESC']);

        $data = array_map(fn(ArticleVersion $v) => [
            'id'          => $v->getId(),
            'title'       => $v->getTitle(),
            'content'     => $v->getContent(),
            'created_at'  => $v->getCreatedAt()->format('Y-m-d H:i:s'),
            'modified_by' => $v->getUser()->getUsername(),
        ], $versions);

        return $this->json(['data' => $data, 'total' => count($data)]);
    }

    // POST /api/articles
    #[Route('', name: 'article_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/articles',
        summary: 'Crée un nouvel article',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'content', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Article créé'),
            new OA\Response(response: 400, description: 'Champs manquants'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (empty($data['title']) || empty($data['content'])) {
            return $this->json(['error' => ['code' => 'MISSING_FIELDS', 'message' => 'Titre et contenu requis.']], 400);
        }

        $article = new Article();
        $article->setTitle(trim($data['title']));
        $article->setContent($data['content']);
        $article->setCreatedAt(new \DateTime());
        $article->setFirstAuthor($user);
        $article->setAuthor($user);

        $em->persist($article);
        $em->flush();

        // Pas de version initiale créée à la publication
        // L'historique ne démarre qu'à la première vraie modification.

        return $this->json(['message' => 'Article créé.', 'id' => $article->getId()], 201);
    }

    // PUT /api/articles/{id}
    // Tout utilisateur connecté peut modifier un article
    #[Route('/{id}', name: 'article_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/articles/{id}',
        summary: 'Modifie un article (sauvegarde l\'ancienne version)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'content', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Article mis à jour'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function update(Article $article, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        // Sauvegarde l'état courant dans l'historique avant modification
        $version = new ArticleVersion();
        $version->setTitle($article->getTitle());
        $version->setContent($article->getContent());
        $version->setCreatedAt(new \DateTime());
        $version->setUser($user);
        $version->setArticle($article);
        $em->persist($version);

        if (!empty($data['title']))   $article->setTitle(trim($data['title']));
        if (!empty($data['content'])) $article->setContent($data['content']);
        $article->setUpdatedAt(new \DateTime());
        $article->setAuthor($user);

        $em->flush();

        return $this->json(['message' => 'Article mis à jour.']);
    }

    // DELETE /api/articles/{id}
    #[Route('/{id}', name: 'article_delete', methods: ['DELETE'])]    #[Route('/{id}', name: 'article_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/articles/{id}',
        summary: 'Supprime un article (auteur ou admin)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Article supprimé'),
            new OA\Response(response: 403, description: 'Accès refusé'),
        ]
    )]    public function delete(Article $article, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if ($article->getAuthor()->getId() !== $user->getId() && !in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Action non autorisée.']], 403);
        }

        $em->remove($article);
        $em->flush();

        return $this->json(['message' => 'Article supprimé.'], 204);
    }

    // POST /api/articles/{id}/image
    // Tout utilisateur connecté peut uploader une image
    #[Route('/{id}/image', name: 'article_upload_image', methods: ['POST'])]
    #[OA\Post(
        path: '/api/articles/{id}/image',
        summary: 'Upload l\'image d\'un article',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [new OA\Property(property: 'image', type: 'string', format: 'binary')]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Image mise à jour'),
            new OA\Response(response: 400, description: 'Fichier invalide ou absent'),
        ]
    )]
    public function uploadImage(Article $article, Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
        $file = $request->files->get('image');

        if (!$file) {
            return $this->json(['error' => ['code' => 'NO_FILE', 'message' => 'Aucun fichier reçu.']], 400);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowedMimes, true)) {
            return $this->json(['error' => ['code' => 'INVALID_TYPE', 'message' => 'Format autorisé : jpg, png, webp, gif.']], 400);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json(['error' => ['code' => 'FILE_TOO_LARGE', 'message' => 'Taille maximale : 5 Mo.']], 400);
        }

        $uploadDir = dirname(__DIR__, 2) . '/private/articles';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Supprime l'ancienne image
        if ($article->getImage()) {
            $old = $uploadDir . '/' . $article->getImage();
            if (file_exists($old)) {
                unlink($old);
            }
        }

        $filename = 'article_' . $article->getId() . '_' . uniqid() . '.' . $file->guessExtension();
        $file->move($uploadDir, $filename);

        $article->setImage($filename);
        $em->flush();

        return $this->json([
            'message'   => 'Image mise à jour.',
            'image_url' => $this->imageUrl($article, $request),
        ]);
    }

    // GET /api/articles/{id}/image
    #[Route('/{id}/image', name: 'article_serve_image', methods: ['GET'])]
    public function serveImage(Article $article): BinaryFileResponse|Response
    {
        if (!$article->getImage()) {
            return new Response('', 404);
        }

        $path = dirname(__DIR__, 2) . '/private/articles/' . $article->getImage();

        if (!file_exists($path)) {
            return new Response('', 404);
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE);
        $response->setMaxAge(3600);
        $response->setPublic();

        return $response;
    }

    // Helper privé
    private function imageUrl(Article $article, Request $request): ?string
    {
        if (!$article->getImage()) {
            return null;
        }

        return $request->getSchemeAndHttpHost()
            . '/api/articles/' . $article->getId() . '/image';
    }
}

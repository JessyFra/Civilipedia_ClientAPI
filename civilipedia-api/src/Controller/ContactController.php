<?php

namespace App\Controller;

use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api')]
#[OA\Tag(name: 'Contact')]
class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact', methods: ['POST'])]
    #[OA\Post(
        path: '/api/contact',
        summary: 'Envoie un message de contact',
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'subject', 'message'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'subject', type: 'string'),
                    new OA\Property(property: 'message', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Message envoyé'),
            new OA\Response(response: 400, description: 'Champs manquants ou email invalide'),
        ]
    )]
    public function contact(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name']) || empty($data['email']) || empty($data['subject']) || empty($data['message'])) {
            return $this->json(['error' => ['code' => 'MISSING_FIELDS', 'message' => 'Tous les champs sont requis.']], 400);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => ['code' => 'INVALID_EMAIL', 'message' => 'Email invalide.']], 400);
        }

        $contact = new Contact();
        $contact->setName(trim($data['name']));
        $contact->setEmail(trim($data['email']));
        $contact->setSubject(trim($data['subject']));
        $contact->setMessage(trim($data['message']));

        $em->persist($contact);
        $em->flush();

        return $this->json(['message' => 'Message envoyé avec succès.'], 201);
    }
}

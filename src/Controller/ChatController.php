<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chat')]
#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    private $httpClient;
    private $javaApiUrl = 'http://localhost:8080/demo-api/api';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/trajet/{trajetId}/conversation/{destinataireId}', name: 'chat_conversation', methods: ['GET', 'POST'])]
    public function conversation(Request $request, int $trajetId, int $destinataireId): Response
    {
        $error = null;
        $success = null;
        $messages = [];
        $trajet = null;
        $destinataire = null;
        
        $userId = $this->getUser()->getId();
        
        // Récupérer les informations du trajet
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/' . $trajetId);
            if ($response->getStatusCode() === 200) {
                $trajet = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération du trajet: " . $e->getMessage();
        }

        // Récupérer la conversation
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/messages/conversation/' . $trajetId . '/' . $userId . '/' . $destinataireId);
            if ($response->getStatusCode() === 200) {
                $messages = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des messages: " . $e->getMessage();
        }

        // Envoyer un nouveau message
        if ($request->isMethod('POST')) {
            $contenu = $request->request->get('contenu');
            if ($contenu && !empty(trim($contenu))) {
                $data = [
                    'trajetId' => $trajetId,
                    'expediteurId' => $userId,
                    'destinataireId' => $destinataireId,
                    'contenu' => trim($contenu)
                ];
                
                try {
                    $response = $this->httpClient->request('POST', $this->javaApiUrl . '/messages', [
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'json' => $data
                    ]);
                    
                    if ($response->getStatusCode() === 201) {
                        $success = 'Message envoyé !';
                        // Recharger les messages
                        $response = $this->httpClient->request('GET', $this->javaApiUrl . '/messages/conversation/' . $trajetId . '/' . $userId . '/' . $destinataireId);
                        if ($response->getStatusCode() === 200) {
                            $messages = $response->toArray();
                        }
                    } else {
                        $error = $response->getContent(false);
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }
        
        return $this->render('chat/conversation.html.twig', [
            'error' => $error,
            'success' => $success,
            'messages' => $messages,
            'trajet' => $trajet,
            'destinataireId' => $destinataireId,
            'trajetId' => $trajetId,
        ]);
    }

    #[Route('/messages', name: 'chat_messages', methods: ['GET'])]
    public function messages(): Response
    {
        $error = null;
        $messages = [];
        
        $userId = $this->getUser()->getId();
        
        // Récupérer tous les messages de l'utilisateur
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/messages/user/' . $userId);
            if ($response->getStatusCode() === 200) {
                $messages = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des messages: " . $e->getMessage();
        }
        
        return $this->render('chat/messages.html.twig', [
            'error' => $error,
            'messages' => $messages,
        ]);
    }
} 
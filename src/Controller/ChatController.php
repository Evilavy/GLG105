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

    #[Route('/conversation/{trajetId}/{destinataireId}', name: 'chat_conversation', methods: ['GET', 'POST'])]
    public function conversation(Request $request, int $trajetId, int $destinataireId): Response
    {
        $error = null;
        $success = null;
        $messages = [];
        $trajet = null;
        
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

        // Récupérer les messages de la conversation
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/messages/conversation/' . $trajetId . '/' . $userId . '/' . $destinataireId);
            if ($response->getStatusCode() === 200) {
                $messages = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des messages: " . $e->getMessage();
        }

        // Traitement de l'envoi d'un nouveau message
        if ($request->isMethod('POST')) {
            $contenu = $request->request->get('contenu');
            if ($contenu) {
                $data = [
                    'trajetId' => $trajetId,
                    'expediteurId' => $userId,
                    'destinataireId' => $destinataireId,
                    'contenu' => $contenu,
                    'dateEnvoi' => date('Y-m-d H:i:s'),
                    'lu' => false
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
                        // Rediriger pour éviter la soumission multiple
                        return $this->redirectToRoute('chat_conversation', [
                            'trajetId' => $trajetId,
                            'destinataireId' => $destinataireId
                        ]);
                    } else {
                        $error = $response->getContent(false);
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }
        
        return $this->render('chat/conversation.html.twig', [
            'trajetId' => $trajetId,
            'destinataireId' => $destinataireId,
            'trajet' => $trajet,
            'messages' => $messages,
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/messages', name: 'chat_messages', methods: ['GET'])]
    public function messages(): Response
    {
        $error = null;
        $conversations = [];
        
        $userId = $this->getUser()->getId();
        
        // Récupérer tous les messages de l'utilisateur
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/messages/user/' . $userId);
            if ($response->getStatusCode() === 200) {
                $tousMessages = $response->toArray();
                
                // Grouper les messages par conversation (trajet + autre utilisateur)
                $conversationsGrouped = [];
                foreach ($tousMessages as $message) {
                    $trajetId = $message['trajetId'];
                    $autreUserId = $message['expediteurId'] == $userId ? $message['destinataireId'] : $message['expediteurId'];
                    $key = $trajetId . '_' . $autreUserId;
                    
                    if (!isset($conversationsGrouped[$key])) {
                        $conversationsGrouped[$key] = [
                            'trajetId' => $trajetId,
                            'autreUserId' => $autreUserId,
                            'messages' => [],
                            'dernierMessage' => null,
                            'messagesNonLus' => 0
                        ];
                    }
                    
                    $conversationsGrouped[$key]['messages'][] = $message;
                    
                    // Compter les messages non lus reçus par l'utilisateur
                    if ($message['destinataireId'] == $userId && !$message['lu']) {
                        $conversationsGrouped[$key]['messagesNonLus']++;
                    }
                    
                    // Garder le dernier message pour l'aperçu
                    if (!$conversationsGrouped[$key]['dernierMessage'] || 
                        $message['dateEnvoi'] > $conversationsGrouped[$key]['dernierMessage']['dateEnvoi']) {
                        $conversationsGrouped[$key]['dernierMessage'] = $message;
                    }
                }
                
                // Trier par date du dernier message (plus récent en premier)
                uasort($conversationsGrouped, function($a, $b) {
                    return strtotime($b['dernierMessage']['dateEnvoi']) - strtotime($a['dernierMessage']['dateEnvoi']);
                });
                
                $conversations = $conversationsGrouped;
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des messages: " . $e->getMessage();
        }
        
        return $this->render('chat/messages.html.twig', [
            'error' => $error,
            'conversations' => $conversations,
        ]);
    }
} 
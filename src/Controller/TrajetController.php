<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trajet')]
#[IsGranted('ROLE_USER')]
class TrajetController extends AbstractController
{
    private $httpClient;
    private $javaApiUrl = 'http://localhost:8080/demo-api/api';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/ajouter', name: 'trajet_ajouter', methods: ['GET', 'POST'])]
    public function ajouter(Request $request): Response
    {
        $error = null;
        $success = null;
        $enfants = [];
        $voitures = [];
        $ecoles = [];
        
        // Récupérer la liste des enfants de l'utilisateur connecté
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/enfants/user/' . $this->getUser()->getId());
            if ($response->getStatusCode() === 200) {
                $enfants = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des enfants: " . $e->getMessage();
        }

        // Récupérer la liste des voitures de l'utilisateur connecté
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/voitures/user/' . $this->getUser()->getId());
            if ($response->getStatusCode() === 200) {
                $voitures = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des voitures: " . $e->getMessage();
        }

        // Récupérer la liste des écoles
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles');
            if ($response->getStatusCode() === 200) {
                $ecoles = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des écoles: " . $e->getMessage();
        }

        if ($request->isMethod('POST')) {
            $enfantsIds = $request->request->all('enfants');
            $enfantsIds = array_filter($enfantsIds, function($id) { return !empty($id); });
            
            // Récupérer les informations de l'école sélectionnée
            $ecoleId = $request->request->get('ecoleArrivee');
            $ecoleArrivee = null;
            if ($ecoleId) {
                foreach ($ecoles as $ecole) {
                    if ($ecole['id'] == $ecoleId) {
                        $ecoleArrivee = $ecole['nom'] . ' - ' . $ecole['ville'] . ' (' . $ecole['codePostal'] . ')';
                        break;
                    }
                }
            }
            
            $data = [
                'pointDepart' => $request->request->get('pointDepart'),
                'pointArrivee' => $ecoleArrivee,
                'dateDepart' => $request->request->get('dateDepart'),
                'heureDepart' => $request->request->get('heureDepart'),
                'dateArrivee' => $request->request->get('dateArrivee'),
                'heureArrivee' => $request->request->get('heureArrivee'),
                'nombrePlaces' => (int) $request->request->get('nombrePlaces'),
                'conducteurId' => $this->getUser()->getId(),
                'voitureId' => (int) $request->request->get('voitureId'),
                'description' => $request->request->get('description'),
                'prix' => (float) $request->request->get('prix'),
                'enfantsIds' => array_map('intval', $enfantsIds)
            ];
            
            try {
                $response = $this->httpClient->request('POST', $this->javaApiUrl . '/trajets', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $data
                ]);
                
                if ($response->getStatusCode() === 201) {
                    $success = 'Trajet créé avec succès !';
                } else {
                    $error = $response->getContent(false);
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        return $this->render('trajet/ajouter.html.twig', [
            'error' => $error,
            'success' => $success,
            'enfants' => $enfants,
            'voitures' => $voitures,
            'ecoles' => $ecoles,
        ]);
    }

    #[Route('/rechercher', name: 'trajet_rechercher', methods: ['GET', 'POST'])]
    public function rechercher(Request $request): Response
    {
        $error = null;
        $trajets = [];
        $ecoles = [];
        $dateRecherche = $request->request->get('date') ?: date('Y-m-d');
        $ecoleId = $request->request->get('ecoleId');
        
        // Récupérer la liste des écoles
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles');
            if ($response->getStatusCode() === 200) {
                $ecoles = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des écoles: " . $e->getMessage();
        }

        // Si une école et une date sont sélectionnées, rechercher les trajets
        if ($ecoleId && $dateRecherche) {
            try {
                $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets');
                if ($response->getStatusCode() === 200) {
                    $tousTrajets = $response->toArray();
                    
                    // Filtrer les trajets par école et date
                    foreach ($tousTrajets as $trajet) {
                        if ($trajet['statut'] === 'disponible' && 
                            $trajet['dateDepart'] === $dateRecherche &&
                            strpos($trajet['pointArrivee'], $ecoles[$ecoleId - 1]['nom']) !== false) {
                            $trajets[] = $trajet;
                        }
                    }
                }
            } catch (\Exception $e) {
                $error = "Erreur lors de la récupération des trajets: " . $e->getMessage();
            }
        }
        
        return $this->render('trajet/rechercher.html.twig', [
            'error' => $error,
            'trajets' => $trajets,
            'ecoles' => $ecoles,
            'dateRecherche' => $dateRecherche,
            'ecoleId' => $ecoleId,
        ]);
    }

    #[Route('/mes-trajets', name: 'trajet_mes_trajets', methods: ['GET'])]
    public function mesTrajets(): Response
    {
        $error = null;
        $trajets = [];
        $conversations = [];
        
        $userId = $this->getUser()->getId();
        
        // Récupérer les trajets de l'utilisateur connecté
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/conducteur/' . $userId);
            if ($response->getStatusCode() === 200) {
                $trajets = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des trajets: " . $e->getMessage();
        }

        // Récupérer les conversations pour chaque trajet
        foreach ($trajets as $trajet) {
            try {
                $response = $this->httpClient->request('GET', $this->javaApiUrl . '/messages/trajet/' . $trajet['id']);
                if ($response->getStatusCode() === 200) {
                    $messages = $response->toArray();
                    if (!empty($messages)) {
                        // Grouper par destinataire
                        $conversationsParTrajet = [];
                        foreach ($messages as $message) {
                            $autreUserId = $message['expediteurId'] == $userId ? $message['destinataireId'] : $message['expediteurId'];
                            if (!isset($conversationsParTrajet[$autreUserId])) {
                                $conversationsParTrajet[$autreUserId] = [];
                            }
                            $conversationsParTrajet[$autreUserId][] = $message;
                        }
                        $conversations[$trajet['id']] = $conversationsParTrajet;
                    }
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs pour les conversations
            }
        }
        
        return $this->render('trajet/mes_trajets.html.twig', [
            'error' => $error,
            'trajets' => $trajets,
            'conversations' => $conversations,
        ]);
    }
} 
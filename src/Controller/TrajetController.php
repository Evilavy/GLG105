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
        $ecoles = [];
        $enfants = [];
        $voitures = [];
        $userId = $this->getUser()->getId();

        // Récupérer la liste des écoles
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles');
            if ($response->getStatusCode() === 200) {
                $ecoles = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des écoles: " . $e->getMessage();
        }

        // Récupérer les enfants validés de l'utilisateur
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/enfants/user/' . $userId);
            if ($response->getStatusCode() === 200) {
                $tousEnfants = $response->toArray();
                // Filtrer seulement les enfants validés par l'admin
                $enfants = array_filter($tousEnfants, function($enfant) {
                    return $enfant['valideParAdmin'] === true;
                });
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des enfants: " . $e->getMessage();
        }

        // Récupérer les voitures de l'utilisateur
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/voitures/user/' . $userId);
            if ($response->getStatusCode() === 200) {
                $voitures = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des voitures: " . $e->getMessage();
        }

        if ($request->isMethod('POST')) {
            $pointDepart = $request->request->get('pointDepart');
            $ecoleArriveeId = $request->request->get('ecoleArrivee');
            $pointArrivee = null;
            foreach ($ecoles as $ecole) {
                if ($ecole['id'] == $ecoleArriveeId) {
                    $pointArrivee = $ecole['nom'] . ' - ' . $ecole['ville'] . ' (' . $ecole['codePostal'] . ')';
                    break;
                }
            }
            $dateDepart = $request->request->get('dateDepart');
            $heureDepart = $request->request->get('heureDepart');
            $dateArrivee = $request->request->get('dateArrivee');
            $heureArrivee = $request->request->get('heureArrivee');
            $nombrePlaces = $request->request->get('nombrePlaces');
            $enfantsIds = $request->request->all('enfants');
            $voitureId = $request->request->get('voitureId');
            $pointsCout = $request->request->get('pointsCout', 5); // Coût par défaut de 5 points

            if ($pointDepart && $pointArrivee && $dateDepart && $heureDepart && $dateArrivee && $heureArrivee && $nombrePlaces && $voitureId) {
                $data = [
                    'pointDepart' => $pointDepart,
                    'pointArrivee' => $pointArrivee,
                    'dateDepart' => $dateDepart,
                    'heureDepart' => $heureDepart,
                    'dateArrivee' => $dateArrivee,
                    'heureArrivee' => $heureArrivee,
                    'nombrePlaces' => (int)$nombrePlaces,
                    'conducteurId' => $userId,
                    'voitureId' => (int)$voitureId,
                    'enfantsIds' => array_map('intval', $enfantsIds),
                    'pointsCout' => (int)$pointsCout,
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
            } else {
                $error = 'Tous les champs sont obligatoires';
            }
        }

        return $this->render('trajet/ajouter.html.twig', [
            'ecoles' => $ecoles,
            'enfants' => $enfants,
            'voitures' => $voitures,
            'error' => $error,
            'success' => $success,
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
                    
                    // Filtrer les trajets par école, date et conducteur différent
                    $userId = $this->getUser()->getId();
                    foreach ($tousTrajets as $trajet) {
                        if (
                            $trajet['statut'] === 'disponible' &&
                            $trajet['dateDepart'] === $dateRecherche &&
                            strpos($trajet['pointArrivee'], $ecoles[$ecoleId - 1]['nom']) !== false &&
                            isset($trajet['conducteurId']) && $trajet['conducteurId'] != $userId
                        ) {
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
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reservation')]
#[IsGranted('ROLE_USER')]
class ReservationController extends AbstractController
{
    private $javaApiUrl = 'http://localhost:8080/demo-api/api';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/trajet/{id}', name: 'reservation_trajet', methods: ['POST'])]
    public function reserverTrajet(Request $request, int $id): Response
    {
        $userId = $this->getUser()->getId();
        
        try {
            // Vérifier d'abord les points de l'utilisateur
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/users/' . $userId . '/points');
            if ($response->getStatusCode() !== 200) {
                $this->addFlash('error', 'Impossible de récupérer vos points');
                return $this->redirectToRoute('trajet_rechercher');
            }
            
            $userData = $response->toArray();
            $userPoints = $userData['points'];
            
            // Récupérer les détails du trajet pour connaître le coût
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/' . $id);
            if ($response->getStatusCode() !== 200) {
                $this->addFlash('error', 'Trajet non trouvé');
                return $this->redirectToRoute('trajet_rechercher');
            }
            
            $trajet = $response->toArray();
            $pointsCout = $trajet['pointsCout'] ?? 5;
            
            // Vérifier si l'utilisateur a assez de points
            if ($userPoints < $pointsCout) {
                $this->addFlash('error', 'Vous n\'avez pas assez de points. Coût: ' . $pointsCout . ' points, Votre solde: ' . $userPoints . ' points');
                return $this->redirectToRoute('trajet_rechercher');
            }
            
            // Effectuer la réservation
            $response = $this->httpClient->request('POST', $this->javaApiUrl . '/trajets/' . $id . '/reserver?userId=' . $userId);
            
            if ($response->getStatusCode() === 200) {
                // Retirer les points à l'utilisateur
                $response = $this->httpClient->request('PUT', $this->javaApiUrl . '/users/' . $userId . '/points/remove', [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json' => $pointsCout
                ]);
                
                if ($response->getStatusCode() === 200) {
                    $this->addFlash('success', 'Réservation effectuée avec succès ! ' . $pointsCout . ' points ont été débités de votre compte.');
                } else {
                    $this->addFlash('warning', 'Réservation effectuée mais erreur lors du débit des points.');
                }
            } else {
                $errorContent = $response->getContent(false);
                $this->addFlash('error', 'Erreur lors de la réservation: ' . $errorContent);
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la réservation: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('trajet_rechercher');
    }
} 
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/enfants')]
#[IsGranted('ROLE_ADMIN')]
class AdminEnfantController extends AbstractController
{
    private $httpClient;
    private $javaApiUrl = 'http://localhost:8080/demo-api/api';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/pending', name: 'admin_enfants_pending')]
    public function pendingEnfants(): Response
    {
        $error = null;
        $enfants = [];

        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/enfants/pending');
            if ($response->getStatusCode() === 200) {
                $enfants = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des enfants en attente: " . $e->getMessage();
        }

        return $this->render('admin/enfants/pending.html.twig', [
            'enfants' => $enfants,
            'error' => $error,
        ]);
    }

    #[Route('/{id}/validate', name: 'admin_enfant_validate', methods: ['POST'])]
    public function validateEnfant(int $id): Response
    {
        try {
            $response = $this->httpClient->request('PUT', $this->javaApiUrl . '/enfants/' . $id . '/validate');
            
            if ($response->getStatusCode() === 200) {
                $this->addFlash('success', 'Enfant validé avec succès !');
            } else {
                $this->addFlash('error', 'Erreur lors de la validation de l\'enfant');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_enfants_pending');
    }

    #[Route('/{id}/reject', name: 'admin_enfant_reject', methods: ['POST'])]
    public function rejectEnfant(int $id): Response
    {
        try {
            $response = $this->httpClient->request('PUT', $this->javaApiUrl . '/enfants/' . $id . '/reject');
            
            if ($response->getStatusCode() === 200) {
                $this->addFlash('success', 'Enfant rejeté avec succès !');
            } else {
                $this->addFlash('error', 'Erreur lors du rejet de l\'enfant');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_enfants_pending');
    }

    #[Route('/{id}/view', name: 'admin_enfant_view')]
    public function viewEnfant(int $id): Response
    {
        $error = null;
        $enfant = null;

        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/enfants/' . $id);
            if ($response->getStatusCode() === 200) {
                $enfant = $response->toArray();
            } else {
                $error = "Enfant non trouvé";
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération de l'enfant: " . $e->getMessage();
        }

        return $this->render('admin/enfants/view.html.twig', [
            'enfant' => $enfant,
            'error' => $error,
        ]);
    }
} 
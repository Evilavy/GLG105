<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/voiture')]
#[IsGranted('ROLE_USER')]
class VoitureController extends AbstractController
{
    private $httpClient;
    private $javaApiUrl = 'http://localhost:8080/demo-api/api/voitures';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/ajouter', name: 'voiture_ajouter', methods: ['GET', 'POST'])]
    public function ajouter(Request $request): Response
    {
        $error = null;
        $success = null;
        if ($request->isMethod('POST')) {
            $data = [
                'marque' => $request->request->get('marque'),
                'modele' => $request->request->get('modele'),
                'couleur' => $request->request->get('couleur'),
                'immatriculation' => $request->request->get('immatriculation'),
                'nombrePlaces' => (int) $request->request->get('nombrePlaces'),
                'userId' => $this->getUser()->getId(),
            ];
            try {
                $response = $this->httpClient->request('POST', $this->javaApiUrl, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $data
                ]);
                if ($response->getStatusCode() === 201) {
                    $success = 'Voiture ajoutée avec succès !';
                } else {
                    $error = $response->getContent(false);
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
        return $this->render('voiture/ajouter.html.twig', [
            'error' => $error,
            'success' => $success,
        ]);
    }
} 
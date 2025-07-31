<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/enfant')]
#[IsGranted('ROLE_USER')]
class EnfantController extends AbstractController
{
    private $httpClient;
    private $javaApiUrl = 'http://localhost:8080/demo-api/api/enfants';
    private $javaApiEcolesUrl = 'http://localhost:8080/demo-api/api/ecoles';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/ajouter', name: 'enfant_ajouter', methods: ['GET', 'POST'])]
    public function ajouter(Request $request): Response
    {
        $error = null;
        $success = null;
        $ecoles = [];
        // Récupérer la liste des écoles depuis l'API JavaEE
        try {
            $response = $this->httpClient->request('GET', $this->javaApiEcolesUrl);
            if ($response->getStatusCode() === 200) {
                $ecoles = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Impossible de récupérer la liste des écoles.";
        }

        if ($request->isMethod('POST')) {
            $data = [
                'nom' => $request->request->get('nom'),
                'prenom' => $request->request->get('prenom'),
                'dateNaissance' => $request->request->get('dateNaissance'),
                'sexe' => $request->request->get('sexe'),
                'ecoleId' => $request->request->get('ecoleId') ? (int)$request->request->get('ecoleId') : null,
                'userId' => $this->getUser()->getId(), // Ajouter l'ID de l'utilisateur connecté
            ];
            try {
                $response = $this->httpClient->request('POST', $this->javaApiUrl, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $data
                ]);
                if ($response->getStatusCode() === 201) {
                    $success = 'Enfant ajouté avec succès !';
                } else {
                    $error = $response->getContent(false);
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
        return $this->render('enfant/ajouter.html.twig', [
            'error' => $error,
            'success' => $success,
            'ecoles' => $ecoles,
        ]);
    }
} 
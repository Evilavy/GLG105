<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use GuzzleHttp\Client;

#[Route('/enfant')]
#[IsGranted('ROLE_USER')]
class EnfantController extends AbstractController
{
    private $httpClient;
    private $javaApiUrl = 'http://localhost:8080/demo-api/api';

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
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $dateNaissance = $request->request->get('dateNaissance');
            $sexe = $request->request->get('sexe');
            $ecoleId = $request->request->get('ecoleId');
            $certificatFile = $request->files->get('certificatScolarite');

            if ($nom && $prenom && $dateNaissance && $sexe && $ecoleId && $certificatFile) {
                $client = new Client();
                try {
                    $response = $client->request('POST', $this->javaApiUrl . '/enfants', [
                        'multipart' => [
                            [ 'name' => 'nom', 'contents' => $nom ],
                            [ 'name' => 'prenom', 'contents' => $prenom ],
                            [ 'name' => 'dateNaissance', 'contents' => $dateNaissance ],
                            [ 'name' => 'sexe', 'contents' => $sexe ],
                            [ 'name' => 'ecoleId', 'contents' => $ecoleId ],
                            [ 'name' => 'userId', 'contents' => $this->getUser()->getId() ],
                            [
                                'name' => 'certificatScolarite',
                                'contents' => fopen($certificatFile->getPathname(), 'r'),
                                'filename' => $certificatFile->getClientOriginalName(),
                            ],
                        ],
                        'headers' => [
                            'Accept' => 'application/json',
                        ],
                    ]);
                    if ($response->getStatusCode() === 201) {
                        $success = 'Enfant ajouté avec succès ! Il sera validé par un administrateur avant de pouvoir être utilisé dans les trajets.';
                    } else {
                        $error = $response->getBody()->getContents();
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            } else {
                $error = 'Tous les champs sont obligatoires';
            }
        }

        return $this->render('enfant/ajouter.html.twig', [
            'ecoles' => $ecoles,
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/liste', name: 'enfant_liste')]
    public function liste(): Response
    {
        $error = null;
        $enfants = [];

        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/enfants/user/' . $this->getUser()->getId());
            if ($response->getStatusCode() === 200) {
                $enfants = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des enfants: " . $e->getMessage();
        }

        return $this->render('enfant/liste.html.twig', [
            'enfants' => $enfants,
            'error' => $error,
        ]);
    }
} 
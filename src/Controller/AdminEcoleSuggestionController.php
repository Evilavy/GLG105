<?php

namespace App\Controller;

use App\Entity\EcoleSuggestion;
use App\Repository\EcoleSuggestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/ecole-suggestion')]
#[IsGranted('ROLE_ADMIN')]
class AdminEcoleSuggestionController extends AbstractController
{
    private $httpClient;
    private $javaApiUrl = 'http://localhost:8080/demo-api/api/ecoles';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/', name: 'admin_ecole_suggestion_list')]
    public function list(EcoleSuggestionRepository $repo): Response
    {
        $suggestions = $repo->findBy([], ['createdAt' => 'DESC']);
        return $this->render('admin/ecole_suggestion/list.html.twig', [
            'suggestions' => $suggestions,
        ]);
    }

    #[Route('/{id}/valider', name: 'admin_ecole_suggestion_valider', methods: ['POST'])]
    public function valider(EcoleSuggestion $suggestion, EntityManagerInterface $em): Response
    {
        if (!$suggestion->isState()) {
            // Envoi à l'API JavaEE
            $data = [
                'nom' => $suggestion->getNom(),
                'adresse' => $suggestion->getAdresse(),
                'ville' => $suggestion->getVille(),
                'codePostal' => $suggestion->getCodePostal(),
            ];
            try {
                $response = $this->httpClient->request('POST', $this->javaApiUrl, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $data
                ]);
                if ($response->getStatusCode() === 201) {
                    $suggestion->setState(true);
                    $em->flush();
                    $this->addFlash('success', 'École validée et ajoutée à l’API JavaEE.');
                } else {
                    $this->addFlash('danger', 'Erreur lors de l’envoi à l’API JavaEE : ' . $response->getContent(false));
                }
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de l’envoi à l’API JavaEE : ' . $e->getMessage());
            }
        }
        return $this->redirectToRoute('admin_ecole_suggestion_list');
    }

    #[Route('/{id}/rejeter', name: 'admin_ecole_suggestion_rejeter', methods: ['POST'])]
    public function rejeter(EcoleSuggestion $suggestion, EntityManagerInterface $em): Response
    {
        $em->remove($suggestion);
        $em->flush();
        $this->addFlash('success', 'Suggestion rejetée et supprimée.');
        return $this->redirectToRoute('admin_ecole_suggestion_list');
    }
} 
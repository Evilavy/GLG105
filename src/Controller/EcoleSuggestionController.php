<?php

namespace App\Controller;

use App\Entity\EcoleSuggestion;
use App\Form\EcoleSuggestionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ecole/suggestion')]
#[IsGranted('ROLE_USER')]
class EcoleSuggestionController extends AbstractController
{
    #[Route('/ajouter', name: 'ecole_suggestion_ajouter', methods: ['GET', 'POST'])]
    public function ajouter(Request $request, EntityManagerInterface $em): Response
    {
        $suggestion = new EcoleSuggestion();
        $form = $this->createForm(EcoleSuggestionType::class, $suggestion);
        $form->handleRequest($request);
        $success = false;

        if ($form->isSubmitted() && $form->isValid()) {
            $suggestion->setCreatedBy($this->getUser());
            $em->persist($suggestion);
            $em->flush();
            $success = true;
        }

        return $this->render('ecole_suggestion/ajouter.html.twig', [
            'form' => $form->createView(),
            'success' => $success,
        ]);
    }
} 
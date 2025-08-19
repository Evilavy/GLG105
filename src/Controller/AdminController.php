<?php

namespace App\Controller;

use App\Entity\Enfant;
use App\Entity\Ecole;
use App\Repository\EnfantRepository;
use App\Repository\EcoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(EnfantRepository $enfantRepository, EcoleRepository $ecoleRepository): Response
    {
        $enfantsEnAttente = $enfantRepository->findEnAttente();
        $ecolesEnAttente = $ecoleRepository->findEnAttente();
        
        return $this->render('admin/dashboard.html.twig', [
            'enfantsEnAttente' => $enfantsEnAttente,
            'ecolesEnAttente' => $ecolesEnAttente,
        ]);
    }

    #[Route('/enfants-en-attente', name: 'admin_enfants_attente')]
    public function enfantsEnAttente(EnfantRepository $enfantRepository): Response
    {
        $enfants = $enfantRepository->findEnAttente();

        return $this->render('admin/enfants-attente.html.twig', [
            'enfants' => $enfants,
        ]);
    }

    #[Route('/valider-enfant/{id}', name: 'admin_valider_enfant', methods: ['POST'])]
    public function validerEnfant(Enfant $enfant, EntityManagerInterface $entityManager): Response
    {
        $enfant->setValide(true);
        $entityManager->flush();

        $this->addFlash('success', 'Enfant validé avec succès !');
        return $this->redirectToRoute('admin_enfants_attente');
    }

    #[Route('/rejeter-enfant/{id}', name: 'admin_rejeter_enfant', methods: ['POST'])]
    public function rejeterEnfant(Enfant $enfant, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($enfant);
        $entityManager->flush();

        $this->addFlash('success', 'Enfant rejeté et supprimé.');
        return $this->redirectToRoute('admin_enfants_attente');
    }

    #[Route('/ecoles-en-attente', name: 'admin_ecoles_attente')]
    public function ecolesEnAttente(EcoleRepository $ecoleRepository): Response
    {
        $ecoles = $ecoleRepository->findEnAttente();

        return $this->render('admin/ecoles-attente.html.twig', [
            'ecoles' => $ecoles,
        ]);
    }

    #[Route('/ecoles/en-attente', name: 'admin_ecoles_attente_alt')]
    public function ecolesEnAttenteAlt(EcoleRepository $ecoleRepository): Response
    {
        // Redirection vers la route correcte
        return $this->redirectToRoute('admin_ecoles_attente');
    }

    #[Route('/valider-ecole/{id}', name: 'admin_valider_ecole', methods: ['POST'])]
    public function validerEcole(Ecole $ecole, EntityManagerInterface $entityManager): Response
    {
        $ecole->setValide(true);
        $entityManager->flush();

        $this->addFlash('success', 'École validée avec succès !');
        return $this->redirectToRoute('admin_ecoles_attente');
    }

    #[Route('/rejeter-ecole/{id}', name: 'admin_rejeter_ecole', methods: ['POST'])]
    public function rejeterEcole(Ecole $ecole, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($ecole);
        $entityManager->flush();

        $this->addFlash('success', 'École rejetée et supprimée.');
        return $this->redirectToRoute('admin_ecoles_attente');
    }

    #[Route('/ecoles', name: 'admin_ecoles_gestion')]
    public function gestionEcoles(EcoleRepository $ecoleRepository): Response
    {
        $ecoles = $ecoleRepository->findAll();

        return $this->render('admin/ecoles_gestion.html.twig', [
            'ecoles' => $ecoles,
        ]);
    }

    #[Route('/ecole/modifier/{id}', name: 'admin_ecole_modifier', methods: ['GET', 'POST'])]
    public function modifierEcole(Request $request, Ecole $ecole, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $ecole->setNom($request->request->get('nom'));
            $ecole->setAdresse($request->request->get('adresse'));
            $ecole->setCodePostal($request->request->get('codePostal'));
            $ecole->setVille($request->request->get('ville'));
            $ecole->setTelephone($request->request->get('telephone'));
            $ecole->setEmail($request->request->get('email'));
            $ecole->setValide($request->request->get('valide') === 'on');
            
            $entityManager->flush();

            $this->addFlash('success', 'École modifiée avec succès !');
            return $this->redirectToRoute('admin_ecoles_gestion');
        }

        return $this->render('admin/ecole_modifier.html.twig', [
            'ecole' => $ecole,
        ]);
    }

    #[Route('/ecole/supprimer/{id}', name: 'admin_ecole_supprimer', methods: ['POST'])]
    public function supprimerEcole(Ecole $ecole, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($ecole);
        $entityManager->flush();

        $this->addFlash('success', 'École supprimée avec succès !');
        return $this->redirectToRoute('admin_ecoles_gestion');
    }
} 
<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(UserRepository $userRepository): Response
    {
        $pendingUsers = $userRepository->findBy(['isApprovedByAdmin' => false]);
        $approvedUsers = $userRepository->findBy(['isApprovedByAdmin' => true]);

        return $this->render('admin/dashboard.html.twig', [
            'pendingUsers' => $pendingUsers,
            'approvedUsers' => $approvedUsers,
        ]);
    }

    #[Route('/users/pending', name: 'admin_pending_users')]
    public function pendingUsers(UserRepository $userRepository): Response
    {
        $pendingUsers = $userRepository->findBy(['isApprovedByAdmin' => false], ['createdAt' => 'DESC']);

        return $this->render('admin/pending_users.html.twig', [
            'pendingUsers' => $pendingUsers,
        ]);
    }

    #[Route('/users/{id}/approve', name: 'admin_approve_user', methods: ['POST'])]
    public function approveUser(int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $userRepository->find($id);
        
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur non trouvé.');
        }

        $user->setIsApprovedByAdmin(true);
        $user->setApprovedAt(new \DateTime());
        
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur approuvé avec succès.');

        return $this->redirectToRoute('admin_pending_users');
    }

    #[Route('/users/{id}/reject', name: 'admin_reject_user', methods: ['POST'])]
    public function rejectUser(int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $userRepository->find($id);
        
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur non trouvé.');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur rejeté et supprimé.');

        return $this->redirectToRoute('admin_pending_users');
    }

    #[Route('/users/{id}/view', name: 'admin_view_user')]
    public function viewUser(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur non trouvé.');
        }

        return $this->render('admin/view_user.html.twig', [
            'user' => $user,
        ]);
    }
} 
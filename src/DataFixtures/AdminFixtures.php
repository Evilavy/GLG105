<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Créer un utilisateur admin
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setNom('Admin');
        $admin->setPrenom('Administrateur');
        $admin->setVille('Paris');
        $admin->setRole('parent');
        $admin->setIsApprovedByAdmin(true);
        $admin->setIsVerified(true);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setCreatedAt(new \DateTime());
        $admin->setApprovedAt(new \DateTime());

        $manager->persist($admin);

        // Créer quelques utilisateurs de test
        $user1 = new User();
        $user1->setEmail('parent@example.com');
        $user1->setPassword($this->passwordHasher->hashPassword($user1, 'password123'));
        $user1->setNom('Dupont');
        $user1->setPrenom('Jean');
        $user1->setVille('Lyon');
        $user1->setRole('parent');
        $user1->setIsApprovedByAdmin(false);
        $user1->setIsVerified(false);
        $user1->setRoles(['ROLE_USER']);
        $user1->setCreatedAt(new \DateTime());

        $manager->persist($user1);

        $user2 = new User();
        $user2->setEmail('grandparent@example.com');
        $user2->setPassword($this->passwordHasher->hashPassword($user2, 'password123'));
        $user2->setNom('Martin');
        $user2->setPrenom('Marie');
        $user2->setVille('Marseille');
        $user2->setRole('grand_parent');
        $user2->setIsApprovedByAdmin(false);
        $user2->setIsVerified(false);
        $user2->setRoles(['ROLE_USER']);
        $user2->setCreatedAt(new \DateTime());

        $manager->persist($user2);

        $user3 = new User();
        $user3->setEmail('autre@example.com');
        $user3->setPassword($this->passwordHasher->hashPassword($user3, 'password123'));
        $user3->setNom('Durand');
        $user3->setPrenom('Pierre');
        $user3->setVille('Toulouse');
        $user3->setRole('autre');
        $user3->setRoleAutre('Tuteur');
        $user3->setIsApprovedByAdmin(false);
        $user3->setIsVerified(false);
        $user3->setRoles(['ROLE_USER']);
        $user3->setCreatedAt(new \DateTime());

        $manager->persist($user3);

        $manager->flush();
    }
} 
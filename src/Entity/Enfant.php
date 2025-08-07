<?php

namespace App\Entity;

use App\Repository\EnfantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnfantRepository::class)]
class Enfant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateNaissance = null;

    #[ORM\Column(length: 10)]
    private ?string $sexe = null;

    #[ORM\Column]
    private ?int $ecoleId = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $certificatScolarite = null;

    #[ORM\Column]
    private ?bool $valideParAdmin = false;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateCreation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTime $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(string $sexe): static
    {
        $this->sexe = $sexe;
        return $this;
    }

    public function getEcoleId(): ?int
    {
        return $this->ecoleId;
    }

    public function setEcoleId(int $ecoleId): static
    {
        $this->ecoleId = $ecoleId;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getCertificatScolarite(): ?string
    {
        return $this->certificatScolarite;
    }

    public function setCertificatScolarite(?string $certificatScolarite): static
    {
        $this->certificatScolarite = $certificatScolarite;
        return $this;
    }

    public function getValideParAdmin(): ?bool
    {
        return $this->valideParAdmin;
    }

    public function setValideParAdmin(bool $valideParAdmin): static
    {
        $this->valideParAdmin = $valideParAdmin;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getAge(): int
    {
        if ($this->dateNaissance === null) {
            return 0;
        }
        
        $now = new \DateTime();
        $diff = $now->diff($this->dateNaissance);
        return $diff->y;
    }
}

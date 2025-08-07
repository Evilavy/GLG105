<?php

namespace App\Entity;

use App\Repository\TrajetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrajetRepository::class)]
class Trajet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $pointDepart = null;

    #[ORM\Column(length: 255)]
    private ?string $pointArrivee = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(length: 10)]
    private ?string $heureDepart = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateArrivee = null;

    #[ORM\Column(length: 10)]
    private ?string $heureArrivee = null;

    #[ORM\Column]
    private ?int $nombrePlaces = null;

    #[ORM\Column]
    private ?int $conducteurId = null;

    #[ORM\Column]
    private ?int $voitureId = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $coutPoints = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $enfantsIds = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPointDepart(): ?string
    {
        return $this->pointDepart;
    }

    public function setPointDepart(string $pointDepart): static
    {
        $this->pointDepart = $pointDepart;
        return $this;
    }

    public function getPointArrivee(): ?string
    {
        return $this->pointArrivee;
    }

    public function setPointArrivee(string $pointArrivee): static
    {
        $this->pointArrivee = $pointArrivee;
        return $this;
    }

    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->dateDepart;
    }

    public function setDateDepart(\DateTimeInterface $dateDepart): static
    {
        $this->dateDepart = $dateDepart;
        return $this;
    }

    public function getHeureDepart(): ?string
    {
        return $this->heureDepart;
    }

    public function setHeureDepart(string $heureDepart): static
    {
        $this->heureDepart = $heureDepart;
        return $this;
    }

    public function getDateArrivee(): ?\DateTimeInterface
    {
        return $this->dateArrivee;
    }

    public function setDateArrivee(\DateTimeInterface $dateArrivee): static
    {
        $this->dateArrivee = $dateArrivee;
        return $this;
    }

    public function getHeureArrivee(): ?string
    {
        return $this->heureArrivee;
    }

    public function setHeureArrivee(string $heureArrivee): static
    {
        $this->heureArrivee = $heureArrivee;
        return $this;
    }

    public function getNombrePlaces(): ?int
    {
        return $this->nombrePlaces;
    }

    public function setNombrePlaces(int $nombrePlaces): static
    {
        $this->nombrePlaces = $nombrePlaces;
        return $this;
    }

    public function getConducteurId(): ?int
    {
        return $this->conducteurId;
    }

    public function setConducteurId(int $conducteurId): static
    {
        $this->conducteurId = $conducteurId;
        return $this;
    }

    public function getVoitureId(): ?int
    {
        return $this->voitureId;
    }

    public function setVoitureId(int $voitureId): static
    {
        $this->voitureId = $voitureId;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCoutPoints(): ?int
    {
        return $this->coutPoints;
    }

    public function setCoutPoints(int $coutPoints): static
    {
        $this->coutPoints = $coutPoints;
        return $this;
    }

    public function getEnfantsIds(): array
    {
        return $this->enfantsIds;
    }

    public function setEnfantsIds(array $enfantsIds): static
    {
        $this->enfantsIds = $enfantsIds;
        return $this;
    }
}

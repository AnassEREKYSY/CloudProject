<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Member $id_membre = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Vehicule $id_vehicule = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_heure_depart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateTime_heure_fin = null;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $prix_total = null;

    

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_enregistrement = null;    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdMembre(): ?Member
    {
        return $this->id_membre;
    }

    public function setIdMembre(?Member $id_membre): static
    {
        $this->id_membre = $id_membre;

        return $this;
    }

    public function getIdVehicule(): ?Vehicule
    {
        return $this->id_vehicule;
    }

    public function setIdVehicule(?Vehicule $id_vehicule): static
    {
        $this->id_vehicule = $id_vehicule;

        return $this;
    }

    public function getPrixTotal(): ?float
    {
        return $this->prix_total;
    }

    public function setPrixTotal(?float $prix_total): static
    {
        $this->prix_total = $prix_total;
        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeInterface
    {
        return $this->date_enregistrement;
    }

    public function setDateEnregistrement(?\DateTimeInterface $date_enregistrement): static
    {
        $this->date_enregistrement = $date_enregistrement;
        return $this;
    }

    public function getDateHeureDepart(): ?\DateTimeInterface
    {
        return $this->date_heure_depart;
    }

    public function setDateHeureDepart(?\DateTimeInterface $date_heure_depart): static
    {
        $this->date_heure_depart = $date_heure_depart;
        return $this;
    }

    public function getDateHeureFin(): ?\DateTimeInterface
    {
        return $this->dateTime_heure_fin;
    }

    public function setDateHeureFin(?\DateTimeInterface $dateTime_heure_fin): static
    {
        $this->dateTime_heure_fin = $dateTime_heure_fin;
        return $this;
    }



   
}

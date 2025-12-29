<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commandes')]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'client_id', type: 'integer', nullable: true)]
    private ?int $clientId = null;

    #[ORM\Column(name: 'date_commande', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateCommande = null;

    #[ORM\Column(name: 'livreur_id', type: 'integer', nullable: true)]
    private ?int $livreurId = null;

    #[ORM\Column(name: 'zone_id', type: 'integer', nullable: true)]
    private ?int $zoneId = null;

    #[ORM\Column(name: 'montant_total', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $montantTotal = null;

    #[ORM\Column(name: 'statut', type: 'string', nullable: true)]
    private ?string $statut = null;

  
    private ?Client $client = null;
    private ?Livreur $livreur = null;
    private ?Zone $zone = null;


    public function getId(): ?int { return $this->id; }
    
    public function getClientId(): ?int { return $this->clientId; }
    public function setClientId(?int $clientId): self { $this->clientId = $clientId; return $this; }
    
    public function getDateCommande(): ?\DateTimeInterface { return $this->dateCommande; }
    public function setDateCommande(?\DateTimeInterface $dateCommande): self { $this->dateCommande = $dateCommande; return $this; }
    
    public function getLivreurId(): ?int { return $this->livreurId; }
    public function setLivreurId(?int $livreurId): self { $this->livreurId = $livreurId; return $this; }
    
    public function getZoneId(): ?int { return $this->zoneId; }
    public function setZoneId(?int $zoneId): self { $this->zoneId = $zoneId; return $this; }
    
    public function getMontantTotal(): ?string { return $this->montantTotal; }
    public function setMontantTotal(?string $montantTotal): self { $this->montantTotal = $montantTotal; return $this; }
    
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(?string $statut): self { $this->statut = $statut; return $this; }

   
    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): self { $this->client = $client; return $this; }
    
    public function getLivreur(): ?Livreur { return $this->livreur; }
    public function setLivreur(?Livreur $livreur): self { $this->livreur = $livreur; return $this; }
    
    public function getZone(): ?Zone { return $this->zone; }
    public function setZone(?Zone $zone): self { $this->zone = $zone; return $this; }

  
    public function getEtat(): ?string { return $this->statut; }
    public function setEtat(?string $etat): self { $this->statut = $etat; return $this; }
    
    public function getTypeConsommation(): string 
    { 
        return $this->livreurId ? 'livraison' : 'emporter'; 
    }

    public function getNumero(): string
    {
        return 'CMD' . str_pad($this->id, 8, '0', STR_PAD_LEFT);
    }

    public function __toString(): string
    {
        return 'Commande #' . $this->id;
    }
}

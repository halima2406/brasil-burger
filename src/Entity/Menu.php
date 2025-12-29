<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: "menu")]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "nom", type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le nom du menu est obligatoire")]
    private string $nom = '';

    #[ORM\Column(name: "image", type: "string", length: 500, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: "burger_id", type: "integer")]
    private int $burgerId;

    #[ORM\Column(name: "boisson_id", type: "integer")]
    private int $boissonId;

    #[ORM\Column(name: "frite_id", type: "integer")]
    private int $friteId;

    #[ORM\Column(name: "est_archive", type: "boolean")]
    private bool $estArchive = false;

    
    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: "burger_id", referencedColumnName: "id", nullable: false)]
    private ?Produit $burger = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: "boisson_id", referencedColumnName: "id", nullable: false)]
    private ?Produit $boisson = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: "frite_id", referencedColumnName: "id", nullable: false)]
    private ?Produit $frite = null;

  
    public function getPrix(): float
    {
        $total = 0.0;
        
        if ($this->burger !== null) {
            $total += $this->burger->getPrix();
        }
        
        if ($this->boisson !== null) {
            $total += $this->boisson->getPrix();
        }
        
        if ($this->frite !== null) {
            $total += $this->frite->getPrix();
        }
        
        return $total;
    }

   
    public function getPrixCalcule(): float
    {
        return $this->getPrix();
    }

   
    public function getPrixFormate(): string
    {
        $prix = $this->getPrix();
        return number_format($prix, 0, ',', ' ') . ' FCFA';
    }

   
    public function getPrixAvecReduction(): float
    {
        $prixBase = $this->getPrix();
        return $prixBase * 0.95; 
    }

  

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getBurgerId(): int
    {
        return $this->burgerId;
    }

    public function setBurgerId(int $burgerId): static
    {
        $this->burgerId = $burgerId;
        return $this;
    }

    public function getBoissonId(): int
    {
        return $this->boissonId;
    }

    public function setBoissonId(int $boissonId): static
    {
        $this->boissonId = $boissonId;
        return $this;
    }

    public function getFriteId(): int
    {
        return $this->friteId;
    }

    public function setFriteId(int $friteId): static
    {
        $this->friteId = $friteId;
        return $this;
    }

    public function isEstArchive(): bool
    {
        return $this->estArchive;
    }

    public function setEstArchive(bool $estArchive): static
    {
        $this->estArchive = $estArchive;
        return $this;
    }

   
    public function getBurger(): ?Produit
    {
        return $this->burger;
    }

    public function setBurger(?Produit $burger): static
    {
        $this->burger = $burger;
        return $this;
    }

    public function getBoisson(): ?Produit
    {
        return $this->boisson;
    }

    public function setBoisson(?Produit $boisson): static
    {
        $this->boisson = $boisson;
        return $this;
    }

    public function getFrite(): ?Produit
    {
        return $this->frite;
    }

    public function setFrite(?Produit $frite): static
    {
        $this->frite = $frite;
        return $this;
    }

    
    public function getDetailCalcul(): array
    {
        return [
            'menu_nom' => $this->nom,
            'burger' => $this->burger ? $this->burger->getNom() . ' (' . $this->burger->getPrix() . ' FCFA)' : 'Aucun',
            'boisson' => $this->boisson ? $this->boisson->getNom() . ' (' . $this->boisson->getPrix() . ' FCFA)' : 'Aucune',
            'frite' => $this->frite ? $this->frite->getNom() . ' (' . $this->frite->getPrix() . ' FCFA)' : 'Aucune',
            'prix_total' => $this->getPrix(),
            'prix_formate' => $this->getPrixFormate()
        ];
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->nom, $this->getPrixFormate());
    }
}
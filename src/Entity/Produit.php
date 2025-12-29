<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: "produit")]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "nom", type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le nom du produit est obligatoire")]
    private string $nom = '';

    #[ORM\Column(name: "prix", type: "decimal", precision: 10, scale: 2)]
    #[Assert\PositiveOrZero(message: "Le prix doit être positif")]
    private float $prix = 0.0;

    #[ORM\Column(name: "image", type: "string", length: 500, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: "type_produit", type: "string", length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(
        choices: ['BURGER', 'COMPLEMENT'],
        message: "Le type de produit doit être BURGER ou COMPLEMENT"
    )]
    private string $typeProduit = '';

    #[ORM\Column(name: "type_complement", type: "string", length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ['FRITE', 'BOISSON'],
        message: "Le type de complément doit être FRITE ou BOISSON"
    )]
    private ?string $typeComplement = null;

    #[ORM\Column(name: "est_archive", type: "boolean")]
    private bool $estArchive = false;

  
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

    public function getPrix(): float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getPrixFormate(): string
    {
        return number_format($this->prix, 0, ',', ' ') . ' FCFA';
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

    public function getTypeProduit(): string
    {
        return $this->typeProduit;
    }

    public function setTypeProduit(string $typeProduit): static
    {
        $this->typeProduit = $typeProduit;
        return $this;
    }

    public function getTypeComplement(): ?string
    {
        return $this->typeComplement;
    }

    public function setTypeComplement(?string $typeComplement): static
    {
        $this->typeComplement = $typeComplement;
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

    
    public function isBurger(): bool
    {
        return $this->typeProduit === 'BURGER';
    }

    
    public function isComplement(): bool
    {
        return $this->typeProduit === 'COMPLEMENT';
    }

   
    public function isBoisson(): bool
    {
        return $this->isComplement() && $this->typeComplement === 'BOISSON';
    }

    
    public function isFrite(): bool
    {
        return $this->isComplement() && $this->typeComplement === 'FRITE';
    }

    public function __toString(): string
    {
        $type = $this->isBurger() ? 'Burger' : $this->typeComplement ?? 'Produit';
        return sprintf('%s (%s - %s)', $this->nom, $type, $this->getPrixFormate());
    }
}
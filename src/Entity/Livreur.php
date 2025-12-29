<?php

namespace App\Entity;

use App\Repository\LivreurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LivreurRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Livreur
{
    #[ORM\Id]
    #[ORM\Column(length: 30)]
    private string $numero;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom complet est obligatoire")]
    private ?string $nomComplet = null;

    #[ORM\Column(length: 15, unique: true)]
    #[Assert\NotBlank(message: "Le téléphone est obligatoire")]
    private ?string $telephone = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide.")]
    private ?string $email = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updateAt = null;

    #[ORM\Column(name: "is_deleted", options: ["default" => false])]
    private bool $isDeleted = false;

    #[ORM\Column(name: "is_active", options: ["default" => true])]
    private bool $isActive = true;

    #[ORM\Column(name: "disponible", options: ["default" => true])]
    private bool $disponible = true;

    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'livreur')]
    private Collection $commandes;

    public function __construct()
    {
        $this->commandes = new ArrayCollection();
        $this->isDeleted = false;
        $this->isActive = true;
        $this->disponible = true;
    }

    #[ORM\PrePersist]
    public function initDates(): void
    {
        if ($this->createAt === null) {
            $this->createAt = new \DateTimeImmutable();
        }
    }

    // --- getters/setters ---
    public function getNumero(): string { return $this->numero; }
    public function setNumero(string $numero): static { $this->numero = $numero; return $this; }

    public function getNomComplet(): string { return $this->nomComplet ?? ''; }
    public function setNomComplet(string $nomComplet): static { $this->nomComplet = $nomComplet; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getCreateAt(): ?\DateTimeImmutable { return $this->createAt; }
    public function setCreateAt(\DateTimeImmutable $createAt): static { $this->createAt = $createAt; return $this; }

    public function getUpdateAt(): ?\DateTimeImmutable { return $this->updateAt; }
    public function setUpdateAt(?\DateTimeImmutable $updateAt): static { $this->updateAt = $updateAt; return $this; }

    public function isDeleted(): bool { return $this->isDeleted; }
    public function setIsDeleted(bool $isDeleted): static { $this->isDeleted = $isDeleted; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function isDisponible(): bool { return $this->disponible; }
    public function setDisponible(bool $disponible): static { $this->disponible = $disponible; return $this; }

   
    public function getCommandes(): Collection { return $this->commandes; }

    public function addCommande(Commande $commande): static
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setLivreur($this);
        }
        return $this;
    }

    public function removeCommande(Commande $commande): static
    {
        if ($this->commandes->removeElement($commande)) {
            if ($commande->getLivreur() === $this) {
                $commande->setLivreur(null);
            }
        }
        return $this;
    }

    public function getNbCommandesEnCours(): int
    {
        return $this->commandes->filter(function($commande) {
            return in_array($commande->getEtat(), ['en_cours', 'validee']);
        })->count();
    }

    public function __toString(): string
    {
        return $this->numero.' - '.($this->nomComplet ?? $this->email);
    }
}

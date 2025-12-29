<?php

namespace App\Entity;

use App\Repository\ZoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ZoneRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Zone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom de la zone est obligatoire")]
    private ?string $nom = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $quartiers = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    #[Assert\NotBlank(message: "Le prix de livraison est obligatoire")]
    #[Assert\PositiveOrZero(message: "Le prix doit être positif")]
    private ?string $prixLivraison = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updateAt = null;

    #[ORM\Column(name: "is_deleted", options: ["default" => false])]
    private bool $isDeleted = false;

    #[ORM\Column(name: "is_active", options: ["default" => true])]
    private bool $isActive = true;

    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'zone')]
    private Collection $commandes;

    public function __construct()
    {
        $this->commandes = new ArrayCollection();
        $this->isDeleted = false;
        $this->isActive = true;
    }

    #[ORM\PrePersist]
    public function initDates(): void
    {
        if ($this->createAt === null) {
            $this->createAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getQuartiers(): ?string { return $this->quartiers; }
    public function setQuartiers(?string $quartiers): self { $this->quartiers = $quartiers; return $this; }

    public function getPrixLivraison(): ?string { return $this->prixLivraison; }
    public function setPrixLivraison(string $prixLivraison): self { $this->prixLivraison = $prixLivraison; return $this; }

    public function getCreateAt(): ?\DateTimeImmutable { return $this->createAt; }
    public function setCreateAt(\DateTimeImmutable $createAt): self { $this->createAt = $createAt; return $this; }

    public function getUpdateAt(): ?\DateTimeImmutable { return $this->updateAt; }
    public function setUpdateAt(?\DateTimeImmutable $updateAt): self { $this->updateAt = $updateAt; return $this; }

    public function isDeleted(): bool { return $this->isDeleted; }
    public function setIsDeleted(bool $isDeleted): self { $this->isDeleted = $isDeleted; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }

    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection { return $this->commandes; }

    public function addCommande(Commande $commande): self
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setZone($this);
        }
        return $this;
    }

    public function removeCommande(Commande $commande): self
    {
        if ($this->commandes->removeElement($commande)) {
            if ($commande->getZone() === $this) {
                $commande->setZone(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }

    // Pour la compatibilité avec l'ancien code
    public function getQuartier(): ?string { return $this->getNom(); }
    public function setQuartier(string $quartier): self { return $this->setNom($quartier); }
    public function getPrix(): ?string { return $this->getPrixLivraison(); }
    public function setPrix(string $prix): self { return $this->setPrixLivraison($prix); }
}

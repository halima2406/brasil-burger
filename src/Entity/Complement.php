<?php

namespace App\Entity;

use App\Repository\ComplementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ComplementRepository::class)]
class Complement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom du complément est obligatoire")]
    private ?string $nom = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: "Le prix est obligatoire")]
    #[Assert\Positive(message: "Le prix doit être positif")]
    private ?string $prix = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // frites, boisson, sauce, dessert

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $disponible = true;

    #[ORM\Column]
    private bool $archive = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\ManyToMany(targetEntity: Menu::class, mappedBy: 'complements')]
    private Collection $menus;

    public function __construct()
    {
        $this->menus = new ArrayCollection();
        $this->createAt = new \DateTimeImmutable();
        $this->disponible = true;
        $this->archive = false;
    }

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

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function isDisponible(): bool
    {
        return $this->disponible;
    }

    public function setDisponible(bool $disponible): static
    {
        $this->disponible = $disponible;
        return $this;
    }

    public function isArchive(): bool
    {
        return $this->archive;
    }

    public function setArchive(bool $archive): static
    {
        $this->archive = $archive;
        return $this;
    }

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeImmutable $createAt): static
    {
        $this->createAt = $createAt;
        return $this;
    }

    /**
     * @return Collection<int, Menu>
     */
    public function getMenus(): Collection
    {
        return $this->menus;
    }

    public function addMenu(Menu $menu): static
    {
        if (!$this->menus->contains($menu)) {
            $this->menus->add($menu);
            $menu->addComplement($this);
        }
        return $this;
    }

    public function removeMenu(Menu $menu): static
    {
        if ($this->menus->removeElement($menu)) {
            $menu->removeComplement($this);
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}

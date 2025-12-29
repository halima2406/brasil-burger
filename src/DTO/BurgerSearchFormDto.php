<?php

namespace App\DTO;

class BurgerSearchFormDto
{
    public ?string $nom = null;
    public ?string $statut = null;
    public ?float $prixMin = null;
    public ?float $prixMax = null;

    public function isArchived(): ?bool
    {
        if (empty($this->statut)) {
            return null;
        }
        return $this->statut === 'archive';
    }

    public function isDisponible(): ?bool
    {
        if (empty($this->statut)) {
            return null;
        }
        return $this->statut === 'disponible';
    }

    public function hasFilters(): bool
    {
        return !empty($this->nom) || !empty($this->statut) || 
               $this->prixMin !== null || $this->prixMax !== null;
    }
}
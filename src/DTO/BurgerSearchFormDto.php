<?php

namespace App\DTO;

class BurgerSearchFormDto
{
    public ?string $nom = null;
    public ?string $statut = null;
    public ?float $prixMin = null;
    public ?float $prixMax = null;

    /** Helper: renvoie null (pas de filtre), true = archivé, false = actif */
    public function archivedRequested(): ?bool
    {
        if ($this->statut === null || $this->statut === '') {
            return null;
        }
        return $this->statut === 'archive';
    }

    /** Helper: renvoie null (pas de filtre), true = disponible, false = indisponible */
    public function disponibleRequested(): ?bool
    {
        if ($this->statut === null || $this->statut === '') {
            return null;
        }
        return $this->statut === 'disponible';
    }
}

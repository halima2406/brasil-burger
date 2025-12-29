<?php

namespace App\DTO;

class CommandeSearchFormDto
{
    public ?string $numero = null;
    public ?string $statut = null;
    public ?string $typeConsommation = null;
    public ?int $clientId = null;
    public ?string $dateDebut = null;
    public ?string $dateFin = null;

    public function hasFilters(): bool
    {
        return !empty($this->numero) || !empty($this->statut) || 
               !empty($this->typeConsommation) || $this->clientId !== null ||
               !empty($this->dateDebut) || !empty($this->dateFin);
    }

    public function hasDateRange(): bool
    {
        return !empty($this->dateDebut) && !empty($this->dateFin);
    }

    public function isValidDateRange(): bool
    {
        if (!$this->hasDateRange()) {
            return true;
        }
        
        return strtotime($this->dateDebut) <= strtotime($this->dateFin);
    }
}
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
        return !empty($this->numero) || 
               !empty($this->statut) || 
               !empty($this->typeConsommation) || 
               !empty($this->clientId) || 
               !empty($this->dateDebut) || 
               !empty($this->dateFin);
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

        $debut = new \DateTime($this->dateDebut);
        $fin = new \DateTime($this->dateFin);
        
        return $debut <= $fin;
    }
}
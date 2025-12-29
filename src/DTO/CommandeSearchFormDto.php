<?php

namespace App\DTO;

use App\Entity\Client;

class CommandeSearchFormDto
{
    public ?string $numero = null;
    public ?string $statut = null;
    public ?string $typeConsommation = null;
    public ?Client $client = null;
    public ?string $dateDebut = null;
    public ?string $dateFin = null;
}

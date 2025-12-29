<?php

namespace App\Service\Impl;

use App\Service\GenerateNumeroService;

class GenerateNumeroServiceImpl implements GenerateNumeroService
{
    public function generateNumeroCommande(): string
    {
        return 'CMD' . date('Ymd') . rand(1000, 9999);
    }

    public function generateNumeroClient(): string
    {
        return 'CLI' . rand(10000, 99999);
    }
}
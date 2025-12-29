<?php

namespace App\Service\Impl;

use App\Service\GenerateNumeroService;

class GenerateNumeroServiceImpl implements GenerateNumeroService
{
    public function generateNumeroCommande(): string
    {
        return 'CMD' . date('Ymd') . strtoupper(bin2hex(random_bytes(3)));
    }

    public function generateNumeroClient(): string
    {
        return 'CLI' . strtoupper(bin2hex(random_bytes(4)));
    }
}

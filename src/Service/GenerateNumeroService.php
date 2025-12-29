<?php

namespace App\Service;

interface GenerateNumeroService
{
    public function generateNumeroCommande(): string;
    public function generateNumeroClient(): string;
}
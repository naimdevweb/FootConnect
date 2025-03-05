<?php

namespace App\Interfaces;

use App\Entity\User;

interface UpdateProfilInterface
{
    public function updateProfil(User $user, string $pseudo, string $email , string $plainPassword): void;
}
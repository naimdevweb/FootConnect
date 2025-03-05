<?php
namespace App\Interfaces;

use App\Entity\User;

interface ProfilCountInterface
{
    public function countFollowers(User $user): int;
    public function countFollowing(User $user): int;
}
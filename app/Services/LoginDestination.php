<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;

class LoginDestination
{
    public function for(User $user, ?string $intended = null): string
    {
        if ($this->isSafeFor($user, $intended)) {
            return $intended;
        }

        return $user->role === UserRole::User
            ? route('portal', absolute: false)
            : route('dashboard', absolute: false);
    }

    private function isSafeFor(User $user, ?string $intended): bool
    {
        if ($intended === null || ! str_starts_with($intended, '/') || str_starts_with($intended, '//')) {
            return false;
        }

        $path = rtrim((string) parse_url($intended, PHP_URL_PATH), '/') ?: '/';

        if (in_array($path, ['/login', '/register'], true)) {
            return false;
        }

        return $user->role !== UserRole::User || $path !== '/tickets';
    }
}

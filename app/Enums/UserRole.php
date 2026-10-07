<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Staff = 'staff';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Staff => 'Support Staff',
            self::User => 'User',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Admin => 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
            self::Staff => 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
            self::User => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        };
    }
}

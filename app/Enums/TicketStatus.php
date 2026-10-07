<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::Pending => 'Pending',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Open => 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
            self::InProgress => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
            self::Pending => 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300',
            self::Resolved => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            self::Closed => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        };
    }

    public function canTransitionTo(self $next, UserRole $actorRole): bool
    {
        if ($this === $next) {
            return false;
        }

        if ($actorRole === UserRole::Admin) {
            return $this !== self::Closed || $next === self::Open;
        }

        if ($actorRole === UserRole::User) {
            return $this === self::Resolved && $next === self::Closed;
        }

        if ($this === self::Closed || $next === self::Closed) {
            return false;
        }

        return true;
    }
}

<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            self::Medium => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
            self::High => 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300',
            self::Urgent => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
        };
    }
}

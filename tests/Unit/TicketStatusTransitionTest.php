<?php

namespace Tests\Unit;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TicketStatusTransitionTest extends TestCase
{
    #[DataProvider('transitions')]
    public function test_role_aware_transitions(TicketStatus $from, TicketStatus $to, UserRole $role, bool $allowed): void
    {
        $this->assertSame($allowed, $from->canTransitionTo($to, $role));
    }

    /** @return array<string, array{TicketStatus, TicketStatus, UserRole, bool}> */
    public static function transitions(): array
    {
        return [
            'staff progresses work' => [TicketStatus::Open, TicketStatus::InProgress, UserRole::Staff, true],
            'staff resolves work' => [TicketStatus::Pending, TicketStatus::Resolved, UserRole::Staff, true],
            'staff cannot close' => [TicketStatus::Resolved, TicketStatus::Closed, UserRole::Staff, false],
            'staff cannot reopen closed' => [TicketStatus::Closed, TicketStatus::Open, UserRole::Staff, false],
            'owner closes resolved' => [TicketStatus::Resolved, TicketStatus::Closed, UserRole::User, true],
            'owner cannot alter open' => [TicketStatus::Open, TicketStatus::Pending, UserRole::User, false],
            'admin closes resolved' => [TicketStatus::Resolved, TicketStatus::Closed, UserRole::Admin, true],
            'admin reopens closed' => [TicketStatus::Closed, TicketStatus::Open, UserRole::Admin, true],
            'admin cannot reopen closed to pending' => [TicketStatus::Closed, TicketStatus::Pending, UserRole::Admin, false],
            'no role submits a no-op' => [TicketStatus::Open, TicketStatus::Open, UserRole::Admin, false],
        ];
    }
}

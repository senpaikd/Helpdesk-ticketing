<?php

namespace Tests\Unit;

use App\Enums\TicketActivityAction;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function test_roles_use_the_persisted_values_expected_by_authorization(): void
    {
        $this->assertSame(['admin', 'staff', 'user'], array_column(UserRole::cases(), 'value'));
        $this->assertSame('Support Staff', UserRole::Staff->label());
    }

    public function test_ticket_statuses_use_stable_values_and_human_labels(): void
    {
        $this->assertSame(
            ['open', 'in_progress', 'pending', 'resolved', 'closed'],
            array_column(TicketStatus::cases(), 'value'),
        );
        $this->assertSame('In Progress', TicketStatus::InProgress->label());
    }

    public function test_priorities_use_stable_values(): void
    {
        $this->assertSame(
            ['low', 'medium', 'high', 'urgent'],
            array_column(TicketPriority::cases(), 'value'),
        );
    }

    public function test_activity_actions_cover_audited_ticket_changes(): void
    {
        $this->assertSame(
            [
                'created',
                'claimed',
                'assigned',
                'status_changed',
                'priority_changed',
                'resolved',
                'closed',
                'reopened',
                'comment_added',
                'attachment_added',
            ],
            array_column(TicketActivityAction::cases(), 'value'),
        );
    }

    #[DataProvider('displayableEnums')]
    public function test_displayable_enum_cases_provide_badge_classes(object $case): void
    {
        $this->assertNotSame('', $case->label());
        $this->assertStringContainsString('bg-', $case->badgeClasses());
        $this->assertStringContainsString('text-', $case->badgeClasses());
    }

    /** @return array<string, array{0: object}> */
    public static function displayableEnums(): array
    {
        return [
            'admin role' => [UserRole::Admin],
            'open status' => [TicketStatus::Open],
            'urgent priority' => [TicketPriority::Urgent],
        ];
    }
}

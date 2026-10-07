<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ticket> */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'ticket_number' => 'TKT-'.now()->format('Y').'-'.fake()->unique()->numerify('#####'),
            'user_id' => User::factory(),
            'assigned_to' => null,
            'category_id' => Category::factory(),
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraphs(2, true),
            'priority' => TicketPriority::Medium,
            'status' => TicketStatus::Open,
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }
}

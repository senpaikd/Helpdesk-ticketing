<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seeder_creates_configuration_without_example_accounts_or_tickets(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('categories', 6);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseHas('categories', ['name' => 'Hardware', 'is_active' => true]);
        $this->assertDatabaseHas('categories', ['name' => 'Account Access', 'is_active' => true]);
    }
}

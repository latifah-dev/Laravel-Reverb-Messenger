<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Laravel\Pest\Factories\TestCase;

it('can search users', function () {
    $this->seed(DatabaseSeeder::class);
    $user1 = User::factory()->create(['name' => 'John Doe']);

    $response = $this
                ->actingAs($user1)
                ->getJson('/api/users/search?query=test');
                info($response->json());
    $response->assertJsonCount(1, 'users')
        ->assertJson(['users' => [['name' => 'Test User']]]);
    $response->assertStatus(200);
});

it('return empty array when no user found', function () {
    $this->seed(DatabaseSeeder::class);
    $user1 = User::factory()->create(['name' => 'John Doe']);

    $response = $this
                ->actingAs($user1)
                ->getJson('/api/users/search?query=tidak ada');
    $response->assertJsonCount(0, 'users')
        ->assertJson(['users' => []]);
    $response->assertStatus(200);
});

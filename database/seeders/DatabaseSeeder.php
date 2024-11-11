<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Message;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed users
        User::factory(10)->create();

        // Optional specific test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '12345678',
        ]);

        // Ensure we have at least two users for messages
        $sender = User::first();
        $receiver = User::skip(1)->first();

        if (!$sender || !$receiver) {
            $this->command->warn('Tidak cukup user untuk membuat pesan. Pastikan ada setidaknya dua user di database.');
            return;
        }

        // Seed 10 messages with different created_at timestamps
        for ($i = 0; $i < 10; $i++) {
            Message::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'content' => "Ini adalah pesan ke-" . ($i + 1),
                'created_at' => Carbon::now()->subDays(10 - $i),
                'updated_at' => Carbon::now()->subDays(10 - $i),
            ]);
        }
    }
}

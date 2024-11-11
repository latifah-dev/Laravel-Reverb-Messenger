<?php

use App\Models\Message;
use App\Models\User;

test('Authenticated User can view Chat Page', function () {
    $user = User::factory()->create();
    $response = $this
                ->actingAs($user)
                ->get('/chats');

    $response->assertStatus(200);
});

test('Unauthenticated User can not view Chat Page', function () {
    $response = $this
                ->get('/chats');

    $response->assertRedirect(route('login'));
});

it('Authenticated can send a message', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $response = $this
                ->actingAs($sender)
                ->post(route('chat.store', $receiver), [
                    'message' => 'Test message',
                ]);

    $response->assertRedirect();
    $this->assertCount(1, Message::all());
    $this->assertEquals('Test message', Message::first()->content);
});

it('Unauthenticated can send a message', function () {
    $receiver = User::factory()->create();

    $response = $this
                ->post(route('chat.store', $receiver), [
                    'message' => 'Test message',
                ]);

    $response->assertRedirect(route('login'));
});

it('An authenticated user can delete their messages.', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $message = $sender->sendMessages()->create([
        'receiver_id' => $receiver->id,
        'content' => 'This is a test'
    ]);

    $this->assertNull(Message::first()->deleted_at);

    $response = $this
                ->actingAs($sender)
                ->delete(route('chat.destroy', $message));

    $response->assertRedirect();
    $this->assertNotNull(Message::first()->deleted_at);
});

it('An unauthenticated user can not delete messages.', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $message = $sender->sendMessages()->create([
        'receiver_id' => $receiver->id,
        'content' => 'This is a test'
    ]);

    $this->assertNull(Message::first()->deleted_at);

    $response = $this
                ->delete(route('chat.destroy', $message));

    $response->assertRedirect(route('login'));
});

test('can not delete a message sent by another user', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $message = $sender->sendMessages()->create([
        'receiver_id' => $receiver->id,
        'content' => 'This is a test'
    ]);

    $this->assertNull(Message::first()->deleted_at);

    $response = $this
                ->actingAs($receiver)
                ->delete(route('chat.destroy', $message));

    $response->assertStatus(403);
});

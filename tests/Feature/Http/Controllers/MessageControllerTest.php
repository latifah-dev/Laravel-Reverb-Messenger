<?php

use App\Models\User;

test('Chat Index page can be rendered', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/chats');

    $response->assertOk();
});

test('Unauthorized User can not view chat page', function () {
    $response = $this
        ->get('/chats');

    $response->assertRedirect(route('login'));
});

<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('message-sent-channel.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Response;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index() : Response
    {
        return inertia('Chat/Index', [
            "users" => User::query()->get()
        ]);
    }
}

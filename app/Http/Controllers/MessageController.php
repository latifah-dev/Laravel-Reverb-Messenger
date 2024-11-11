<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class MessageController extends Controller
{
    public function index(): Response
    {
        return inertia('Chat/Index', [
            "users" => $this->getUser()
        ]);
    }

    public function show(User $user): Response
    {
        // dd($this->getMessages($user));
        return inertia('Chat/Show', [
            'users' => $this->getUser(),
            'chat_with' => $user,
            'messages' => $this->getMessages($user),
        ]);
    }

    public function store(User $user, Request $request): RedirectResponse
    {
        /** @var User $authUser */
        $authUser = auth()->user();
        $authUser->sendMessages()->create([
            'content' => $request->input("message"),
            'receiver_id' => $user->id,
            'reply_id' => $request->reply_id
        ]);

        return back();
    }

    public function destroy(Message $message)
    {
        if ($message->sender_id !== auth()->id()) {
            abort(403);
        }

        tap($message)->update([
            'deleted_at' => now(),
        ]);

        return redirect()->back();
    }

    private function getMessages($user)
    {
        $messages = Message::query()
                        ->where(fn($q) => $q->where('sender_id', auth()->user()->id)->where('receiver_id', $user->id))
                        ->orWhere(fn($q) => $q->where('sender_id', $user->id)->where('receiver_id', auth()->user()->id))
                        ->get()
                        ->groupBy(function ($message){
                            return $message->created_at->isToday() ? 'Today' : ($message->created_at->isYesterday() ? 'Yesterday' : $message->created_at->format('F j, Y'));
                        })
                        ->map(function($messages, $date){
                            return [
                                'date' => $date,
                                'messages' => $messages,
                            ];
                        })
                        ->values()
                        ->toArray();

        return $messages;
    }

    private function getUser() {
        return User::query()->where('id','!=', auth()->user()->id)
        ->get();
    }
}

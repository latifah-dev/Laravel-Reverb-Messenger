<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Response;
use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Http\Request;

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
        return inertia('Chat/Show', [
            "chat_with" => $user,
            "messages" => Message::query()
                ->where(fn ($q) => $q->where('sender_id', auth()->user()->id)->where('receiver_id', $user->id))
                ->orWhere(fn ($q) => $q->where('sender_id', $user->id)->where('receiver_id', auth()->user()->id))
                ->get()
                ->groupBy(function ($message) {
                    return $message->created_at->isToday() ? "Today" : ($message->created_at->isYesterday() ? "Yesterday" : $message->created_at->format("F j, Y"));
                })
                ->map(function ($messages, $date) {
                    return [
                        "messages" => $messages,
                        "date" => $date
                    ];
                })
                ->values(),
            "users" => $this->getUser()
        ]);
    }

    public function store(User $user, Request $request)
    {
        /** @var User $authUser */
        $authUser = auth()->user();
        $authUser->sendMessages()->create([
            "content" => $request->message,
            "receiver_id" => $user->id,
        ]);

        broadcast(new MessageSent($request->message))->toOthers();

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

        return back();
    }

    private function getUser()
    {
        return User::query()
                ->whereHas('sendMessages', function($query){
                    $query->where('receiver_id', auth()->user()->id);
                })
                ->orWhereHas('receiveMessages', function($query){
                    $query->where('sender_id', auth()->user()->id);
                })
                ->withCount(['sendMessages' => fn($query) => $query->where('receiver_id', auth()->id())->whereNull('seen_at')])
                ->with([
                    'sendMessages' => function ($query) {
                        $query->whereIn('id', function ($query) {
                            $query->selectRaw('max(id)')
                                ->from('messages')
                                ->where('receiver_id', auth()->id())
                                ->groupBy('sender_id');
                        });
                    },
                    'receiveMessages' => function ($query) {
                        $query->whereIn('id', function ($query) {
                            $query->selectRaw('max(id)')
                                ->from('messages')
                                ->where('sender_id', auth()->id())
                                ->groupBy('receiver_id');
                        });
                    },
                ])
                ->orderByDesc(function ($query) {
                    $query->select('created_at')
                        ->from('messages')
                        ->whereColumn('sender_id', 'users.id')
                        ->orWhereColumn('receiver_id', 'users.id')
                        ->orderByDesc('created_at')
                        ->limit(1);
                })
                ->get();
    }
}

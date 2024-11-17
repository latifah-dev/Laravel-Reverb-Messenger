<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Response;
use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

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
            'users' => $this->getUser(),
            'chat_with' => $user,
            'messages' => $this->getMessages($user),
        ]);
    }

    public function store(User $user, Request $request): RedirectResponse
    {
        /** @var User $authUser */
        $authUser = auth()->user();
        $message = $authUser->sendMessages()->create([
            'content' => $request->input("message"),
            'receiver_id' => $user->id,
            'reply_id' => $request->reply_id
        ]);

        broadcast(new MessageSent($message->load('receiver')))->toOthers();

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

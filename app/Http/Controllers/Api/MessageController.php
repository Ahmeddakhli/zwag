<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function conversations(Request $request)
    {
        try {
            $user = $request->user();
            
            $conversations = Conversation::where(function($query) use ($user) {
                                    $query->where('sender_id', $user->id)
                                          ->orWhere('receiver_id', $user->id);
                                })
                                ->with(['sender:id,firstname,lastname,image', 'receiver:id,firstname,lastname,image'])
                                ->withCount(['messages'])
                                ->latest('updated_at')
                                ->paginate(20);

            // Add unread count for each conversation
            $conversations->getCollection()->transform(function($conversation) use ($user) {
                $otherUser = $conversation->sender_id == $user->id ? $conversation->receiver : $conversation->sender;
                $conversation->other_user = $otherUser;
                $conversation->unread_count = $conversation->messages()
                                                          ->where('sender_id', '!=', $user->id)
                                                          ->where('is_read', 0)
                                                          ->count();
                $conversation->last_message = $conversation->messages()->latest()->first();
                return $conversation;
            });

            return response()->json([
                'success' => true,
                'data' => $conversations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get conversations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function messages(Request $request, $conversationId)
    {
        try {
            $user = $request->user();
            
            // Verify user has access to this conversation
            $conversation = Conversation::where('id', $conversationId)
                                      ->where(function($query) use ($user) {
                                          $query->where('sender_id', $user->id)
                                                ->orWhere('receiver_id', $user->id);
                                      })
                                      ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found'
                ], 404);
            }

            $messages = Message::where('conversation_id', $conversationId)
                              ->with('sender:id,firstname,lastname,image')
                              ->orderBy('created_at', 'desc')
                              ->paginate(50);

            // Mark messages as read
            Message::where('conversation_id', $conversationId)
                  ->where('sender_id', '!=', $user->id)
                  ->where('is_read', 0)
                  ->update(['is_read' => 1]);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation' => $conversation,
                    'messages' => $messages
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get messages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|exists:conversations,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $receiverId = $request->receiver_id;

            // Check if receiver exists and is active
            $receiver = User::where('id', $receiverId)->where('status', 1)->first();
            if (!$receiver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Receiver not found or inactive'
                ], 404);
            }

            // Get or create conversation
            $conversation = null;
            if ($request->conversation_id) {
                $conversation = Conversation::where('id', $request->conversation_id)
                                          ->where(function($query) use ($user, $receiverId) {
                                              $query->where(function($q) use ($user, $receiverId) {
                                                  $q->where('sender_id', $user->id)
                                                    ->where('receiver_id', $receiverId);
                                              })->orWhere(function($q) use ($user, $receiverId) {
                                                  $q->where('sender_id', $receiverId)
                                                    ->where('receiver_id', $user->id);
                                              });
                                          })
                                          ->first();
            }

            if (!$conversation) {
                $conversation = Conversation::firstOrCreate([
                    'sender_id' => $user->id,
                    'receiver_id' => $receiverId
                ]);
            }

            // Create message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'message' => $request->message,
                'is_read' => 0
            ]);

            // Update conversation timestamp
            $conversation->touch();

            // Load sender information
            $message->load('sender:id,firstname,lastname,image');

            // Send notification to receiver
            notify($receiver, 'NEW_MESSAGE', [
                'sender_name' => $user->fullname,
                'message' => substr($request->message, 0, 100),
                'conversation_link' => route('user.message.index', $conversation->id)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'message' => $message,
                    'conversation_id' => $conversation->id
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateMessage(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $message = Message::where('id', $id)
                             ->where('sender_id', $user->id)
                             ->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found'
                ], 404);
            }

            // Check if message was sent within last 5 minutes (edit window)
            if ($message->created_at->diffInMinutes() > 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message can only be edited within 5 minutes of sending'
                ], 422);
            }

            $message->message = $request->message;
            $message->is_edited = 1;
            $message->save();

            return response()->json([
                'success' => true,
                'message' => 'Message updated successfully',
                'data' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteMessage(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $message = Message::where('id', $id)
                             ->where('sender_id', $user->id)
                             ->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found'
                ], 404);
            }

            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsRead(Request $request, $conversationId)
    {
        try {
            $user = $request->user();

            // Verify user has access to this conversation
            $conversation = Conversation::where('id', $conversationId)
                                      ->where(function($query) use ($user) {
                                          $query->where('sender_id', $user->id)
                                                ->orWhere('receiver_id', $user->id);
                                      })
                                      ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found'
                ], 404);
            }

            // Mark all messages in conversation as read
            Message::where('conversation_id', $conversationId)
                  ->where('sender_id', '!=', $user->id)
                  ->where('is_read', 0)
                  ->update(['is_read' => 1]);

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $query = $request->query;

            $users = User::where('id', '!=', $user->id)
                        ->where('status', 1)
                        ->where(function($q) use ($query) {
                            $q->where('firstname', 'like', '%' . $query . '%')
                              ->orWhere('lastname', 'like', '%' . $query . '%')
                              ->orWhere('email', 'like', '%' . $query . '%');
                        })
                        ->select('id', 'firstname', 'lastname', 'image', 'city', 'state')
                        ->limit(20)
                        ->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unreadCount(Request $request)
    {
        try {
            $user = $request->user();
            
            $unreadCount = Message::whereHas('conversation', function($query) use ($user) {
                                $query->where('sender_id', $user->id)
                                      ->orWhere('receiver_id', $user->id);
                            })
                            ->where('sender_id', '!=', $user->id)
                            ->where('is_read', 0)
                            ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $unreadCount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function loadMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|exists:conversations,id',
            'last_message_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $conversationId = $request->conversation_id;
            $lastMessageId = $request->last_message_id;

            // Verify user has access to this conversation
            $conversation = Conversation::where('id', $conversationId)
                                      ->where(function($query) use ($user) {
                                          $query->where('sender_id', $user->id)
                                                ->orWhere('receiver_id', $user->id);
                                      })
                                      ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found'
                ], 404);
            }

            $query = Message::where('conversation_id', $conversationId)
                           ->with('sender:id,firstname,lastname,image');

            if ($lastMessageId) {
                $query->where('id', '>', $lastMessageId);
            }

            $messages = $query->orderBy('created_at', 'asc')->get();

            // Mark new messages as read
            if ($messages->count() > 0) {
                Message::where('conversation_id', $conversationId)
                      ->where('sender_id', '!=', $user->id)
                      ->where('is_read', 0)
                      ->update(['is_read' => 1]);
            }

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load messages',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
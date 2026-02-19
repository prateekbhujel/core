<?php

namespace App\Http\Controllers;

use App\Http\Services\TelegramNotificationService;
use App\Models\User;
use App\Notifications\SystemBroadcastNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !Schema::hasTable('notifications')) {
            return response()->json([
                'unread_count' => 0,
                'items' => [],
            ]);
        }

        $items = $user
            ->notifications()
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data ?? [];

                return [
                    'id'       => $notification->id,
                    'title'    => $data['title'] ?? 'Notification',
                    'message'  => $data['message'] ?? '',
                    'level'    => $data['level'] ?? 'info',
                    'url'      => $data['url'] ?? null,
                    'read'     => $notification->read_at !== null,
                    'time'     => optional($notification->created_at)->diffForHumans(),
                    'datetime' => optional($notification->created_at)?->toDateTimeString(),
                ];
            })
            ->values();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $items,
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        if (!Schema::hasTable('notifications')) {
            return response()->json(['ok' => false, 'message' => 'Notifications table is missing.'], 404);
        }

        $notification = $request->user()->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json(['ok' => false, 'message' => 'Notification not found.'], 404);
        }

        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function broadcast(Request $request, TelegramNotificationService $telegram): RedirectResponse
    {
        $actor = $request->user();
        if (!$actor || !$actor->isAdmin()) {
            abort(403, 'Only admin can broadcast notifications.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1200'],
            'level' => ['required', Rule::in(['info', 'success', 'warning', 'error'])],
            'url' => ['nullable', 'url', 'max:255'],
            'audience' => ['required', Rule::in(['all', 'admins', 'role', 'users'])],
            'role' => ['nullable', Rule::in(['admin', 'manager', 'user'])],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => [Rule::in(['in_app', 'telegram'])],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $query = User::query();
        $audience = $validated['audience'];

        if ($audience === 'admins') {
            $query->where('role', 'admin');
        } elseif ($audience === 'role') {
            $query->where('role', $validated['role'] ?? 'user');
        } elseif ($audience === 'users') {
            $ids = $validated['user_ids'] ?? [];
            if (empty($ids)) {
                return back()->with('error', 'Select at least one user for custom audience.');
            }
            $query->whereIn('id', $ids);
        }

        $recipients = $query->get();
        $channels = $validated['channels'];
        if (in_array('in_app', $channels, true) && !Schema::hasTable('notifications')) {
            return back()->with('error', 'Run migrations first to enable in-app notifications.');
        }

        $inAppCount = 0;
        $telegramCount = 0;

        foreach ($recipients as $recipient) {
            if (in_array('in_app', $channels, true) && $recipient->receive_in_app_notifications) {
                $recipient->notify(new SystemBroadcastNotification(
                    title: $validated['title'],
                    message: $validated['message'],
                    level: $validated['level'],
                    url: $validated['url'] ?? null,
                ));
                $inAppCount++;
            }

            if (in_array('telegram', $channels, true) && $recipient->receive_telegram_notifications && $recipient->telegram_chat_id) {
                $sent = $telegram->sendMessage(
                    $recipient->telegram_chat_id,
                    "<b>{$validated['title']}</b>\n{$validated['message']}"
                );
                if ($sent) {
                    $telegramCount++;
                }
            }
        }

        return back()->with(
            'success',
            "Broadcast sent. In-app: {$inAppCount}, Telegram: {$telegramCount}."
        );
    }
}

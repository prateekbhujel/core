<?php

namespace App\Http\Controllers;

use App\Models\UserActivity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UiOptionsController extends Controller
{
    public function leads(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('q', ''));

        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->when($search !== '', function ($query) use ($search) {
                $query
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        $results = $users->map(function (User $user) {
            return [
                'id'       => (string) $user->id,
                'text'     => $user->name,
                'subtitle' => $user->email,
                'image'    => $this->avatarFor($user->name),
            ];
        })->values();

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => false],
        ]);
    }

    private function avatarFor(string $name): string
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=f5a623&color=111111&size=64';
    }

    public function usersTable(Request $request): JsonResponse
    {
        $query = User::query()
            ->with(['roles:id,name'])
            ->select([
                'id',
                'name',
                'email',
                'role',
                'receive_in_app_notifications',
                'receive_telegram_notifications',
                'browser_notifications_enabled',
                'created_at',
            ]);

        return DataTables::eloquent($query)
            ->editColumn('role', function (User $user) {
                $roleName = optional($user->roles->first())->name ?: ($user->role ?: 'user');
                return strtoupper((string) $roleName);
            })
            ->addColumn('channels', function (User $user) {
                $channels = [];

                if ($user->receive_in_app_notifications) {
                    $channels[] = 'In-app';
                }

                if ($user->receive_telegram_notifications) {
                    $channels[] = 'Telegram';
                }

                if ($user->browser_notifications_enabled) {
                    $channels[] = 'Browser';
                }

                return !empty($channels) ? implode(', ', $channels) : 'None';
            })
            ->editColumn('created_at', fn (User $user) => optional($user->created_at)->format('Y-m-d'))
            ->toJson();
    }

    public function activityTable(Request $request): JsonResponse
    {
        $query = UserActivity::query()
            ->with(['user:id,name,email'])
            ->select([
                'id',
                'user_id',
                'method',
                'path',
                'route_name',
                'ip_address',
                'meta',
                'created_at',
            ]);

        return DataTables::eloquent($query)
            ->addColumn('user', function (UserActivity $activity) {
                $name = (string) ($activity->user->name ?? 'Unknown');
                $email = (string) ($activity->user->email ?? '');

                return $email !== '' ? $name . ' (' . $email . ')' : $name;
            })
            ->editColumn('method', fn (UserActivity $activity) => strtoupper((string) $activity->method))
            ->addColumn('status', function (UserActivity $activity) {
                return (string) data_get($activity->meta, 'status', '-');
            })
            ->editColumn('created_at', fn (UserActivity $activity) => optional($activity->created_at)->format('Y-m-d H:i:s'))
            ->toJson();
    }
}

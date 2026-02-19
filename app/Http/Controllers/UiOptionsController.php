<?php

namespace App\Http\Controllers;

use App\Models\UserActivity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
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
            ->addColumn('actions', function (User $user) use ($request) {
                if (!$request->user() || !$request->user()->can('manage users')) {
                    return '<span class="h-muted">View only</span>';
                }

                $url = route('settings.users.index', ['user' => $user->id]) . '#user-editor';
                return '<a class="btn btn-outline-secondary btn-sm" data-spa href="' . e($url) . '">Edit</a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function rolesTable(Request $request): JsonResponse
    {
        $roles = Role::query()
            ->with('permissions:id,name')
            ->orderBy('id', 'desc')
            ->get();

        $roleUserCounts = $this->roleUserCounts();
        $protected = ['super-admin', 'admin'];

        $rows = $roles->map(function (Role $role) use ($roleUserCounts, $protected, $request) {
            $isProtected = in_array((string) $role->name, $protected, true);
            $editUrl = route('settings.rbac', ['role' => $role->id]) . '#role-editor';

            return [
                'id' => $role->id,
                'name' => strtoupper((string) $role->name),
                'permissions_count' => $role->permissions->count(),
                'users_count' => (int) ($roleUserCounts[$role->id] ?? 0),
                'is_protected' => $isProtected ? 'Yes' : 'No',
                'actions' => $request->user() && $request->user()->can('manage settings')
                    ? '<a class="btn btn-outline-secondary btn-sm" data-spa href="' . e($editUrl) . '">Edit</a>'
                    : '<span class="h-muted">View only</span>',
            ];
        })->values();

        return DataTables::collection($rows)
            ->rawColumns(['actions'])
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

    /**
     * @return array<int, int>
     */
    private function roleUserCounts(): array
    {
        $tables = config('permission.table_names', []);
        $pivotTable = (string) ($tables['model_has_roles'] ?? '');
        if ($pivotTable === '' || !Schema::hasTable($pivotTable)) {
            return [];
        }

        return DB::table($pivotTable)
            ->select('role_id', DB::raw('COUNT(*) AS total'))
            ->where('model_type', User::class)
            ->groupBy('role_id')
            ->pluck('total', 'role_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }
}

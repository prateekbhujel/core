<?php

namespace App\Http\Controllers;

use App\Models\UserActivity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=2f7df6&color=ffffff&size=64';
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

                $editButton = '<button type="button" class="btn btn-outline-secondary btn-sm" data-user-edit-id="' . (int) $user->id . '">Edit</button>';
                if ((int) $request->user()->id === (int) $user->id) {
                    return $editButton;
                }

                $deleteAction = route('settings.users.delete', $user);
                $csrf = csrf_token();
                $deleteForm = '<form method="POST" action="' . e($deleteAction) . '" class="d-inline-block ms-1" data-spa data-confirm="true" data-confirm-title="Delete user?" data-confirm-text="This user account will be removed permanently.">'
                    . '<input type="hidden" name="_token" value="' . e($csrf) . '">'
                    . '<input type="hidden" name="_method" value="DELETE">'
                    . '<button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>'
                    . '</form>';

                return $editButton . $deleteForm;
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

            return [
                'id' => $role->id,
                'name' => strtoupper((string) $role->name),
                'permissions_count' => $role->permissions->count(),
                'users_count' => (int) ($roleUserCounts[$role->id] ?? 0),
                'is_protected' => $isProtected ? 'Yes' : 'No',
                'actions' => $request->user() && $request->user()->can('manage settings')
                    ? '<button type="button" class="btn btn-outline-secondary btn-sm" data-role-edit-id="' . (int) $role->id . '">Edit</button>'
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

    public function fileManagerList(Request $request): JsonResponse
    {
        if (!$request->user() || !$request->user()->can('view settings')) {
            abort(403, 'You do not have permission to view media files.');
        }

        $query = trim((string) $request->query('q', ''));
        $limit = max(20, min((int) $request->query('limit', 80), 300));
        $directory = public_path('uploads');

        if (!File::isDirectory($directory)) {
            return response()->json(['items' => []]);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico'];
        $files = collect(File::allFiles($directory))
            ->filter(function ($file) use ($allowedExtensions) {
                return in_array(strtolower((string) $file->getExtension()), $allowedExtensions, true);
            })
            ->when($query !== '', function ($items) use ($query) {
                return $items->filter(function ($file) use ($query) {
                    return str_contains(strtolower((string) $file->getFilename()), strtolower($query));
                });
            })
            ->sortByDesc(fn ($file) => (int) $file->getMTime())
            ->take($limit)
            ->values();

        $items = $files->map(function ($file) {
            $absolutePath = (string) $file->getPathname();
            $relative = str_replace('\\', '/', ltrim(str_replace(public_path(), '', $absolutePath), '/'));

            return [
                'name' => (string) $file->getFilename(),
                'path' => $relative,
                'url' => url($relative),
                'size_kb' => number_format(((int) $file->getSize()) / 1024, 1),
                'modified_at' => date('Y-m-d H:i', (int) $file->getMTime()),
            ];
        })->all();

        return response()->json(['items' => $items]);
    }

    public function fileManagerUpload(Request $request): JsonResponse
    {
        if (!$request->user() || !$request->user()->can('manage settings')) {
            abort(403, 'You do not have permission to upload media files.');
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg,ico', 'max:5120'],
            'folder' => ['nullable', 'string', 'max:80'],
        ]);

        $folder = trim((string) ($validated['folder'] ?? 'editor'));
        $folder = preg_replace('/[^a-z0-9_-]/i', '-', $folder) ?: 'editor';
        $subPath = 'uploads/' . $folder . '/' . now()->format('Y/m');
        $targetDirectory = public_path($subPath);
        File::ensureDirectoryExists($targetDirectory, 0775, true);

        $file = $validated['file'];
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'png'));
        $safeExtension = preg_replace('/[^a-z0-9]/i', '', $extension) ?: 'png';
        $filename = now()->format('YmdHis') . '-' . strtolower(str()->random(8)) . '.' . $safeExtension;
        $file->move($targetDirectory, $filename);

        $relative = $subPath . '/' . $filename;

        return response()->json([
            'ok' => true,
            'item' => [
                'name' => $filename,
                'path' => $relative,
                'url' => url($relative),
                'size_kb' => number_format(((int) File::size(public_path($relative))) / 1024, 1),
                'modified_at' => now()->format('Y-m-d H:i'),
            ],
        ]);
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

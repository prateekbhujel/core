<?php

namespace App\Http\Controllers;

use App\Models\UserActivity;
use App\Models\User;
use App\Support\AppSettings;
use App\Support\HealthCheckService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Throwable;
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

                $editButton = '<button type="button" class="btn btn-outline-secondary btn-sm h-action-icon" data-user-edit-id="' . (int) $user->id . '" title="Edit user" aria-label="Edit user">'
                    . '<i class="fa-solid fa-pen-to-square"></i>'
                    . '</button>';
                $accessButton = '<button type="button" class="btn btn-outline-secondary btn-sm h-action-icon" data-user-access-id="' . (int) $user->id . '" title="Role and permission access" aria-label="Role and permission access">'
                    . '<i class="fa-solid fa-user-shield"></i>'
                    . '</button>';
                $notifyButton = '<button type="button" class="btn btn-outline-secondary btn-sm h-action-icon" data-user-notify-id="' . (int) $user->id . '" title="Notification channels" aria-label="Notification channels">'
                    . '<i class="fa-solid fa-bell"></i>'
                    . '</button>';
                if ((int) $request->user()->id === (int) $user->id) {
                    return '<span class="h-action-group">' . $editButton . $accessButton . $notifyButton . '</span>';
                }

                $deleteAction = route('settings.users.delete', $user);
                $csrf = csrf_token();
                $deleteForm = '<form method="POST" action="' . e($deleteAction) . '" class="d-inline-block" data-spa data-confirm="true" data-confirm-title="Delete user?" data-confirm-text="This user account will be removed permanently.">'
                    . '<input type="hidden" name="_token" value="' . e($csrf) . '">'
                    . '<input type="hidden" name="_method" value="DELETE">'
                    . '<button type="submit" class="btn btn-outline-danger btn-sm h-action-icon" title="Delete user" aria-label="Delete user"><i class="fa-solid fa-trash"></i></button>'
                    . '</form>';

                return '<span class="h-action-group">' . $editButton . $accessButton . $notifyButton . $deleteForm . '</span>';
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
                    ? '<a href="' . e(route('settings.rbac', ['role' => (int) $role->id])) . '#role-editor" data-spa class="btn btn-outline-secondary btn-sm h-action-icon" title="Edit role" aria-label="Edit role"><i class="fa-solid fa-pen-to-square"></i></a>'
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

    public function healthReport(Request $request, HealthCheckService $health): JsonResponse
    {
        if (!$request->user() || !$request->user()->can('view settings')) {
            abort(403, 'You do not have permission to view health diagnostics.');
        }

        return response()->json($health->report());
    }

    public function hotReloadSignature(Request $request): JsonResponse
    {
        if (!app()->environment('local') || !filter_var((string) env('HAARRAY_HOT_RELOAD', 'true'), FILTER_VALIDATE_BOOL)) {
            abort(404);
        }

        $signature = $this->computeHotReloadSignature();
        $current = trim((string) $request->query('sig', ''));

        if ($current !== '' && hash_equals($current, $signature)) {
            return response()->json([], 204);
        }

        return response()->json([
            'signature' => $signature,
            'generated_at' => now()->toDateTimeString(),
        ]);
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

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico', 'mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];
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
            $extension = strtolower((string) $file->getExtension());

            $type = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico'], true)
                ? 'image'
                : (in_array($extension, ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'], true) ? 'audio' : 'file');

            return [
                'name' => (string) $file->getFilename(),
                'path' => $relative,
                'url' => url($relative),
                'type' => $type,
                'extension' => $extension,
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
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg,ico,mp3,wav,ogg,m4a,aac,flac', 'max:15360'],
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
        $type = in_array($safeExtension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico'], true) ? 'image' : 'audio';

        return response()->json([
            'ok' => true,
            'item' => [
                'name' => $filename,
                'path' => $relative,
                'url' => url($relative),
                'type' => $type,
                'extension' => $safeExtension,
                'size_kb' => number_format(((int) File::size(public_path($relative))) / 1024, 1),
                'modified_at' => now()->format('Y-m-d H:i'),
            ],
        ]);
    }

    public function globalSearch(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json(['items' => []]);
        }

        $items = [];
        $maxItems = max(8, min((int) $request->query('limit', 20), 80));
        $perSource = max(3, min((int) $request->query('per_source', 8), 20));

        foreach ($this->globalSearchLinksConfig() as $link) {
            $permission = trim((string) ($link['permission'] ?? ''));
            if ($permission !== '' && !$user->can($permission)) {
                continue;
            }

            $title = trim((string) ($link['title'] ?? ''));
            $subtitle = trim((string) ($link['subtitle'] ?? ''));
            $haystack = strtolower($title . ' ' . $subtitle);
            if (!str_contains($haystack, strtolower($query))) {
                continue;
            }

            $url = $this->resolveSearchLinkUrl($link);
            if ($url === '') {
                continue;
            }

            $items[] = [
                'id' => 'link-' . sha1($url . $title),
                'title' => $title !== '' ? $title : 'Link',
                'subtitle' => $subtitle,
                'url' => $url,
                'icon' => (string) ($link['icon'] ?? 'fa-solid fa-link'),
                'type' => 'link',
            ];
            if (count($items) >= $maxItems) {
                return response()->json(['items' => array_slice($items, 0, $maxItems)]);
            }
        }

        foreach ($this->globalSearchModelsConfig() as $source) {
            $permission = trim((string) ($source['permission'] ?? ''));
            if ($permission !== '' && !$user->can($permission)) {
                continue;
            }

            $rows = $this->searchModelSource($source, $query, $perSource);
            foreach ($rows as $row) {
                $items[] = $row;
                if (count($items) >= $maxItems) {
                    break 2;
                }
            }
        }

        return response()->json([
            'items' => array_slice($items, 0, $maxItems),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function globalSearchLinksConfig(): array
    {
        $config = (array) config('haarray.global_search.links', []);
        return array_values(array_filter($config, fn ($item) => is_array($item)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function globalSearchModelsConfig(): array
    {
        $config = (array) config('haarray.global_search.models', []);
        $dynamic = trim(AppSettings::get('search.registry_json', ''));

        if ($dynamic !== '') {
            try {
                $decoded = json_decode($dynamic, true, flags: JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $config = $decoded;
                }
            } catch (Throwable) {
                // Keep static fallback when dynamic JSON is invalid.
            }
        }

        return array_values(array_filter($config, fn ($item) => is_array($item)));
    }

    /**
     * @param array<string, mixed> $source
     * @return array<int, array<string, string>>
     */
    private function searchModelSource(array $source, string $query, int $limit): array
    {
        $modelClass = trim((string) ($source['model'] ?? ''));
        if ($modelClass === '' || !class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            return [];
        }

        $searchFields = array_values(array_filter(array_map(
            fn ($field) => trim((string) $field),
            (array) ($source['search'] ?? [])
        )));
        if (empty($searchFields)) {
            return [];
        }

        $titleField = trim((string) ($source['title'] ?? $searchFields[0]));
        $subtitleField = trim((string) ($source['subtitle'] ?? ''));
        $idField = trim((string) ($source['id'] ?? 'id'));
        $icon = trim((string) ($source['icon'] ?? 'fa-solid fa-file-lines'));
        $type = trim((string) ($source['key'] ?? 'record'));

        /** @var class-string<Model> $modelClass */
        $model = new $modelClass();
        $table = (string) $model->getTable();
        if ($table === '') {
            return [];
        }

        try {
            $columns = Schema::getColumnListing($table);
        } catch (Throwable) {
            return [];
        }
        if (empty($columns)) {
            return [];
        }

        $columnSet = array_fill_keys($columns, true);
        $safeFields = array_values(array_filter($searchFields, fn ($field) => isset($columnSet[$field])));
        if (empty($safeFields) || !isset($columnSet[$idField]) || !isset($columnSet[$titleField])) {
            return [];
        }

        $queryBuilder = $modelClass::query();
        $queryBuilder->where(function ($builder) use ($safeFields, $query) {
            foreach ($safeFields as $index => $field) {
                if ($index === 0) {
                    $builder->where($field, 'like', '%' . $query . '%');
                    continue;
                }
                $builder->orWhere($field, 'like', '%' . $query . '%');
            }
        });

        $selectColumns = array_values(array_unique(array_filter([
            $idField,
            $titleField,
            $subtitleField !== '' && isset($columnSet[$subtitleField]) ? $subtitleField : null,
        ])));

        if (!empty($selectColumns)) {
            $queryBuilder->select($selectColumns);
        }

        try {
            $rows = $queryBuilder->limit(max(1, min($limit, 30)))->get();
        } catch (Throwable) {
            return [];
        }

        return $rows->map(function (Model $row) use ($titleField, $subtitleField, $source, $icon, $type) {
            $title = trim((string) data_get($row, $titleField));
            $subtitle = $subtitleField !== '' ? trim((string) data_get($row, $subtitleField)) : '';
            $url = $this->resolveSearchRowUrl($source, $row);
            if ($url === '') {
                return null;
            }

            $id = (string) data_get($row, $source['id'] ?? 'id');
            return [
                'id' => $type . '-' . $id,
                'title' => $title !== '' ? $title : ('Record #' . $id),
                'subtitle' => $subtitle,
                'url' => $url,
                'icon' => $icon,
                'type' => $type,
            ];
        })->filter()->values()->all();
    }

    /**
     * @param array<string, mixed> $config
     */
    private function resolveSearchLinkUrl(array $config): string
    {
        $routeName = trim((string) ($config['route'] ?? ''));
        $params = is_array($config['params'] ?? null) ? $config['params'] : [];
        if ($routeName !== '' && Route::has($routeName)) {
            return route($routeName, $params);
        }

        $url = trim((string) ($config['url'] ?? ''));
        if ($url === '') {
            return '';
        }

        return Str::startsWith($url, ['http://', 'https://', '/']) ? $url : ('/' . ltrim($url, '/'));
    }

    /**
     * @param array<string, mixed> $source
     */
    private function resolveSearchRowUrl(array $source, Model $row): string
    {
        $routeName = trim((string) ($source['route'] ?? ''));
        $params = is_array($source['route_params'] ?? null) ? $source['route_params'] : [];
        if ($routeName !== '' && Route::has($routeName)) {
            $built = [];
            foreach ($params as $key => $value) {
                $built[(string) $key] = $this->replaceTemplateTokens((string) $value, $row);
            }
            $url = route($routeName, $built);
        } else {
            $url = trim((string) ($source['url'] ?? ''));
            if ($url === '') {
                return '';
            }
            $url = $this->replaceTemplateTokens($url, $row);
            if (!Str::startsWith($url, ['http://', 'https://', '/'])) {
                $url = '/' . ltrim($url, '/');
            }
        }

        $queryTemplate = trim((string) ($source['query'] ?? ''));
        if ($queryTemplate !== '') {
            $query = $this->replaceTemplateTokens($queryTemplate, $row);
            $url .= (str_contains($url, '?') ? '&' : '?') . ltrim($query, '?&');
        }

        return $url;
    }

    private function replaceTemplateTokens(string $value, Model $row): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function ($matches) use ($row) {
            $key = (string) ($matches[1] ?? '');
            if ($key === '') {
                return '';
            }
            return (string) data_get($row, $key, '');
        }, $value) ?? $value;
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

    private function computeHotReloadSignature(): string
    {
        $files = [
            public_path('css/haarray.app.css'),
            public_path('js/haarray.app.js'),
            base_path('routes/web.php'),
            resource_path('views/layouts/app.blade.php'),
            resource_path('views/dashboard.blade.php'),
            resource_path('views/settings/index.blade.php'),
            resource_path('views/settings/users.blade.php'),
            resource_path('views/settings/rbac.blade.php'),
            resource_path('views/docs/starter-kit.blade.php'),
        ];

        $buffer = [];
        foreach ($files as $file) {
            if (!File::exists($file)) {
                continue;
            }

            try {
                $buffer[] = $file . ':' . (string) File::lastModified($file);
            } catch (Throwable) {
                // Ignore unreadable files in hot-reload signature generation.
            }
        }

        if (empty($buffer)) {
            return sha1((string) microtime(true));
        }

        return sha1(implode('|', $buffer));
    }
}

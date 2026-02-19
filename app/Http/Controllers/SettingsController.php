<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class SettingsController extends Controller
{
    public function index(): View
    {
        $viewer = request()->user();

        $fields = $this->fields();
        $values = $this->readEnvValues(array_keys($fields));

        foreach ($fields as $key => $meta) {
            if (($values[$key] ?? '') === '' && array_key_exists('default', $meta)) {
                $values[$key] = (string) $meta['default'];
            }
        }

        return view('settings.index', [
            'fields'      => $fields,
            'values'      => $values,
            'sections'    => $this->sections(),
            'envWritable' => $this->envWritable(),
            'users'       => $viewer && $viewer->isAdmin() ? User::orderBy('name')->get() : collect(),
            'isAdmin'     => (bool) ($viewer && $viewer->isAdmin()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->assertAdmin($request);

        if (!$this->envWritable()) {
            return back()->with('error', 'The .env file is not writable. Update file permissions and try again.');
        }

        $fields = $this->fields();
        $validated = $request->validate($this->rules($fields));

        $updates = [];
        foreach ($fields as $key => $meta) {
            $rawValue = (string) ($validated[$key] ?? '');
            $updates[$key] = $this->formatEnvValue($rawValue, $meta['type'] ?? 'text');
        }

        try {
            $this->writeEnvValues($updates);
            Artisan::call('config:clear');
        } catch (Throwable $e) {
            return back()->with('error', 'Settings could not be saved: ' . $e->getMessage());
        }

        return back()->with('success', 'Environment settings were updated successfully.');
    }

    public function updateUserAccess(Request $request, User $user): RedirectResponse
    {
        $this->assertAdmin($request);

        $validated = $request->validate([
            'role' => ['required', 'in:admin,manager,user'],
            'receive_in_app_notifications' => ['nullable', 'boolean'],
            'receive_telegram_notifications' => ['nullable', 'boolean'],
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'string', 'max:1000'],
        ]);

        $permissions = array_filter(array_map(
            fn ($permission) => trim($permission),
            explode(',', (string) ($validated['permissions'] ?? ''))
        ));

        $user->update([
            'role' => $validated['role'],
            'receive_in_app_notifications' => (bool) $request->boolean('receive_in_app_notifications', false),
            'receive_telegram_notifications' => (bool) $request->boolean('receive_telegram_notifications', false),
            'telegram_chat_id' => $validated['telegram_chat_id'] ?? null,
            'permissions' => array_values($permissions),
        ]);

        return back()->with('success', "Access settings updated for {$user->name}.");
    }

    public function updateMySecurity(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $validated = $request->validate([
            'two_factor_enabled' => ['nullable', 'boolean'],
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
            'receive_in_app_notifications' => ['nullable', 'boolean'],
            'receive_telegram_notifications' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'two_factor_enabled' => (bool) $request->boolean('two_factor_enabled', false),
            'telegram_chat_id' => $validated['telegram_chat_id'] ?? null,
            'receive_in_app_notifications' => (bool) $request->boolean('receive_in_app_notifications', false),
            'receive_telegram_notifications' => (bool) $request->boolean('receive_telegram_notifications', false),
        ]);

        return back()->with('success', 'Your security and notification preferences were updated.');
    }

    private function sections(): array
    {
        return [
            'app' => [
                'title'       => 'Application',
                'description' => 'Core app identity and runtime behavior.',
            ],
            'haarray' => [
                'title'       => 'Haarray Features',
                'description' => 'Branding and feature flags from config/haarray.php.',
            ],
            'telegram' => [
                'title'       => 'Telegram Bot API',
                'description' => 'Bot token, username, and webhook endpoint configuration.',
            ],
            'ml' => [
                'title'       => 'ML Suggestion Engine',
                'description' => 'Thresholds used by the suggestion service.',
            ],
            'database' => [
                'title'       => 'Database',
                'description' => 'Connection details used by Laravel database config.',
            ],
            'mail' => [
                'title'       => 'Mail',
                'description' => 'Mailer and sender details.',
            ],
        ];
    }

    private function fields(): array
    {
        return [
            'APP_NAME' => [
                'section'  => 'app',
                'label'    => 'App Name',
                'type'     => 'text',
                'required' => true,
                'default'  => 'Haarray Core',
            ],
            'APP_URL' => [
                'section'  => 'app',
                'label'    => 'App URL',
                'type'     => 'url',
                'required' => true,
                'default'  => 'http://localhost',
            ],
            'APP_ENV' => [
                'section'  => 'app',
                'label'    => 'App Environment',
                'type'     => 'select',
                'required' => true,
                'options'  => ['local', 'staging', 'production'],
                'default'  => 'local',
            ],
            'APP_DEBUG' => [
                'section'  => 'app',
                'label'    => 'Debug Mode',
                'type'     => 'bool',
                'required' => true,
                'default'  => 'true',
            ],
            'APP_TIMEZONE' => [
                'section'  => 'app',
                'label'    => 'Timezone',
                'type'     => 'text',
                'required' => true,
                'default'  => 'UTC',
            ],
            'HAARRAY_BRAND' => [
                'section'  => 'haarray',
                'label'    => 'Brand Name',
                'type'     => 'text',
                'required' => true,
                'default'  => 'Haarray',
            ],
            'HAARRAY_INITIAL' => [
                'section'  => 'haarray',
                'label'    => 'Brand Initial',
                'type'     => 'text',
                'required' => true,
                'default'  => 'H',
            ],
            'HAARRAY_SHOW_TG' => [
                'section'  => 'haarray',
                'label'    => 'Show Telegram Status',
                'type'     => 'bool',
                'required' => true,
                'default'  => 'false',
            ],
            'HAARRAY_PWA' => [
                'section'  => 'haarray',
                'label'    => 'Enable PWA',
                'type'     => 'bool',
                'required' => true,
                'default'  => 'true',
            ],
            'HAARRAY_ML' => [
                'section'  => 'haarray',
                'label'    => 'Enable ML Suggestions',
                'type'     => 'bool',
                'required' => true,
                'default'  => 'true',
            ],
            'TELEGRAM_BOT_TOKEN' => [
                'section'  => 'telegram',
                'label'    => 'Bot Token',
                'type'     => 'password',
                'required' => false,
                'default'  => '',
            ],
            'TELEGRAM_BOT_USERNAME' => [
                'section'  => 'telegram',
                'label'    => 'Bot Username',
                'type'     => 'text',
                'required' => false,
                'default'  => 'HariLogBot',
            ],
            'TELEGRAM_BOT_WEBHOOK_URL' => [
                'section'  => 'telegram',
                'label'    => 'Webhook URL',
                'type'     => 'url',
                'required' => false,
                'default'  => '',
            ],
            'HAARRAY_ML_IDLE_CASH_THRESHOLD' => [
                'section'  => 'ml',
                'label'    => 'Idle Cash Threshold (NPR)',
                'type'     => 'number',
                'required' => true,
                'default'  => '5000',
            ],
            'HAARRAY_ML_FOOD_BUDGET_WARNING' => [
                'section'  => 'ml',
                'label'    => 'Food Budget Warning Ratio',
                'type'     => 'decimal',
                'required' => true,
                'default'  => '0.35',
            ],
            'HAARRAY_ML_SAVINGS_RATE_TARGET' => [
                'section'  => 'ml',
                'label'    => 'Savings Rate Target',
                'type'     => 'decimal',
                'required' => true,
                'default'  => '0.30',
            ],
            'HAARRAY_ML_RETRAIN_DAYS' => [
                'section'  => 'ml',
                'label'    => 'Model Retrain Interval (days)',
                'type'     => 'number',
                'required' => true,
                'default'  => '7',
            ],
            'DB_HOST' => [
                'section'  => 'database',
                'label'    => 'DB Host',
                'type'     => 'text',
                'required' => true,
                'default'  => '127.0.0.1',
            ],
            'DB_PORT' => [
                'section'  => 'database',
                'label'    => 'DB Port',
                'type'     => 'number',
                'required' => true,
                'default'  => '3306',
            ],
            'DB_DATABASE' => [
                'section'  => 'database',
                'label'    => 'DB Database',
                'type'     => 'text',
                'required' => true,
                'default'  => 'harray_core',
            ],
            'DB_USERNAME' => [
                'section'  => 'database',
                'label'    => 'DB Username',
                'type'     => 'text',
                'required' => true,
                'default'  => 'root',
            ],
            'DB_PASSWORD' => [
                'section'  => 'database',
                'label'    => 'DB Password',
                'type'     => 'password',
                'required' => false,
                'default'  => '',
            ],
            'MAIL_MAILER' => [
                'section'  => 'mail',
                'label'    => 'Mail Driver',
                'type'     => 'select',
                'required' => true,
                'options'  => ['log', 'smtp', 'sendmail', 'mailgun'],
                'default'  => 'log',
            ],
            'MAIL_HOST' => [
                'section'  => 'mail',
                'label'    => 'Mail Host',
                'type'     => 'text',
                'required' => true,
                'default'  => '127.0.0.1',
            ],
            'MAIL_PORT' => [
                'section'  => 'mail',
                'label'    => 'Mail Port',
                'type'     => 'number',
                'required' => true,
                'default'  => '2525',
            ],
            'MAIL_USERNAME' => [
                'section'  => 'mail',
                'label'    => 'Mail Username',
                'type'     => 'text',
                'required' => false,
                'default'  => '',
            ],
            'MAIL_PASSWORD' => [
                'section'  => 'mail',
                'label'    => 'Mail Password',
                'type'     => 'password',
                'required' => false,
                'default'  => '',
            ],
            'MAIL_FROM_ADDRESS' => [
                'section'  => 'mail',
                'label'    => 'From Address',
                'type'     => 'email',
                'required' => true,
                'default'  => 'hello@example.com',
            ],
            'MAIL_FROM_NAME' => [
                'section'  => 'mail',
                'label'    => 'From Name',
                'type'     => 'text',
                'required' => true,
                'default'  => 'Haarray Core',
            ],
        ];
    }

    private function rules(array $fields): array
    {
        $rules = [];

        foreach ($fields as $key => $meta) {
            $type = $meta['type'] ?? 'text';
            $required = ($meta['required'] ?? false) ? 'required' : 'nullable';
            $options = $meta['options'] ?? [];

            $rules[$key] = match ($type) {
                'url'    => [$required, 'url', 'max:255'],
                'email'  => [$required, 'email', 'max:255'],
                'number' => [$required, 'integer', 'between:1,65535'],
                'decimal' => [$required, 'numeric', 'between:0,100'],
                'bool'   => [$required, 'in:true,false,1,0,on,off'],
                'select' => array_merge([$required, 'string', 'max:255'], $options ? ['in:' . implode(',', $options)] : []),
                default  => [$required, 'string', 'max:255'],
            };
        }

        return $rules;
    }

    private function envPath(): string
    {
        return base_path('.env');
    }

    private function envWritable(): bool
    {
        $path = $this->envPath();
        return File::exists($path) ? is_writable($path) : is_writable(base_path());
    }

    private function readEnvValues(array $keys): array
    {
        $path = $this->envPath();
        if (!File::exists($path) && File::exists(base_path('.env.example'))) {
            File::copy(base_path('.env.example'), $path);
        }

        $content = File::exists($path) ? File::get($path) : '';
        $values = [];

        foreach ($keys as $key) {
            $pattern = '/^' . preg_quote($key, '/') . '=(.*)$/m';

            if (preg_match($pattern, $content, $matches) === 1) {
                $values[$key] = $this->decodeEnvValue($matches[1]);
            } else {
                $values[$key] = '';
            }
        }

        return $values;
    }

    private function decodeEnvValue(string $value): string
    {
        $value = trim($value);

        if ($value === '' || strtolower($value) === 'null') {
            return '';
        }

        $startsWithQuote = Str::startsWith($value, '"') || Str::startsWith($value, "'");
        $endsWithQuote = Str::endsWith($value, '"') || Str::endsWith($value, "'");

        if ($startsWithQuote && $endsWithQuote) {
            $value = substr($value, 1, -1);
            $value = str_replace(['\\"', '\\\\'], ['"', '\\'], $value);
        }

        return $value;
    }

    private function formatEnvValue(string $value, string $type): string
    {
        if ($type === 'bool') {
            return filter_var($value, FILTER_VALIDATE_BOOL) ? 'true' : 'false';
        }

        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (preg_match('/\s|#|"|\'/', $value) === 1) {
            return '"' . addcslashes($value, "\\\"") . '"';
        }

        return $value;
    }

    private function writeEnvValues(array $updates): void
    {
        $path = $this->envPath();

        if (!File::exists($path) && File::exists(base_path('.env.example'))) {
            File::copy(base_path('.env.example'), $path);
        }

        $content = File::exists($path) ? File::get($path) : '';

        foreach ($updates as $key => $value) {
            $line = $key . '=' . $value;
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

            if (preg_match($pattern, $content) === 1) {
                $content = preg_replace($pattern, $line, $content, 1) ?? $content;
            } else {
                $content = rtrim($content) . PHP_EOL . $line . PHP_EOL;
            }
        }

        File::put($path, $content);
    }

    private function assertAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Only admin can change system settings.');
        }
    }
}

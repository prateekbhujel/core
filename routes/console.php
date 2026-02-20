<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Support\RbacBootstrap;
use App\Support\HealthCheckService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('haarray:permissions:sync {--seed-admins : Create/update default admin users with full access} {--admin-email=* : Extra admin emails to promote as super-admin}', function () {
    /** @var RbacBootstrap $rbac */
    $rbac = app(RbacBootstrap::class);
    $result = $rbac->syncPermissionsAndRoles();

    if (!$result['ok']) {
        $this->error((string) $result['message']);
        return self::FAILURE;
    }

    $this->info("Synced {$result['permissions']} permissions and {$result['roles']} roles.");

    if ($this->option('seed-admins')) {
        $defaultPassword = (string) env('HAARRAY_ADMIN_PASSWORD', 'Admin@12345');
        $seedUsers = [
            [
                'name' => 'Prateek Bhujel',
                'email' => 'prateekbhujelpb@gmail.com',
                'password' => $defaultPassword,
                'role' => 'super-admin',
            ],
            [
                'name' => 'System Admin',
                'email' => 'admin@admin.com',
                'password' => $defaultPassword,
                'role' => 'admin',
            ],
        ];

        $admins = (array) $this->option('admin-email');
        foreach ($admins as $email) {
            $email = strtolower(trim((string) $email));
            if ($email === '') {
                continue;
            }
            $seedUsers[] = [
                'name' => Str::headline(Str::before($email, '@')),
                'email' => $email,
                'password' => $defaultPassword,
                'role' => 'super-admin',
            ];
        }

        $seed = $rbac->ensureUsers($seedUsers);
        $this->info("Admin users ensured. Created {$seed['created']}, updated {$seed['updated']}.");
        $this->line("Default admin password from HAARRAY_ADMIN_PASSWORD (fallback: {$defaultPassword}).");
    }

    return self::SUCCESS;
})->purpose('Sync role/permission matrix and optionally seed default admin users');

Artisan::command('haarray:starter:setup {--seed-admins : Seed/update default admin accounts}', function () {
    $this->comment('Preparing starter kit for production-style usage...');

    $this->call('haarray:permissions:sync', [
        '--seed-admins' => (bool) $this->option('seed-admins'),
    ]);

    $this->call('optimize:clear');
    $this->call('migrate:status');

    $this->line('');
    $this->info('Shared hosting cron recommendations:');
    $this->line('* * * * * php artisan schedule:run >> /dev/null 2>&1');
    $this->line('* * * * * php artisan queue:work --stop-when-empty --tries=1 --timeout=90 >> /dev/null 2>&1');

    $this->line('');
    $this->info('Writable paths checklist:');
    $paths = ['storage', 'bootstrap/cache', 'public/uploads', '.env'];
    foreach ($paths as $path) {
        $absolute = base_path($path);
        $writable = File::exists($absolute) ? is_writable($absolute) : is_writable(dirname($absolute));
        $this->line(($writable ? '[OK] ' : '[WARN] ') . $path);
    }

    return self::SUCCESS;
})->purpose('Run starter bootstrap tasks (permissions sync, diagnostics hints, cron guidance)');

Artisan::command('haarray:health:check', function () {
    /** @var HealthCheckService $health */
    $health = app(HealthCheckService::class);
    $report = $health->report();

    $this->info('Haarray Health Check');
    $this->line('Summary: ' . (int) ($report['summary']['ok'] ?? 0) . ' OK / ' . (int) ($report['summary']['warn'] ?? 0) . ' WARN / ' . (int) ($report['summary']['fail'] ?? 0) . ' FAIL');

    foreach ((array) ($report['checks'] ?? []) as $check) {
        $status = strtoupper((string) ($check['status'] ?? 'warn'));
        $label = (string) ($check['label'] ?? 'check');
        $detail = trim((string) ($check['detail'] ?? ''));
        $line = "[{$status}] {$label}";
        if ($detail !== '') {
            $line .= " - {$detail}";
        }
        if ($status === 'FAIL') {
            $this->error($line);
            continue;
        }
        if ($status === 'WARN') {
            $this->warn($line);
            continue;
        }
        $this->line($line);
    }

    return ((int) ($report['summary']['fail'] ?? 0)) > 0 ? self::FAILURE : self::SUCCESS;
})->purpose('Run internal health checks for DB/cache/storage/queue/notifications');

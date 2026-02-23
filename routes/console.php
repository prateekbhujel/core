<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Support\RbacBootstrap;
use App\Support\HealthCheckService;
use App\Support\ReflectionSyncService;
use Symfony\Component\Process\Process;

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

Artisan::command('haarray:reflect:list', function () {
    /** @var ReflectionSyncService $reflection */
    $reflection = app(ReflectionSyncService::class);
    $targetNames = $reflection->targetNames();

    if (empty($targetNames)) {
        $this->warn('No reflection targets configured. Check config/reflection.php.');
        return self::SUCCESS;
    }

    $this->info('Haarray Reflection Targets');
    foreach ($targetNames as $name) {
        $target = $reflection->target($name);
        if (!$target) {
            $this->warn("- {$name}: invalid target configuration.");
            continue;
        }

        $path = (string) ($target['path'] ?? '');
        $branch = (string) ($target['branch'] ?? 'main');
        $remote = (string) ($target['remote'] ?? 'origin');
        $paths = (array) ($target['shared_paths'] ?? []);

        $this->line("- {$name}");
        $this->line("  path: {$path}");
        $this->line("  git: {$remote}/{$branch}");
        $this->line('  shared paths: ' . count($paths));
    }

    return self::SUCCESS;
})->purpose('List configured downstream reflection targets');

Artisan::command('haarray:reflect:sync {target?* : Target keys from config/reflection.php. Defaults to all.} {--dry-run : Preview sync without writing files} {--commit-targets : Commit synced changes inside target repositories} {--push-targets : Push target branch after sync/commit} {--push-core : Push current core HEAD after target sync} {--message=chore(sync): reflect shared core layer : Commit message used for target commits} {--allow-dirty : Allow commit/push workflow even when target repo starts dirty}', function () {
    /** @var ReflectionSyncService $reflection */
    $reflection = app(ReflectionSyncService::class);
    $availableTargets = $reflection->targetNames();

    if (empty($availableTargets)) {
        $this->error('No reflection targets configured. Add targets in config/reflection.php.');
        return self::FAILURE;
    }

    $requestedTargets = array_values(array_filter(array_map(
        fn ($name) => trim((string) $name),
        (array) $this->argument('target')
    ), fn ($name) => $name !== ''));
    $selectedTargets = empty($requestedTargets) ? $availableTargets : $requestedTargets;

    $unknownTargets = array_values(array_diff($selectedTargets, $availableTargets));
    if (!empty($unknownTargets)) {
        $this->error('Unknown targets: ' . implode(', ', $unknownTargets));
        $this->line('Available targets: ' . implode(', ', $availableTargets));
        return self::FAILURE;
    }

    $dryRun = (bool) $this->option('dry-run');
    $commitTargets = (bool) $this->option('commit-targets');
    $pushTargets = (bool) $this->option('push-targets');
    $pushCore = (bool) $this->option('push-core');
    $allowDirty = (bool) $this->option('allow-dirty');
    $commitMessage = trim((string) $this->option('message')) ?: 'chore(sync): reflect shared core layer';

    if ($dryRun && ($commitTargets || $pushTargets || $pushCore)) {
        $this->warn('Dry-run mode ignores commit/push flags.');
        $commitTargets = false;
        $pushTargets = false;
        $pushCore = false;
    }

    $runGit = static function (string $repositoryPath, array $args): Process {
        $process = new Process(array_merge(['git'], $args), $repositoryPath);
        $process->setTimeout(180);
        $process->run();

        if (!$process->isSuccessful()) {
            $message = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new RuntimeException($message !== '' ? $message : 'Git command failed.');
        }

        return $process;
    };

    $isRepoDirty = static function (string $repositoryPath) use ($runGit): bool {
        $process = $runGit($repositoryPath, ['status', '--porcelain']);
        return trim((string) $process->getOutput()) !== '';
    };

    $hasStagedChanges = static function (string $repositoryPath) use ($runGit): bool {
        $process = $runGit($repositoryPath, ['diff', '--cached', '--name-only']);
        return trim((string) $process->getOutput()) !== '';
    };

    $currentBranch = static function (string $repositoryPath) use ($runGit): string {
        $process = $runGit($repositoryPath, ['rev-parse', '--abbrev-ref', 'HEAD']);
        return trim((string) $process->getOutput()) ?: 'main';
    };

    foreach ($selectedTargets as $targetName) {
        $target = $reflection->target($targetName);
        if (!$target) {
            $this->error("Target {$targetName} is misconfigured.");
            continue;
        }

        $targetPath = (string) $target['path'];
        $targetRemote = (string) $target['remote'];
        $targetBranch = (string) $target['branch'];

        $this->line('');
        $this->info("Reflecting target: {$targetName}");
        $this->line("Path: {$targetPath}");

        try {
            $dirtyBefore = $isRepoDirty($targetPath);
            if (($commitTargets || $pushTargets) && $dirtyBefore && !$allowDirty) {
                throw new RuntimeException("Target {$targetName} is dirty before sync. Use --allow-dirty if intentional.");
            }

            $result = $reflection->sync($target, $dryRun);
            $this->line("Synced {$result['synced']} paths, removed {$result['removed']}, skipped {$result['skipped']}.");

            foreach ((array) ($result['operations'] ?? []) as $operation) {
                $this->line('  ' . $operation);
            }

            if ($dryRun) {
                continue;
            }

            if ($commitTargets) {
                $runGit($targetPath, ['add', '-A']);
                if ($hasStagedChanges($targetPath)) {
                    $runGit($targetPath, ['commit', '-m', $commitMessage]);
                    $this->info("Committed target {$targetName}.");
                } else {
                    $this->line("No target changes to commit for {$targetName}.");
                }
            }

            if ($pushTargets) {
                if (!$allowDirty && $isRepoDirty($targetPath)) {
                    throw new RuntimeException("Target {$targetName} still has uncommitted changes. Aborting push.");
                }

                $runGit($targetPath, ['push', $targetRemote, 'HEAD:' . $targetBranch]);
                $this->info("Pushed {$targetName} to {$targetRemote}/{$targetBranch}.");
            }
        } catch (RuntimeException $exception) {
            $this->error("Target {$targetName} failed: " . $exception->getMessage());
        }
    }

    if ($pushCore && !$dryRun) {
        try {
            $corePath = base_path();
            if (!$allowDirty && $isRepoDirty($corePath)) {
                throw new RuntimeException('Core repository is dirty. Commit or stash changes first, or use --allow-dirty.');
            }

            $branch = $currentBranch($corePath);
            $runGit($corePath, ['push', 'origin', 'HEAD:' . $branch]);
            $this->info("Pushed core repository to origin/{$branch}.");
        } catch (RuntimeException $exception) {
            $this->error('Core push failed: ' . $exception->getMessage());
            return self::FAILURE;
        }
    }

    return self::SUCCESS;
})->purpose('Reflect shared core layer into downstream apps (with optional git commit/push)');

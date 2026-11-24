<?php

use Illuminate\Foundation\Application;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ============ ALIAS MIDDLEWARE ============
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        // ============ JANGAN REDIRECT KE ROUTE LOGIN ============
        // Untuk request API / yang minta JSON → lempar exception (jadi 401 JSON)
        // Untuk request web → tetap redirect ke /login kalau ada
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null; // biar jadi 401 Unauthenticated JSON
            }
            return '/login';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function ($request, $throwable) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })
    ->withSchedule(function (Schedule $schedule) {
        // ============ BACKUP SCHEDULER DINAMIS ============
        try {
            $enabled = \App\Models\Setting::get('backup_enabled', '1') === '1';
        } catch (\Throwable $e) {
            $enabled = true;
        }

        // Auto-cleanup activity logs > 90 hari, tiap Minggu jam 04:00
        // (dijadwalkan di luar pengecekan backup, supaya tetap jalan walau backup dimatikan)
        $schedule->call(function () {
            \App\Models\ActivityLog::where('created_at', '<', now()->subDays(90))->delete();
        })->weekly()->sundays()->at('04:00');

        // Buang token login yang sudah kedaluwarsa lebih dari 24 jam.
        $schedule->command('sanctum:prune-expired --hours=24')->daily();

        $cleanupEvent = $schedule->command('backup:clean');

        if (! $enabled) {
            $cleanupEvent->daily()->at('01:30');
            return;
        }

        $frequency = \App\Models\Setting::get('backup_frequency', 'daily');
        $time = \App\Models\Setting::get('backup_time', '02:00');
        $day = (int) \App\Models\Setting::get('backup_day', 1);

        // Pakai perintah sendiri (database + file upload), BUKAN backup:run bawaan Spatie,
        // supaya hasilnya sama dengan tombol "Jalankan Backup" dan path mysqldump terdeteksi sama.
        $backupEvent = $schedule->command('spj:backup');

        switch ($frequency) {
            case 'daily':
                $backupEvent->dailyAt($time);
                $cleanupEvent->dailyAt('01:30');
                break;
            case 'every_6_hours':
                $backupEvent->everySixHours();
                $cleanupEvent->dailyAt('01:30');
                break;
            case 'every_12_hours':
                $backupEvent->twiceDaily(0, 12);
                $cleanupEvent->dailyAt('01:30');
                break;
            case 'weekly':
                $carbonDay = $day % 7;
                $backupEvent->weeklyOn($carbonDay, $time);
                $cleanupEvent->weeklyOn($carbonDay, '01:30');
                break;
        }

        $schedule->command('backup:monitor')
            ->weekly()
            ->sundays()
            ->at('03:00');
    })
    ->create();
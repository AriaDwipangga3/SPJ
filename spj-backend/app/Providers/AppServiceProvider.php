<?php

namespace App\Providers;

use App\Models\Responsible;
use App\Models\Spj;
use App\Models\SpjDokumen;
use App\Observers\NotificationObserver;
use App\Observers\ResponsibleObserver;
use App\Observers\SpjChangeObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Responsible::observe(ResponsibleObserver::class);
        DatabaseNotification::observe(NotificationObserver::class);

        // Realtime daftar SPJ & dashboard
        Spj::observe(SpjChangeObserver::class);
        SpjDokumen::observe(SpjChangeObserver::class);

        $this->configureRateLimiting();
    }

    /**
     * Batas percobaan untuk endpoint publik (login & lupa password).
     * Kunci = email + IP, jadi satu orang yang salah-salah password tidak
     * mengunci seluruh kantor yang berbagi satu IP.
     */
    protected function configureRateLimiting(): void
    {
        $kunci = fn (Request $r) => strtolower((string) $r->input('email')) . '|' . $r->ip();

        $balasan = fn (string $pesan) => function (Request $request, array $headers) use ($pesan) {
            $detik = (int) ($headers['Retry-After'] ?? 60);

            return response()->json([
                'message' => "{$pesan} Coba lagi dalam {$detik} detik.",
            ], 429, $headers);
        };

        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)
            ->by($kunci($r))
            ->response($balasan('Terlalu banyak percobaan login.')));

        RateLimiter::for('forgot-password', fn (Request $r) => Limit::perMinute(3)
            ->by($kunci($r))
            ->response($balasan('Terlalu banyak permintaan reset password.')));
    }
}

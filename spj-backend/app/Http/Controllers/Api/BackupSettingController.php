<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BackupSettingController extends Controller
{
    protected array $defaults = [
        'backup_enabled' => '1',
        'backup_frequency' => 'daily',
        'backup_time' => '02:00',
        'backup_day' => '1',
    ];

    public function show()
    {
        $settings = Setting::getMany(array_keys($this->defaults), $this->defaults);

        return response()->json([
            'backup_enabled' => $settings['backup_enabled'] === '1',
            'backup_frequency' => $settings['backup_frequency'],
            'backup_time' => $settings['backup_time'],
            'backup_day' => (int) $settings['backup_day'],
            'next_run_label' => $this->hitungNextRun(
                $settings['backup_enabled'] === '1',
                $settings['backup_frequency'],
                $settings['backup_time'],
                (int) $settings['backup_day']
            ),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'backup_enabled' => ['required', 'boolean'],
            'backup_frequency' => ['required', 'in:daily,every_6_hours,every_12_hours,weekly'],
            'backup_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'backup_day' => ['required', 'integer', 'between:1,7'],
        ], [
            'backup_frequency.in' => 'Interval backup tidak valid.',
            'backup_time.regex' => 'Format jam harus HH:MM.',
            'backup_day.between' => 'Hari harus 1 (Senin) s/d 7 (Minggu).',
        ]);

        Setting::set('backup_enabled', $data['backup_enabled'] ? '1' : '0');
        Setting::set('backup_frequency', $data['backup_frequency']);
        Setting::set('backup_time', $data['backup_time']);
        Setting::set('backup_day', (string) $data['backup_day']);

        return response()->json([
            'message' => 'Konfigurasi backup berhasil disimpan.',
            'next_run_label' => $this->hitungNextRun(
                $data['backup_enabled'],
                $data['backup_frequency'],
                $data['backup_time'],
                (int) $data['backup_day']
            ),
        ]);
    }

    protected function hitungNextRun(bool $enabled, string $freq, string $time, int $day): ?string
    {
        if (! $enabled) return null;

        $now = Carbon::now();
        [$jam, $menit] = explode(':', $time);
        $hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulanIndo = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        switch ($freq) {
            case 'daily':
                $next = $now->copy()->setTime((int) $jam, (int) $menit, 0);
                if ($next->isPast()) $next->addDay();
                return $this->labelTanggal($next, $bulanIndo) . " jam {$time} WIB";

            case 'every_6_hours':
            case 'every_12_hours':
                $hours = $freq === 'every_6_hours' ? [0, 6, 12, 18] : [0, 12];
                $next = null;
                foreach ($hours as $h) {
                    $candidate = $now->copy()->setTime($h, 0, 0);
                    if ($candidate->isFuture()) { $next = $candidate; break; }
                }
                if (! $next) $next = $now->copy()->addDay()->setTime(0, 0, 0);
                return $this->labelTanggal($next, $bulanIndo) . ' jam ' . $next->format('H:i') . ' WIB';

            case 'weekly':
                $carbonDay = $day % 7;
                $next = $now->copy()->next($carbonDay)->setTime((int) $jam, (int) $menit, 0);
                return $hariIndo[$carbonDay] . ', ' . $this->labelTanggal($next, $bulanIndo) . " jam {$time} WIB";
        }
        return null;
    }

    protected function labelTanggal(Carbon $date, array $bulanIndo): string
    {
        $hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        if ($date->isToday()) return 'Hari ini, ' . $date->day . ' ' . $bulanIndo[$date->month] . ' ' . $date->year;
        if ($date->isTomorrow()) return 'Besok, ' . $date->day . ' ' . $bulanIndo[$date->month] . ' ' . $date->year;
        return $hariIndo[$date->dayOfWeek] . ', ' . $date->day . ' ' . $bulanIndo[$date->month] . ' ' . $date->year;
    }
}
<?php

namespace App\Console\Commands;

use App\Exceptions\BackupFailedException;
use App\Services\ActivityLogger;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupSpj extends Command
{
    protected $signature = 'spj:backup {--db-only : Hanya database, tanpa foto dokumen & tanda tangan}';

    protected $description = 'Backup database + file dokumen/tanda tangan SPJ ke storage/app/private/SPJ';

    public function handle(BackupService $backup): int
    {
        try {
            $result = $backup->run(! $this->option('db-only'));
        } catch (BackupFailedException $e) {
            $this->error($e->getMessage());
            if ($e->detail !== '') {
                $this->line($e->detail);
            }
            Log::error('Backup terjadwal gagal: ' . $e->getMessage(), ['detail' => $e->detail]);

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Backup gagal: ' . $e->getMessage());
            Log::error('Backup terjadwal gagal: ' . $e->getMessage());

            return self::FAILURE;
        }

        $size = BackupService::formatSize($result['size']);
        $this->info("Backup berhasil: {$result['filename']} ({$size}, {$result['file_count']} file upload)");

        try {
            ActivityLogger::log(
                'backup_run',
                'backup',
                "Backup otomatis berhasil: {$result['filename']}",
                null,
                ['filename' => $result['filename'], 'size' => $size, 'file_upload' => $result['file_count']]
            );
        } catch (\Throwable $e) {
            // Gagal mencatat log tidak boleh membuat backup dianggap gagal.
        }

        return self::SUCCESS;
    }
}

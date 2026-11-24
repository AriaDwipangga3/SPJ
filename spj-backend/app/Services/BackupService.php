<?php

namespace App\Services;

use App\Exceptions\BackupFailedException;
use FilesystemIterator;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Satu-satunya tempat logika backup SPJ. Dipakai oleh:
 *  - tombol "Jalankan Backup" (BackupController)
 *  - jadwal otomatis (perintah `php artisan spj:backup`)
 *
 * Isi zip:
 *  - db-dumps/<nama_database>.sql   -> dump database
 *  - files/spj/...                  -> foto dokumen & tanda tangan (storage/app/public/spj)
 */
class BackupService
{
    /** Folder backup di disk 'local' (storage/app/private/SPJ). */
    public const BACKUP_DIR = 'SPJ';

    public function backupAbsoluteDir(): string
    {
        return Storage::disk('local')->path(self::BACKUP_DIR);
    }

    /**
     * @return array{filename:string, path:string, size:int, file_count:int}
     *
     * @throws BackupFailedException
     */
    public function run(bool $includeFiles = true): array
    {
        @set_time_limit(0);

        $tempDir = storage_path('app/backup-temp');
        $timestamp = now()->format('Y-m-d-H-i-s');
        $sqlFile = "{$tempDir}/dump-{$timestamp}.sql";
        $errFile = "{$tempDir}/err-{$timestamp}.log";

        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        try {
            $database = $this->dumpDatabase($sqlFile, $errFile);

            $zipFileName = "{$timestamp}.zip";
            $backupDir = $this->backupAbsoluteDir();

            if (! is_dir($backupDir)) {
                @mkdir($backupDir, 0777, true);
            }

            $zipPath = $backupDir . DIRECTORY_SEPARATOR . $zipFileName;

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new BackupFailedException('Gagal membuat file zip.');
            }

            $zip->addFile($sqlFile, "db-dumps/{$database}.sql");

            $fileCount = $includeFiles ? $this->addUploadedFiles($zip) : 0;

            // File sebenarnya dibaca saat close(), jadi $sqlFile baru boleh dihapus sesudahnya.
            if (! $zip->close()) {
                @unlink($zipPath);
                throw new BackupFailedException('Gagal menulis file zip (disk penuh atau izin folder?).');
            }

            return [
                'filename' => $zipFileName,
                'path' => $zipPath,
                'size' => (int) filesize($zipPath),
                'file_count' => $fileCount,
            ];
        } finally {
            @unlink($sqlFile);
            @unlink($errFile);
        }
    }

    /**
     * Tambahkan semua file upload (dokumen & TTD) ke zip di bawah files/spj/.
     */
    protected function addUploadedFiles(ZipArchive $zip): int
    {
        $root = Storage::disk('public')->path('spj');

        if (! is_dir($root)) {
            return 0;
        }

        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $zip->addFile($file->getPathname(), 'files/spj/' . $relative);
            $count++;
        }

        return $count;
    }

    /**
     * Dump database ke $sqlFile pakai mysqldump. Mengembalikan nama database.
     */
    protected function dumpDatabase(string $sqlFile, string $errFile): string
    {
        $connection = config('database.default');
        $db = config("database.connections.{$connection}");

        if (! in_array($db['driver'] ?? '', ['mysql', 'mariadb'], true)) {
            throw new BackupFailedException('Backup hanya mendukung database MySQL/MariaDB.');
        }

        $mysqldump = $this->getMysqldumpPath();

        if (! $mysqldump) {
            throw new BackupFailedException(
                'mysqldump tidak ditemukan di sistem ini. '
                . 'Set path-nya manual di .env dengan MYSQLDUMP_PATH=C:\\path\\ke\\mysqldump.exe '
                . 'lalu jalankan: php artisan config:clear'
            );
        }

        $database = $db['database'];
        $password = $db['password'] ?? '';

        $baseParts = [
            '"' . $mysqldump . '"',
            '--host=' . escapeshellarg($db['host']),
            '--port=' . escapeshellarg((string) ($db['port'] ?? 3306)),
            '--user=' . escapeshellarg($db['username']),
            '--single-transaction',
            '--routines',
            '--triggers',
            '--events',
        ];

        if (! empty($password)) {
            $baseParts[] = '--password=' . escapeshellarg($password);
        }

        // Flag ini tidak dikenal semua versi (mis. MariaDB tidak punya --column-statistics).
        // Kalau mysqldump menolak sebuah flag, buang flag itu lalu coba lagi.
        $optionalFlags = ['--skip-ssl', '--column-statistics=0'];

        $cmd = '';
        $exitCode = 0;
        $errorOutput = '';

        for ($attempt = 0; $attempt <= count($optionalFlags); $attempt++) {
            $cmdParts = array_merge($baseParts, $optionalFlags);
            $cmdParts[] = escapeshellarg($database);
            $cmdParts[] = '> ' . escapeshellarg($sqlFile);
            $cmdParts[] = '2> ' . escapeshellarg($errFile);

            $cmd = implode(' ', $cmdParts);

            $output = [];
            $exitCode = 0;
            exec($cmd, $output, $exitCode);

            $errorOutput = file_exists($errFile) ? trim((string) file_get_contents($errFile)) : '';

            if ($exitCode === 0 && file_exists($sqlFile) && filesize($sqlFile) > 0) {
                return $database;
            }

            if (preg_match("/unknown option '(--[a-zA-Z0-9=_-]+)'/i", $errorOutput, $m)) {
                $unknownFlag = $m[1];
                $removed = false;

                foreach ($optionalFlags as $i => $flag) {
                    if (str_starts_with($flag, $unknownFlag)) {
                        unset($optionalFlags[$i]);
                        $removed = true;
                    }
                }

                $optionalFlags = array_values($optionalFlags);
                @unlink($sqlFile);
                @unlink($errFile);

                if ($removed) {
                    continue;
                }
            }

            break;
        }

        throw new BackupFailedException(
            'Backup gagal (exit code ' . $exitCode . '). Pastikan MySQL sedang menyala.',
            $errorOutput ?: 'Tidak ada pesan error. Perintah: ' . substr($cmd, 0, 300)
        );
    }

    protected function getMysqldumpPath(): ?string
    {
        // Dibaca lewat config() (bukan env()) supaya tetap jalan saat config di-cache.
        $envPath = config('spj.mysqldump_path');
        if ($envPath && file_exists($envPath)) {
            return $envPath;
        }

        $laragonMysqlDir = 'C:\\laragon\\bin\\mysql';
        if (is_dir($laragonMysqlDir)) {
            $versions = glob($laragonMysqlDir . '\\mysql-*', GLOB_ONLYDIR) ?: [];
            rsort($versions);
            foreach ($versions as $versionDir) {
                $candidate = $versionDir . '\\bin\\mysqldump.exe';
                if (file_exists($candidate)) {
                    return $candidate;
                }
            }
        }

        foreach (['C:\\xampp\\mysql\\bin\\mysqldump.exe'] as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        $which = PHP_OS_FAMILY === 'Windows' ? 'where mysqldump' : 'which mysqldump';
        exec($which . ' 2>&1', $output, $exitCode);
        if ($exitCode === 0 && ! empty($output[0]) && file_exists(trim($output[0]))) {
            return trim($output[0]);
        }

        return null;
    }

    public static function formatSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1024 * 1024 * 1024) return round($bytes / 1024 / 1024, 2) . ' MB';
        return round($bytes / 1024 / 1024 / 1024, 2) . ' GB';
    }
}

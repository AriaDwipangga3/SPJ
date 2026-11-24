<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BackupFailedException;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    protected function getBackupPath(): string
    {
        return BackupService::BACKUP_DIR;
    }

    public function index()
    {
        $path = $this->getBackupPath();

        if (! Storage::disk('local')->exists($path)) {
            Storage::disk('local')->makeDirectory($path);
        }

        $files = Storage::disk('local')->files($path);
        $backups = [];

        foreach ($files as $file) {
            if (! str_ends_with($file, '.zip')) continue;

            $size = Storage::disk('local')->size($file);
            $mtime = Storage::disk('local')->lastModified($file);

            $backups[] = [
                'filename' => basename($file),
                'path' => $file,
                'size_kb' => intdiv($size, 1024),
                'size_formatted' => BackupService::formatSize($size),
                'created_at' => date('Y-m-d H:i:s', $mtime),
                'created_at_human' => \Carbon\Carbon::createFromTimestamp($mtime)->diffForHumans(),
            ];
        }

        usort($backups, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));

        $totalSize = array_sum(array_map(fn ($b) => $b['size_kb'] * 1024, $backups));

        return response()->json([
            'backups' => $backups,
            'total_count' => count($backups),
            'total_size_formatted' => BackupService::formatSize($totalSize),
        ]);
    }

    public function run(Request $request, BackupService $backup)
    {
        try {
            $result = $backup->run();
        } catch (BackupFailedException $e) {
            Log::error('Backup manual gagal: ' . $e->getMessage(), ['detail' => $e->detail]);

            return response()->json([
                'message' => $e->getMessage(),
                'output' => $e->detail,
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Backup manual gagal: ' . $e->getMessage());

            $payload = ['message' => 'Backup gagal: ' . $e->getMessage()];
            // Detail teknis hanya untuk mode debug (jangan bocor ke pengguna produksi).
            if (config('app.debug')) {
                $payload['trace'] = $e->getTraceAsString();
            }

            return response()->json($payload, 500);
        }

        $size = BackupService::formatSize($result['size']);

        // ============ LOG ============
        ActivityLogger::log(
            'backup_run',
            'backup',
            "Backup manual berhasil: {$result['filename']}",
            null,
            ['filename' => $result['filename'], 'size' => $size, 'file_upload' => $result['file_count']],
            $request
        );

        return response()->json([
            'message' => 'Backup berhasil dijalankan.',
            'filename' => $result['filename'],
            'size' => $size,
            'file_count' => $result['file_count'],
            'output' => 'Backup completed at ' . now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function download(string $filename)
    {
        $filename = basename($filename);
        $path = $this->getBackupPath() . '/' . $filename;

        if (! Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'File backup tidak ditemukan.'], 404);
        }

        // ============ LOG ============
        ActivityLogger::log(
            'backup_download',
            'backup',
            "Download backup: {$filename}",
            null,
            ['filename' => $filename]
        );

        return Storage::disk('local')->download($path);
    }

    public function destroy(string $filename)
    {
        $filename = basename($filename);
        $path = $this->getBackupPath() . '/' . $filename;

        if (! Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'File backup tidak ditemukan.'], 404);
        }

        Storage::disk('local')->delete($path);

        // ============ LOG ============
        ActivityLogger::log(
            'backup_delete',
            'backup',
            "Hapus backup: {$filename}",
            null,
            ['filename' => $filename]
        );

        return response()->json(['message' => 'File backup berhasil dihapus.']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    /**
     * List activity logs dengan filter & pagination.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::query()->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($dari = $request->input('dari_tanggal')) {
            $query->whereDate('created_at', '>=', $dari);
        }
        if ($sampai = $request->input('sampai_tanggal')) {
            $query->whereDate('created_at', '<=', $sampai);
        }

        return $query->paginate($request->input('per_page', 25));
    }

    /**
     * Detail 1 log.
     */
    public function show(ActivityLog $activityLog)
    {
        return response()->json($activityLog);
    }

    /**
     * Ringkasan statistik log.
     */
    public function summary(Request $request)
    {
        $dari = $request->input('dari_tanggal', now()->subDays(30)->format('Y-m-d'));
        $sampai = $request->input('sampai_tanggal', now()->format('Y-m-d'));

        $base = ActivityLog::whereBetween('created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59']);

        return response()->json([
            'total' => (clone $base)->count(),
            'per_category' => (clone $base)
                ->selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->get()
                ->map(fn ($r) => [
                    'category' => $r->category,
                    'count' => $r->count,
                ]),
            'per_action' => (clone $base)
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(fn ($r) => [
                    'action' => $r->action,
                    'count' => $r->count,
                ]),
            'top_users' => (clone $base)
                ->selectRaw('user_id, user_name, COUNT(*) as count')
                ->whereNotNull('user_id')
                ->groupBy('user_id', 'user_name')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->map(fn ($r) => [
                    'user_id' => $r->user_id,
                    'user_name' => $r->user_name,
                    'count' => $r->count,
                ]),
        ]);
    }

    /**
     * Hapus log lebih lama dari X hari.
     * Default 90 hari.
     */
    public function cleanup(Request $request)
    {
        $days = (int) $request->input('days', 90);
        $cutoff = now()->subDays($days);

        $count = ActivityLog::where('created_at', '<', $cutoff)->delete();

        ActivityLogger::log(
            'log_cleanup',
            'master',
            "Hapus {$count} log lebih lama dari {$days} hari"
        );

        return response()->json([
            'message' => "{$count} log lebih lama dari {$days} hari berhasil dihapus.",
            'deleted_count' => $count,
        ]);
    }
}
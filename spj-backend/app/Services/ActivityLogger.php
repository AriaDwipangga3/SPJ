<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogger
{
    /**
     * Log aktivitas user.
     *
     * @param string $action    Aksi yang dilakukan (spj_create, spj_verify, dll)
     * @param string $category  Kategori (auth, spj, master, user, backup, profile)
     * @param string|null $description  Deskripsi singkat
     * @param Model|null $subject  Objek yang dioperasikan
     * @param array|null $metadata  Data tambahan
     * @param Request|null $request  Request object untuk IP/UA
     */
    public static function log(
        string $action,
        string $category,
        ?string $description = null,
        ?Model $subject = null,
        ?array $metadata = null,
        ?Request $request = null
    ): ActivityLog {
        $request = $request ?? request();
        $user = $request?->user();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_role' => $user?->role,
            'action' => $action,
            'category' => $category,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
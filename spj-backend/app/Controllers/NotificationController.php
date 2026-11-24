<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Spj;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Ambil daftar notifikasi user (terbaru dulu).
     * Query param:
     *  - limit: jumlah maksimal (default 20)
     *  - unread_only: 1 untuk filter unread saja
     */
    public function index(Request $request)
    {
        $query = $request->user()->notifications();

        if ($request->input('unread_only') === '1') {
            $query->whereNull('read_at');
        }

        $limit = (int) $request->input('limit', 20);
        $notifs = $query->limit($limit)->get();

        // Ambil slug semua SPJ yang dirujuk dalam 1 query. Link notifikasi lama
        // tersimpan sebagai /spj/{id}, padahal route detail memakai slug.
        $slugs = Spj::whereIn('id', $notifs->pluck('data.spj_id')->filter()->unique())
            ->pluck('slug', 'id');

        $items = $notifs->map(function ($n) use ($slugs) {
            $spjId = $n->data['spj_id'] ?? null;
            $url = $n->data['url'] ?? null;
            if ($spjId) {
                // SPJ sudah dihapus -> url null (tidak diarahkan ke halaman kosong)
                $url = isset($slugs[$spjId]) ? "/spj/{$slugs[$spjId]}" : null;
            }

            return [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'info',
                'title' => $n->data['title'] ?? 'Notifikasi',
                'message' => $n->data['message'] ?? '',
                'catatan' => $n->data['catatan'] ?? null,
                'spj_id' => $n->data['spj_id'] ?? null,
                'nomor_spj' => $n->data['nomor_spj'] ?? null,
                'url' => $url,
                'icon' => $n->data['icon'] ?? 'notifications',
                'color' => $n->data['color'] ?? 'info',
                'read' => ! is_null($n->read_at),
                'created_at' => $n->created_at->toIso8601String(),
                'created_at_human' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'notifications' => $items,
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'total' => $request->user()->notifications()->count(),
        ]);
    }

    /**
     * Hitung saja jumlah unread — endpoint ringan untuk polling.
     */
    public function unreadCount(Request $request)
    {
        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Tandai 1 notif sebagai read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notif = $request->user()->notifications()->where('id', $id)->first();

        if (! $notif) {
            return response()->json(['message' => 'Notifikasi tidak ditemukan.'], 404);
        }

        if (is_null($notif->read_at)) {
            $notif->markAsRead();
        }

        return response()->json([
            'message' => 'Ditandai sudah dibaca.',
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Tandai semua notif sebagai read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'Semua notifikasi ditandai sudah dibaca.',
            'unread_count' => 0,
        ]);
    }

    /**
     * Hapus 1 notif.
     */
    public function destroy(Request $request, string $id)
    {
        $notif = $request->user()->notifications()->where('id', $id)->first();

        if (! $notif) {
            return response()->json(['message' => 'Notifikasi tidak ditemukan.'], 404);
        }

        $notif->delete();

        return response()->json([
            'message' => 'Notifikasi dihapus.',
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Hapus semua notif read.
     */
    public function clearRead(Request $request)
    {
        $count = $request->user()->readNotifications()->delete();

        return response()->json([
            'message' => "{$count} notifikasi dihapus.",
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
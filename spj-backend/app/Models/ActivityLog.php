<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'user_role',
        'action', 'category',
        'subject_type', 'subject_id',
        'description', 'metadata',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Label Indonesia untuk kategori.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'auth' => 'Autentikasi',
            'spj' => 'SPJ',
            'master' => 'Master Data',
            'user' => 'Kelola User',
            'backup' => 'Backup',
            'profile' => 'Profil',
            default => ucfirst($this->category),
        };
    }

    /**
     * Label Indonesia untuk action.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'login' => 'Login',
            'logout' => 'Logout',
            'password_reset_request' => 'Minta Reset Password',
            'password_reset' => 'Reset Password',
            'password_change' => 'Ganti Password',
            'profile_update' => 'Update Profil',
            'spj_create' => 'Buat SPJ',
            'spj_update' => 'Edit SPJ',
            'spj_delete' => 'Hapus SPJ',
            'spj_verify' => 'Verifikasi SPJ',
            'spj_reject' => 'Tolak SPJ',
            'spj_pdf' => 'Cetak PDF SPJ',
            'spj_export' => 'Ekspor SPJ',
            'dokumen_upload' => 'Upload Dokumen',
            'dokumen_delete' => 'Hapus Dokumen',
            'ttd_save' => 'Simpan TTD',
            'jenis_create' => 'Tambah Jenis SPJ',
            'jenis_update' => 'Edit Jenis SPJ',
            'jenis_delete' => 'Hapus Jenis SPJ',
            'dokumen_wajib_create' => 'Tambah Dokumen Wajib',
            'dokumen_wajib_delete' => 'Hapus Dokumen Wajib',
            'responsible_create' => 'Tambah Responsible',
            'responsible_update' => 'Edit Responsible',
            'responsible_delete' => 'Hapus Responsible',
            'user_create' => 'Tambah User',
            'user_update' => 'Edit User',
            'user_delete' => 'Hapus User',
            'backup_run' => 'Jalankan Backup',
            'backup_download' => 'Download Backup',
            'backup_delete' => 'Hapus Backup',
            'backup_setting_update' => 'Update Setting Backup',
            default => ucwords(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Warna badge per action (untuk frontend).
     */
    public function getActionColorAttribute(): string
    {
        if (str_contains($this->action, 'delete')) return 'error';
        if (str_contains($this->action, 'reject')) return 'warning';
        if (str_contains($this->action, 'create') || str_contains($this->action, 'verify')) return 'success';
        if (str_contains($this->action, 'update')) return 'info';
        return 'default';
    }
}
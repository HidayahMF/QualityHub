<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Field yang boleh diisi melalui mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'department_id',
        'role',
        'account_status',
        'last_login_at',
        'password',
    ];

    /**
     * Field yang disembunyikan saat model
     * dikonversi menjadi array / JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting tipe data.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Department tempat user berada.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Dokumen yang didaftarkan / di-upload oleh user.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    /**
     * Dokumen favorit milik user.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Riwayat aktivitas user.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Role Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Cek apakah user adalah Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Cek apakah user adalah Admin.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [
            'super_admin',
            'admin',
        ]);
    }

    /**
     * Cek apakah user adalah User biasa.
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Cek apakah user adalah Viewer.
     */
    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    /*
    |--------------------------------------------------------------------------
    | Account Status Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Cek apakah akun aktif.
     */
    public function isActive(): bool
    {
        return $this->account_status === 'active';
    }

    /**
     * Cek apakah akun masih menunggu approval.
     */
    public function isPending(): bool
    {
        return $this->account_status === 'pending';
    }

    /**
     * Cek apakah akun dinonaktifkan.
     */
    public function isInactive(): bool
    {
        return $this->account_status === 'inactive';
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'role',
        'no_hp',
        'nip',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isBendahara(): bool
    {
        return $this->role === 'bendahara';
    }

    public function isKetua(): bool
    {
        return $this->role === 'ketua';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'bendahara', 'ketua'], true);
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function canManageFinance(): bool
    {
        return in_array($this->role, ['admin', 'bendahara'], true);
    }

    public function canInput(): bool
    {
        return $this->canManageFinance();
    }

    public function donasi()
    {
        return $this->hasMany(Donasi::class, 'user_id');
    }

    public function transaksiDibuat()
    {
        return $this->hasMany(Transaksi::class, 'dibuat_oleh');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }
}

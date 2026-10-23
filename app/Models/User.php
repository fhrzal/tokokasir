<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_KASIR = 'kasir';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_PEMILIK = 'pemilik';

    public const ROLES = [
        self::ROLE_KASIR => 'Kasir',
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_PEMILIK => 'Pemilik Toko',
    ];

    protected $primaryKey = 'user_id';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'user_id');
    }

    public function openShift(): HasOne
    {
        return $this->hasOne(Shift::class, 'user_id')->whereNull('waktu_tutup')->latestOfMany('waktu_buka');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }

    /**
     * Halaman awal setelah login sesuai peran.
     */
    public function homeRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => route('admin.dashboard'),
            self::ROLE_PEMILIK => route('pemilik.dashboard'),
            default => route('kasir.pos'),
        };
    }
}

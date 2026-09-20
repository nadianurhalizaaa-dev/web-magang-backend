<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Relasi ke aktivitas magang yang dibuat oleh user (admin).
     */
    public function aktivitasMagang()
    {
        return $this->hasMany(AktivitasMagang::class, 'user_id');
    }

    /**
     * Relasi ke komentar aktivitas yang dibuat oleh user.
     */
    public function komentarAktivitas()
    {
        return $this->hasMany(KomentarAktivitas::class, 'user_id');
    }

    /**
     * Relasi ke komentar ulasan aplikasi yang dibuat oleh user.
     */
    public function komentarAplikasi()
    {
        return $this->hasMany(KomentarAplikasi::class, 'user_id');
    }
}

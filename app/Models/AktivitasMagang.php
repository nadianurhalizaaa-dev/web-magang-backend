<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AktivitasMagang extends Model
{
    use HasFactory;

    protected $table = 'aktivitas_magang';

    protected $fillable = [
        'user_id',
        'judul',
        'deskripsi',
        'tanggal',
        'kategori',
        'lokasi',
        'foto',
        'video',
        'status',
    ];

    protected $appends = ['foto_url', 'video_url'];

    /**
     * Accessor untuk mendapatkan URL lengkap foto aktivitas.
     */
    public function getFotoUrlAttribute()
    {
        if ($this->foto) {
            return asset($this->foto);
        }
        return null;
    }

    /**
     * Accessor untuk mendapatkan URL lengkap video aktivitas.
     */
    public function getVideoUrlAttribute()
    {
        if (! $this->video) {
            return null;
        }

        if (str_starts_with($this->video, 'http://') || str_starts_with($this->video, 'https://')) {
            return $this->video;
        }

        return asset($this->video);
    }

    /**
     * Relasi ke Admin/User yang menginput aktivitas magang.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke daftar komentar pengguna pada aktivitas magang ini.
     */
    public function komentar()
    {
        return $this->hasMany(KomentarAktivitas::class, 'aktivitas_magang_id')->latest();
    }
}

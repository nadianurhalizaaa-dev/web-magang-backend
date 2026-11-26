<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Logbook extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'kategori',
        'judul_kegiatan',
        'deskripsi_kegiatan',
        'foto_kegiatan',
        'link_video',
        'status',
        'catatan_pembimbing',
        'verified_by',
        'verified_at',
    ];

    protected $appends = [
        'foto_url',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'verified_at' => 'datetime',
    ];

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_kegiatan ? '/storage/' . ltrim($this->foto_kegiatan, '/') : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}


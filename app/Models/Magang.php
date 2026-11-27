<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Magang extends Model
{
    protected $table = 'magangs';

    protected $fillable = [
        'participant_code',
        'participant_name',
        'institution',
        'status',
        'periode_id',
        'pembimbing_id',
    ];

    public function pembimbing()
    {
        return $this->belongsTo(Pembimbing::class);
    }

    public function absensis()
    {
        return $this->hasMany(Absensi::class, 'participant_code', 'participant_code');
    }
}

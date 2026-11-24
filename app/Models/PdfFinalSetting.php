<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfFinalSetting extends Model
{
    protected $table = 'pdf_final_settings';

    public $incrementing = false;

    protected $fillable = [
        'signature_path',
    ];
}

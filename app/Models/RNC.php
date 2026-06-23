<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RNC extends Model
{
    protected $table = 'rncs';

    protected $fillable = [
        'rnc',
        'razon_social',
        'actividad',
        'status',
        'type',
    ];
}

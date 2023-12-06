<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'language_name',
        'language_code',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}

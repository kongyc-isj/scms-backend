<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class FieldData extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'language_code',
        'field_key_value',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}

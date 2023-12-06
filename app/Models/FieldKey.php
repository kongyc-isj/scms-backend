<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class FieldKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'field_type_id',
        'field_key_name',
        'field_key_description',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class FieldType extends Model
{
    use HasFactory;
    protected $collection = 'field_type';

    protected $fillable = [
        'field_type_tag',
        'field_type_name',
        'field_type_description',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class FieldKey extends Model
{
    use HasFactory;
    protected $collection = 'field_key';

    protected $fillable = [
        'component_id',
        'field_type_name',
        'field_key_name',
        'field_key_description',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function component()
    {
        return $this->belongsTo(Component::class, 'component_id');
    }
    
}

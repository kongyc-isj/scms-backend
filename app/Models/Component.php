<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class Component extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'board_id',
        'component_name',
        'component_description',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}

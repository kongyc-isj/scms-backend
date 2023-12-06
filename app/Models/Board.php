<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class Board extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'board_name',
        'board_description',
        'board_api_key',
        'board_owner_user',
        'board_shared_user',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}

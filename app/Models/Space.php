<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class Space extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_name',
        'space_description',
        'space_owner_user',
        'space_shared_user',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    //protected $casts = [
    //    'space_owner_user' => 'json',
    //    'space_shared_user' => 'json',
    //];
}

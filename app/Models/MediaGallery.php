<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class MediaGallery extends Model
{
    use HasFactory;

    protected $collection = 'media_gallery';

    protected $fillable = [
        'media_name',
        'media_description',
        'media_url',
        'board_id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $collection = 'audit_log';

    protected $fillable = [
        'email',
        'board_id',
        'action',
        'description',
        'created_at',
        'deleted_at'
    ];

    
    public static function logAction($boardId, $email, $action, $description)
    {

        self::create([
            'board_id' => $boardId,
            'email' => $email,
            'action' => $action,
            'description' => $description,
            'deleted_at' => null
        ]);
        
    }
}

<?php

/** @noinspection SpellCheckingInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'fw_notifications';

    protected $fillable = [
        'title', 'description', 'link', 'user_id', 'is_seen', 'is_read'
    ];
}

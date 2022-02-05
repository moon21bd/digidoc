<?php

namespace App\Models;

use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class BoxComment extends Model
{
    protected $casts = [

    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'box_comments';
    protected $fillable = ['box_id', 'title', 'created_by', 'updated_by'];

    public function creator()
    {
        return $this->belongsTo(Administrator::class, 'created_by', 'id');
    }

}

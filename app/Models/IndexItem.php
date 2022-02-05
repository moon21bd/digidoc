<?php

namespace App\Models;

use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class IndexItem extends Model
{
    protected $casts = [

    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'index_items';
    protected $fillable = ['title', 'box_id', 'created_by', 'updated_by'];

    public function creator()
    {
        return $this->belongsTo(Administrator::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(Administrator::class, 'updated_by', 'id');
    }

}

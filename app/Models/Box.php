<?php

namespace App\Models;

use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class Box extends Model
{
    // this is a recommended way to declare event handlers
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($box) { // before delete() method call this
            $box->index_item()->delete();
            $box->box_comment()->delete();
        });
    }

    protected $casts = [

    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'boxes';
    protected $fillable = ['serial_no', 'box_image', 'status', 'created_by', 'updated_by'];

    public function creator()
    {
        return $this->belongsTo(Administrator::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(Administrator::class, 'updated_by', 'id');
    }

    public function index_item()
    {
        return $this->hasMany(IndexItem::class);
    }

    public function box_comment()
    {
        return $this->hasMany(BoxComment::class);
    }

    public function setBoxImageAttribute($pictures)
    {
        if (is_array($pictures)) {
            $this->attributes['box_image'] = json_encode($pictures);
        }
    }

    public function getBoxImageAttribute($pictures)
    {
        return json_decode($pictures, true);
    }

}

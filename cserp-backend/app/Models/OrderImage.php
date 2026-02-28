<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class OrderImage extends Model
{
    protected $fillable = [
        'order_id',
        'filename',
        'path',
        'thumbnail_path',
        'mime_type',
        'size',
        'description',
        'sort_order'
    ];

    protected $appends = ['url', 'thumbnail_url'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getUrlAttribute()
    {
        return Storage::url($this->path);
    }

    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail_path) {
            return Storage::url($this->thumbnail_path);
        }
        return $this->url;
    }
}

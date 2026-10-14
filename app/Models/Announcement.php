<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'category',
        'summary',
        'content',
        'image_url',
        'published_at',
        'is_published',
        'author_id',
    ];

    protected $casts = [
        'published_at' => 'date:Y-m-d',
        'is_published' => 'boolean',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}

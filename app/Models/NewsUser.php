<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class NewsUser extends Pivot
{
    protected $table = 'news_users';
    
    public $incrementing = false;
    
    protected $primaryKey = ['user_id', 'news_id'];
    
    protected $fillable = ['user_id', 'news_id', 'send_at'];
    protected $casts = [
        'user_id' => 'string',
        'news_id' => 'string',
        'send_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope('uuid', function ($query) {
            $query->whereKey(request()->user()->id); // Adapte conforme necessário
            $query->whereKey(request()->news()->id); // Adapte conforme necessário
        });
    }
}

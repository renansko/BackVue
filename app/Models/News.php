<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Ramsey\Uuid\Uuid;

class News extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'news';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'pubDate',
        'link',
        'image_url',
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'title' => 'string',
        'description' => 'string',
        'pubDate' => 'datetime',
        'link' => 'string',
        'image_url' => 'string',
    ];

    protected static function booted()
    {
        
        static::creating(function ($model) {
            $model->id = (string) Uuid::uuid7();
        });
    }
}

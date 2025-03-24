<?php

namespace App\Models;

use App\Events\NewsProcessedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Ramsey\Uuid\Uuid;

class News extends Model
{
    use HasApiTokens, HasFactory, Notifiable, Prunable;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'description',
        'pubDate',
        'link',
        'image_url',
        'news_hash'
    ];

    protected $hidden = ['pivot'];


    protected $casts = [
        'id'            => 'string',
        'title'         => 'string',
        'description'   => 'string',
        'pubDate'       => 'datetime',
        'link'          => 'string',
        'image_url'     => 'string',
        'news_hash'     => 'string',
    ];


    // This dispache a event to send a news 'if created' 
    // Is commented why is not util if you call a Job every day 
    // Only if you want a creating a new manualy
    // I used for Testing
    // protected $dispatchesEvents = [
    //     'created' => NewsProcessedEvent::class,
    // ];
    //php artisan queue:work --tries=3 --timeout=120

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->id = (string) Uuid::uuid7();
        });
    }
    public $incrementing = false;
    protected $keyType = 'string';

    public function users()
    {
        return $this->belongsToMany(User::class, 'news_users')
            ->using(NewsUser::class)
            ->withPivot('send_at')
            ->withTimestamps()
            ->withPivot(['user_id', 'news_id']);
    }

    public function prunable()
    {
        return self::where('pubDate', '<', now()->subDays(30));
    }
}
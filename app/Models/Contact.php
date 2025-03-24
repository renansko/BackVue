<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Ramsey\Uuid\Uuid;

class Contact extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'contacts';

    protected $fillable = [
        'phone',
    ];

    protected $casts = [
        'id'        => 'string',
        'phone' => 'string',
        'user_id' => 'string',
    ];

    protected static function booted()
    {
        
        static::creating(function ($model) {
            $model->id = (string) Uuid::uuid7();
        });
    }

    public function users()
    {
        return $this->belongsTo(User::class);
    }
}

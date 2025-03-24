<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Ramsey\Uuid\Uuid;

class Company extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'email',
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'string',
        'email' => 'string',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
    
    protected static function booted()
    {
        
        static::creating(function ($model) {
            $model->id = (string) Uuid::uuid7();
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}

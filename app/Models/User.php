<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Ramsey\Uuid\Uuid;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'company_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pivot'
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'id' => 'string',
        'phone' => 'string',
        'company_id' => 'string'
    ];
    
    public $incrementing = false;
    protected $keyType = 'string';
    
    // Load all information of contatcs and companies
    // protected $with = ['contacts', 'companies'];
    
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->id = (string) Uuid::uuid7();
        });

        // Trait to load some information of contacts and companies
        // Less processing
        static::addGlobalScope('withLimitedRelations', function ($builder) {
            $builder->with([
                'contacts:id,user_id,phone',  // Only select phone field from contacts
                'companies:id,name,email'        // Select name and email from company
            ]);
        });
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function companies()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function news()
    {
        return $this->belongsToMany(News::class, 'news_users')
            ->using(NewsUser::class)
            ->withPivot('send_at')
            ->withTimestamps()
            ->withPivot(['user_id', 'news_id']);
    }
    
}

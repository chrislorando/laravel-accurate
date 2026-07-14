<?php

namespace ChrisLorando\LaravelAccurate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccurateConnection extends Model
{
    protected $table = 'accurate_connections';

    protected $fillable = [
        'name', 'client_id', 'client_secret',
        'access_token', 'refresh_token', 'token_type', 'expires_at', 
        'accurate_user_id', 'accurate_user_name', 'accurate_user_nickname', 'accurate_user_email', 'accurate_user_mobile',
        'scopes'
    ];

    protected $hidden = ['client_secret', 'access_token', 'refresh_token'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'scopes' => 'array',

            'client_secret' => 'encrypted',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
        ];
    }

    public function databases(): HasMany
    {
        return $this->hasMany(AccurateDatabase::class, 'connection_id');
    }

    public function isTokenExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}

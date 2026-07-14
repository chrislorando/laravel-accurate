<?php

namespace ChrisLorando\LaravelAccurate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $client_id
 * @property string $client_secret
 * @property string $access_token
 * @property string|null $refresh_token
 * @property string $token_type
 * @property Carbon|null $expires_at
 * @property string|null $accurate_user_id
 * @property string|null $accurate_user_name
 * @property string|null $accurate_user_nickname
 * @property string|null $accurate_user_email
 * @property string|null $accurate_user_mobile
 * @property array|null $scopes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AccurateConnection extends Model
{
    protected $table = 'accurate_connections';

    protected $fillable = [
        'name', 'client_id', 'client_secret',
        'access_token', 'refresh_token', 'token_type', 'expires_at',
        'accurate_user_id', 'accurate_user_name', 'accurate_user_nickname', 'accurate_user_email', 'accurate_user_mobile',
        'scopes',
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

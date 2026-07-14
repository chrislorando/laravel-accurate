<?php

namespace ChrisLorando\LaravelAccurate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $connection_id
 * @property string $database_id
 * @property string $alias
 * @property string $company_name
 * @property string $host
 * @property string $session_id
 * @property \Illuminate\Support\Carbon|null $session_expires_at
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \ChrisLorando\LaravelAccurate\Models\AccurateConnection $connection
 */
class AccurateDatabase extends Model
{
    protected $table = 'accurate_databases';

    protected $fillable = [
        'connection_id', 'database_id', 'alias', 'company_name',
        'host', 'session_id', 'session_expires_at', 'is_default',
    ];

    protected $hidden = ['session_id'];

    protected function casts(): array
    {
        return [
            'session_id' => 'encrypted',
            'session_expires_at' => 'datetime',
            'is_default' => 'boolean',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(AccurateConnection::class, 'connection_id');
    }

    public function isSessionExpired(): bool
    {
        return $this->session_expires_at && $this->session_expires_at->isPast();
    }
}

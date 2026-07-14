<?php

namespace ChrisLorando\LaravelAccurate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

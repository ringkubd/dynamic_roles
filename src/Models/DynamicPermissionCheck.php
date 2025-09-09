<?php

namespace Anwar\DynamicRoles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DynamicPermissionCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'dynamic_url_id',
        'permission_name',
        'granted',
        'reason',
        'ip_address',
        'user_agent',
        'checked_at',
    ];

    protected $casts = [
        'granted' => 'boolean',
        'checked_at' => 'datetime',
    ];

    /**
     * Get the table name from config.
     */
    public function getTable(): string
    {
        return config('dynamic-roles.database.tables.dynamic_permission_checks', 'dynamic_permission_checks');
    }

    /**
     * User who was checked.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * URL that was checked.
     */
    public function dynamicUrl(): BelongsTo
    {
        return $this->belongsTo(DynamicUrl::class);
    }

    /**
     * Scope for granted permissions.
     */
    public function scopeGranted($query)
    {
        return $query->where('granted', true);
    }

    /**
     * Scope for denied permissions.
     */
    public function scopeDenied($query)
    {
        return $query->where('granted', false);
    }

    /**
     * Scope for specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}

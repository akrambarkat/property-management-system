<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Notification extends Model
{
    use SoftDeletes;

    public const TYPE_CONTRACT = 'contract';
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_TENANT = 'tenant';
    public const TYPE_MAINTENANCE = 'maintenance';
    public const TYPE_SMS = 'sms';
    public const TYPE_BUILDING = 'building';
    public const TYPE_SYSTEM = 'system';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    public const CATEGORY_EXPIRATION = 'expiration';
    public const CATEGORY_OVERDUE = 'overdue';
    public const CATEGORY_PAYMENT = 'payment';
    public const CATEGORY_CREATED = 'created';
    public const CATEGORY_UPDATED = 'updated';
    public const CATEGORY_COMPLETED = 'completed';
    public const CATEGORY_FAILED = 'failed';
    public const CATEGORY_ALERT = 'alert';

    protected $fillable = [
        'user_id', 'title', 'message', 'type', 'priority', 'category',
        'related_model', 'related_id', 'is_read', 'is_archived',
        'delivery_channel', 'action_url', 'read_at', 'archived_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'is_archived' => 'boolean',
            'read_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update(['is_read' => true, 'read_at' => now()]);
        }
    }

    public function markAsUnread()
    {
        $this->update(['is_read' => false, 'read_at' => null]);
    }

    public function archive()
    {
        $this->update(['is_archived' => true, 'archived_at' => now()]);
    }

    public function unarchive()
    {
        $this->update(['is_archived' => false, 'archived_at' => null]);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => '#6b7280',
            self::PRIORITY_MEDIUM => '#f59e0b',
            self::PRIORITY_HIGH => '#f97316',
            self::PRIORITY_CRITICAL => '#ef4444',
            default => '#6b7280',
        };
    }

    public function getPriorityIconAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'pi pi-info-circle',
            self::PRIORITY_MEDIUM => 'pi pi-exclamation-triangle',
            self::PRIORITY_HIGH => 'pi pi-exclamation-circle',
            self::PRIORITY_CRITICAL => 'pi pi-shield',
            default => 'pi pi-info-circle',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_CONTRACT => 'pi pi-file',
            self::TYPE_INVOICE => 'pi pi-dollar',
            self::TYPE_TENANT => 'pi pi-user',
            self::TYPE_MAINTENANCE => 'pi pi-wrench',
            self::TYPE_SMS => 'pi pi-comments',
            self::TYPE_BUILDING => 'pi pi-building',
            self::TYPE_SYSTEM => 'pi pi-cog',
            default => 'pi pi-bell',
        };
    }
}

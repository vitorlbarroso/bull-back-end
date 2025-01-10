<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembersArea extends Model
{
    use SoftDeletes;
    protected $table = 'members_area';
    protected $fillable = [
        'user_id',
        'area_name',
        'area_type',
        'slug',
        'comments_allow',
        'is_comments_auto_approve',
        'layout_type',
        'is_active',
        'is_blocked',
        'is_deleted',
        'blocked_at',
        'blocked_reason',
    ];

    protected $casts = [
        'deleted_at' => 'timestamp',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class, 'members_area_id');
    }


    public function toArray()
    {
        $array = parent::toArray();
        // Remover campos duplicados
        unset($array['media_id']);
        return $array;
    }

    public function getIsDeletedAttribute($value)
    {
        return (boolean) $value;
    }

    public function getIsActiveAttribute($value)
    {
        return (boolean) $value;
    }

    public function getIsBlockedAttribute($value)
    {
        return (boolean) $value;
    }

    public function modules()
    {
        return $this->hasMany(Modules::class, 'members_area_id');
    }

    protected static function booted()
    {
        static::creating(function ($module) {
            $module->is_active = $module->is_active ?? true;    // Valor padrão para is_active
            $module->is_blocked = $module->is_blocked ?? false;  // Valor padrão para is_blocked
            $module->is_deleted = $module->is_deleted ?? false;  // Valor padrão para is_deleted
        });
    }
}

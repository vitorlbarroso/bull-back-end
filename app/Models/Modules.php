<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Modules extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'module_name',
        'members_area_id',
        'is_active',
        'is_blocked',
        'is_deleted',
        'blocked_at',
        'blocked_reason',
    ];

    protected $casts = [
        'blocked_at' => 'timestamp',
        'is_active' => 'boolean',
    ];

    public function media()
    {
        return $this->hasMany(Media::class, 'modules_id');
    }

    public function toArray()
    {
        $array = parent::toArray();
        unset($array['media_id']);
        unset($array['modules_id']);
        return $array;
    }

    public function members_area()
    {
        return $this->belongsTo(MembersArea::class, 'members_area_id');
    }

    public function getIsDeletedAttribute($value)
    {
        return (boolean) $value;
    }


    public function getIsBlockedAttribute($value)
    {
        return (boolean) $value;
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'modules_id');
    }

    protected static function booted()
    {
        static::creating(function ($module) {
            $module->is_active = $module->is_active ?? false;    // Valor padrão para is_active
            $module->is_blocked = $module->is_blocked ?? false;  // Valor padrão para is_blocked
            $module->is_deleted = $module->is_deleted ?? false;  // Valor padrão para is_deleted
        });
    }
}

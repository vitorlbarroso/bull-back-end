<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lesson_name',
        'description',
        'comments_allow',
        'modules_id',
        'is_active',
        'is_deleted',
        'default_release_lesson',
        'custom_release_date',
    ];

    protected $casts = [
        'custom_release_date' => 'timestamp',
    ];

    public function modules(): BelongsTo
    {
        return $this->belongsTo(Modules::class);
    }

    protected static function booted()
    {
        static::creating(function ($module) {
            $module->is_active = $module->is_active ?? true;    // Valor padrão para is_active
            $module->is_deleted = $module->is_deleted ?? false;  // Valor padrão para is_deleted
        });
    }

}

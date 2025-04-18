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
        'order',
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
        static::creating(function ($lesson) {
            $lesson->is_active = $lesson->is_active ?? true;    // Valor padrão para is_active
            $lesson->is_deleted = $lesson->is_deleted ?? false;  // Valor padrão para is_deleted
            $lastOrder = Lesson::where('modules_id', $lesson->modules_id)->max('order') ?? 0;
            $lesson->order = $lastOrder + 1;
        });
    }

}

<?php

namespace App\Models;

use App\Enums\ProductCategoryEnum;
use App\Enums\ProductTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_name',
        'product_description',
        'product_types_id',
        'product_categories_id',
        'card_description',
        'email_support',
        'whatsapp_support',
        'card_description',
        'media_id',
        'is_blocked',
        'is_active',
        'is_deleted',
        'user_id',
        'refund_time',
        'blocked_reason',
        'deleted_at',
        'blocked_at',
        'id',
    ];

    protected $casts = [

    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(ProductOffering::class, 'product_id');
    }

    public function product_category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_categories_id');
    }

    public function product_type(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_types_id');
    }
    public function toArray()
    {
        $array = parent::toArray();

        // Adicionar as descrições dos tipos e categorias de produtos
        $array['product_type'] = $this->product_type;
        $array['product_category'] = $this->product_category;
        $array['media_id'] = $this->media_id;

        // Remover campos duplicados
        unset($array['product_types_id']);
        unset($array['product_categories_id']);
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
}

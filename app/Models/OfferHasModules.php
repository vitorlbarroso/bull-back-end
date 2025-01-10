<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferHasModules extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_offering_id',
        'modules_id',
        'is_selected',
    ];

    public function productOffering(): BelongsTo
    {
        return $this->belongsTo(ProductOffering::class);
    }

    public function modules(): BelongsTo
    {
        return $this->belongsTo(Modules::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tax;

class ProductTax extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "product_taxes";
    protected $fillable = ['product_id', 'tax_id'];
    protected $casts = [
        'id'         => 'integer',
        'product_id' => 'integer',
        'tax_id'     => 'integer',
    ];

    public function tax(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Retorna o NCM do produto, filtrando apenas registros não deletados
     */
    public static function getNcmByProductId(int $productId): ?string
    {
        $productTax = self::where('product_id', $productId)
                          ->whereNull('deleted_at')
                          ->first();

        return $productTax?->tax?->name ?? null;
    }
}

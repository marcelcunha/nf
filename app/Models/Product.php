<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'supplier_id',
        'code',
        'name',
        'ean',
        'ncm',
        'cfop',
        'unit',
        'last_unit_cost',
        'sale_price',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_unit_cost' => 'decimal:4',
            'sale_price' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'supplier_id');
    }
}

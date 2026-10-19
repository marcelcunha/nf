<?php

namespace App\Models;

use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    protected $table = 'people';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document',
        'name',
        'trade_name',
        'state_registration',
        'email',
        'phone',
        'street',
        'number',
        'complement',
        'district',
        'city',
        'state',
        'zip_code',
    ];

    /**
     * @return BelongsToMany<PersonCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PersonCategory::class, 'person_person_category');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'supplier_id');
    }

    /**
     * @return HasMany<NfeImport, $this>
     */
    public function nfeImports(): HasMany
    {
        return $this->hasMany(NfeImport::class, 'supplier_id');
    }
}

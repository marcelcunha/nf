<?php

namespace App\Models;

use Database\Factories\PersonCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PersonCategory extends Model
{
    /** @use HasFactory<PersonCategoryFactory> */
    use HasFactory;

    public const SUPPLIER_SLUG = 'fornecedor';

    /**
     * @var list<string>
     */
    protected $fillable = ['name', 'slug'];

    /**
     * @return BelongsToMany<Person, $this>
     */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'person_person_category');
    }

    public static function supplier(): self
    {
        return self::firstOrCreate(
            ['slug' => self::SUPPLIER_SLUG],
            ['name' => 'Fornecedor'],
        );
    }
}

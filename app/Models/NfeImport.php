<?php

namespace App\Models;

use Database\Factories\NfeImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfeImport extends Model
{
    /** @use HasFactory<NfeImportFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'access_key',
        'supplier_id',
        'number',
        'series',
        'issued_at',
        'total',
        'items_count',
        'xml',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'total' => 'decimal:2',
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

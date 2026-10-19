<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SefazSyncState extends Model
{
    /**
     * @var array<string, string>
     */
    protected $attributes = ['last_nsu' => '0', 'max_nsu' => '0'];

    /**
     * @var list<string>
     */
    protected $fillable = ['last_nsu', 'max_nsu', 'last_run_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['last_run_at' => 'datetime'];
    }

    public static function current(): self
    {
        return self::firstOrCreate([]);
    }
}

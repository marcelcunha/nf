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
    protected $fillable = ['last_nsu', 'max_nsu', 'last_run_at', 'blocked_until'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['last_run_at' => 'datetime', 'blocked_until' => 'datetime'];
    }

    public function isBlocked(): bool
    {
        return $this->blocked_until?->isFuture() ?? false;
    }

    public static function current(): self
    {
        return self::firstOrCreate([]);
    }
}

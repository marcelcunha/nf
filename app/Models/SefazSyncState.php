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
    protected $fillable = ['last_nsu', 'max_nsu', 'last_run_at', 'blocked_until', 'schedule_times'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['last_run_at' => 'datetime', 'blocked_until' => 'datetime', 'schedule_times' => 'array'];
    }

    public function isBlocked(): bool
    {
        return $this->blocked_until?->isFuture() ?? false;
    }

    /**
     * Horários (HH:MM) definidos pelo usuário, ou os padrões da configuração.
     *
     * @return list<string>
     */
    public function scheduleTimes(): array
    {
        return $this->schedule_times ?? config('sefaz.schedule_times');
    }

    public static function current(): self
    {
        return self::firstOrCreate([]);
    }
}

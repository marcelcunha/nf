<?php

namespace App\Services\Sefaz;

final readonly class DistributionBatch
{
    /**
     * @param  list<array{nsu: string, schema: string, xml: string}>  $documents
     */
    public function __construct(
        public int $statusCode,
        public string $statusMessage,
        public string $lastNsu,
        public string $maxNsu,
        public array $documents,
    ) {}

    public function hasMore(): bool
    {
        return $this->statusCode === 138 && (int) $this->lastNsu < (int) $this->maxNsu;
    }

    public function isEmpty(): bool
    {
        return $this->statusCode === 137;
    }

    public function isSuccessful(): bool
    {
        return in_array($this->statusCode, [137, 138], true);
    }
}

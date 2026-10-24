<?php

namespace App\Jobs;

use App\Models\SefazSyncState;
use App\Services\NfeImporter;
use App\Services\Sefaz\SefazClient;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

class ImportNfesFromSefaz implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Consumo indevido: repetir a consulta só prolonga o bloqueio imposto pela SEFAZ.
     */
    private const CONSUMPTION_ABUSE_STATUS = 656;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(SefazClient $client, NfeImporter $importer): void
    {
        $state = SefazSyncState::current();

        for ($batchCount = 0; $batchCount < config('sefaz.max_batches_per_run'); $batchCount++) {
            $batch = $client->fetchSince($state->last_nsu);

            if (! $batch->isSuccessful()) {
                $exception = new RuntimeException("SEFAZ retornou {$batch->statusCode}: {$batch->statusMessage}");

                if ($batch->statusCode === self::CONSUMPTION_ABUSE_STATUS) {
                    $this->fail($exception);

                    return;
                }

                throw $exception;
            }

            foreach ($batch->documents as $document) {
                if (str_starts_with($document['schema'], 'procNFe')) {
                    $importer->import($document['xml']);
                }
            }

            $state->update([
                'last_nsu' => $batch->lastNsu,
                'max_nsu' => $batch->maxNsu,
                'last_run_at' => now(),
            ]);

            if (! $batch->hasMore()) {
                return;
            }
        }
    }
}

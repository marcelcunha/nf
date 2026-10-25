<?php

namespace App\Console\Commands;

use App\Jobs\ImportNfesFromSefaz;
use App\Models\SefazSyncState;
use Illuminate\Console\Command;

class ImportNfes extends Command
{
    /**
     * @var string
     */
    protected $signature = 'nfe:import';

    /**
     * @var string
     */
    protected $description = 'Importa as NF-es recebidas da SEFAZ (produtos e fornecedores)';

    public function handle(): int
    {
        $state = SefazSyncState::current();

        if ($state->isBlocked()) {
            $this->components->warn("SEFAZ bloqueada até {$state->blocked_until->timezone(config('sefaz.schedule_timezone'))->format('d/m/Y H:i')}. Importação não enviada.");

            return self::SUCCESS;
        }

        ImportNfesFromSefaz::dispatch();

        $this->components->info('Importação de NF-es enviada para a fila.');

        return self::SUCCESS;
    }
}

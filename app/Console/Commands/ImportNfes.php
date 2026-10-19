<?php

namespace App\Console\Commands;

use App\Jobs\ImportNfesFromSefaz;
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
        ImportNfesFromSefaz::dispatch();

        $this->components->info('Importação de NF-es enviada para a fila.');

        return self::SUCCESS;
    }
}

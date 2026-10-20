<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ambiente (razão social e CNPJ vêm do certificado A1 cadastrado em Configurações > Certificado)
    |--------------------------------------------------------------------------
    */
    'state' => env('SEFAZ_STATE', 'SP'),
    'environment' => (int) env('SEFAZ_ENVIRONMENT', 2), // 1 = produção, 2 = homologação

    /*
    |--------------------------------------------------------------------------
    | Importação
    |--------------------------------------------------------------------------
    */
    'schedule_times' => ['06:30', '12:10', '18:40'],
    'schedule_timezone' => env('SEFAZ_SCHEDULE_TIMEZONE', 'America/Sao_Paulo'),

    // Máximo de requisições por execução (cada uma retorna até 50 documentos).
    'max_batches_per_run' => (int) env('SEFAZ_MAX_BATCHES', 20),

];

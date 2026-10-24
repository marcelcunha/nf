<?php

use App\Jobs\ImportNfesFromSefaz;
use App\Models\NfeImport;
use App\Models\SefazSyncState;
use App\Services\Sefaz\DistributionBatch;
use App\Services\Sefaz\SefazClient;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Queue;

test('job imports documents and advances nsu', function () {
    $xml = file_get_contents(base_path('tests/Fixtures/nfe.xml'));

    $this->mock(SefazClient::class)
        ->shouldReceive('fetchSince')->once()->with('0')
        ->andReturn(new DistributionBatch(138, 'ok', '5', '5', [
            ['nsu' => '5', 'schema' => 'procNFe_v4.00.xsd', 'xml' => $xml],
            ['nsu' => '4', 'schema' => 'resNFe_v1.01.xsd', 'xml' => '<resNFe/>'],
        ]));

    app()->call([new ImportNfesFromSefaz, 'handle']);

    expect(NfeImport::count())->toBe(1)
        ->and(SefazSyncState::current()->last_nsu)->toBe('5');
});

test('job fails on sefaz error without advancing nsu', function () {
    $this->mock(SefazClient::class)
        ->shouldReceive('fetchSince')->once()
        ->andReturn(new DistributionBatch(999, 'Erro', '0', '0', []));

    $this->expectException(RuntimeException::class);

    try {
        app()->call([new ImportNfesFromSefaz, 'handle']);
    } finally {
        expect(SefazSyncState::current()->last_nsu)->toBe('0');
    }
});

test('job fails without retries on sefaz status 656', function () {
    $this->mock(SefazClient::class)
        ->shouldReceive('fetchSince')->once()
        ->andReturn(new DistributionBatch(656, 'Consumo Indevido', '0', '0', []));

    $queueJob = Mockery::mock(Job::class);
    $queueJob->shouldReceive('fail')->once()->with(Mockery::type(RuntimeException::class));

    $job = new ImportNfesFromSefaz;
    $job->setJob($queueJob);

    app()->call([$job, 'handle']);

    expect(SefazSyncState::current()->last_nsu)->toBe('0');
});

test('command dispatches job', function () {
    Queue::fake();

    $this->artisan('nfe:import')->assertSuccessful();

    Queue::assertPushed(ImportNfesFromSefaz::class);
});

test('import is scheduled at expected times', function () {
    $expressions = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains($event->command, 'nfe:import'))
        ->map->expression->sort()->values()->all();

    expect($expressions)->toBe(['10 12 * * *', '30 6 * * *', '40 18 * * *']);
});

test('client parses distribution response', function () {
    $doc = base64_encode(gzencode('<nfeProc/>'));
    $response = '<retDistDFeInt xmlns="http://www.portalfiscal.inf.br/nfe"><cStat>138</cStat><xMotivo>ok</xMotivo>'
        .'<ultNSU>000000000000007</ultNSU><maxNSU>000000000000009</maxNSU><loteDistDFeInt>'
        ."<docZip NSU=\"000000000000007\" schema=\"procNFe_v4.00.xsd\">$doc</docZip></loteDistDFeInt></retDistDFeInt>";

    $batch = (new SefazClient)->parseResponse($response);

    expect($batch->statusCode)->toBe(138)
        ->and($batch->hasMore())->toBeTrue()
        ->and($batch->documents[0]['xml'])->toBe('<nfeProc/>');
});

<?php

use App\Jobs\ImportNfesFromSefaz;
use App\Models\NfeImport;
use App\Models\SefazSyncState;
use App\Services\Sefaz\DistributionBatch;
use App\Services\Sefaz\SefazClient;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Carbon;
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

    expect(SefazSyncState::current()->last_nsu)->toBe('0')
        ->and(SefazSyncState::current()->isBlocked())->toBeTrue();
});

test('job does not query sefaz while blocked', function () {
    SefazSyncState::current()->update(['blocked_until' => now()->addMinutes(30)]);

    $this->mock(SefazClient::class)->shouldNotReceive('fetchSince');

    app()->call([new ImportNfesFromSefaz, 'handle']);
});

test('job queries sefaz again after block expires', function () {
    SefazSyncState::current()->update(['blocked_until' => now()->subMinute()]);

    $this->mock(SefazClient::class)
        ->shouldReceive('fetchSince')->once()
        ->andReturn(new DistributionBatch(137, 'Nenhum documento', '0', '0', []));

    app()->call([new ImportNfesFromSefaz, 'handle']);
});

test('command does not dispatch while blocked', function () {
    Queue::fake();
    SefazSyncState::current()->update(['blocked_until' => now()->addMinutes(30)]);

    $this->artisan('nfe:import')->assertSuccessful();

    Queue::assertNothingPushed();
});

test('command dispatches job', function () {
    Queue::fake();

    $this->artisan('nfe:import')->assertSuccessful();

    Queue::assertPushed(ImportNfesFromSefaz::class);
});

test('import runs only at the configured times', function () {
    SefazSyncState::current()->update(['schedule_times' => ['08:15']]);
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'nfe:import'));

    $this->travelTo(Carbon::parse('2026-03-10 08:15', config('sefaz.schedule_timezone')));
    expect($event->filtersPass($this->app))->toBeTrue();

    $this->travelTo(Carbon::parse('2026-03-10 08:16', config('sefaz.schedule_timezone')));
    expect($event->filtersPass($this->app))->toBeFalse();
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

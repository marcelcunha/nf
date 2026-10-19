<?php

namespace Tests\Feature;

use App\Jobs\ImportNfesFromSefaz;
use App\Models\NfeImport;
use App\Models\SefazSyncState;
use App\Services\Sefaz\DistributionBatch;
use App\Services\Sefaz\SefazClient;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class ImportNfesFromSefazTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_imports_documents_and_advances_nsu(): void
    {
        $xml = file_get_contents(base_path('tests/Fixtures/nfe.xml'));

        $this->mock(SefazClient::class)
            ->shouldReceive('fetchSince')->once()->with('0')
            ->andReturn(new DistributionBatch(138, 'ok', '5', '5', [
                ['nsu' => '5', 'schema' => 'procNFe_v4.00.xsd', 'xml' => $xml],
                ['nsu' => '4', 'schema' => 'resNFe_v1.01.xsd', 'xml' => '<resNFe/>'],
            ]));

        app()->call([new ImportNfesFromSefaz, 'handle']);

        $this->assertSame(1, NfeImport::count());
        $this->assertSame('5', SefazSyncState::current()->last_nsu);
    }

    public function test_job_fails_on_sefaz_error_without_advancing_nsu(): void
    {
        $this->mock(SefazClient::class)
            ->shouldReceive('fetchSince')->once()
            ->andReturn(new DistributionBatch(656, 'Consumo Indevido', '0', '0', []));

        $this->expectException(RuntimeException::class);

        try {
            app()->call([new ImportNfesFromSefaz, 'handle']);
        } finally {
            $this->assertSame('0', SefazSyncState::current()->last_nsu);
        }
    }

    public function test_command_dispatches_job(): void
    {
        Queue::fake();

        $this->artisan('nfe:import')->assertSuccessful();

        Queue::assertPushed(ImportNfesFromSefaz::class);
    }

    public function test_import_is_scheduled_at_expected_times(): void
    {
        $expressions = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command, 'nfe:import'))
            ->map->expression->sort()->values()->all();

        $this->assertSame(['10 12 * * *', '30 6 * * *', '40 18 * * *'], $expressions);
    }

    public function test_client_parses_distribution_response(): void
    {
        $doc = base64_encode(gzencode('<nfeProc/>'));
        $response = '<retDistDFeInt xmlns="http://www.portalfiscal.inf.br/nfe"><cStat>138</cStat><xMotivo>ok</xMotivo>'
            .'<ultNSU>000000000000007</ultNSU><maxNSU>000000000000009</maxNSU><loteDistDFeInt>'
            ."<docZip NSU=\"000000000000007\" schema=\"procNFe_v4.00.xsd\">$doc</docZip></loteDistDFeInt></retDistDFeInt>";

        $batch = (new SefazClient)->parseResponse($response);

        $this->assertSame(138, $batch->statusCode);
        $this->assertTrue($batch->hasMore());
        $this->assertSame('<nfeProc/>', $batch->documents[0]['xml']);
    }
}

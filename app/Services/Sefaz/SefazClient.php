<?php

namespace App\Services\Sefaz;

use App\Models\SefazCertificate;
use NFePHP\NFe\Tools;
use RuntimeException;
use SimpleXMLElement;

class SefazClient
{
    /**
     * Consulta o serviço NFeDistribuicaoDFe a partir do último NSU recebido.
     */
    public function fetchSince(string $lastNsu): DistributionBatch
    {
        $response = $this->tools()->sefazDistDFe((int) $lastNsu);

        return $this->parseResponse($response);
    }

    public function parseResponse(string $xml): DistributionBatch
    {
        $root = new SimpleXMLElement($xml);
        $root->registerXPathNamespace('n', 'http://www.portalfiscal.inf.br/nfe');

        $documents = [];
        foreach ($root->xpath('//n:docZip') ?: [] as $doc) {
            $decoded = gzdecode(base64_decode((string) $doc));

            if ($decoded === false) {
                continue;
            }

            $documents[] = [
                'nsu' => (string) $doc['NSU'],
                'schema' => (string) $doc['schema'],
                'xml' => $decoded,
            ];
        }

        return new DistributionBatch(
            statusCode: (int) ($root->xpath('//n:cStat')[0] ?? 0),
            statusMessage: (string) ($root->xpath('//n:xMotivo')[0] ?? ''),
            lastNsu: (string) ($root->xpath('//n:ultNSU')[0] ?? '0'),
            maxNsu: (string) ($root->xpath('//n:maxNSU')[0] ?? '0'),
            documents: $documents,
        );
    }

    private function tools(): Tools
    {
        $certificate = SefazCertificate::current();

        if (! $certificate) {
            throw new RuntimeException('Certificado A1 da SEFAZ não cadastrado. Envie o certificado em Configurações > Certificado.');
        }

        $config = json_encode([
            'atualizacao' => now()->format('Y-m-d H:i:s'),
            'tpAmb' => config('sefaz.environment'),
            'razaosocial' => config('sefaz.company_name'),
            'siglaUF' => config('sefaz.state'),
            'cnpj' => config('sefaz.cnpj'),
            'schemes' => 'PL_009_V4',
            'versao' => '4.00',
        ]);

        return new Tools($config, $certificate->toCertificate());
    }
}

<?php

namespace App\Services;

use SimpleXMLElement;

class NfeXmlReader
{
    /**
     * Extrai do XML da NF-e os dados exibidos na tela de detalhes.
     *
     * @return array{
     *     items: list<array{number: int, code: string, name: string, ncm: ?string, cfop: ?string, unit: ?string, quantity: string, unit_price: string, total: string, unit_cost_with_taxes: ?string, taxes: array{icms: string, icms_st: string, ipi: string, pis: string, cofins: string, import_tax: string, approximate: ?string}}>,
     *     totals: array{products: string, discount: string, freight: string, total: string},
     *     taxes: array{icms: string, icms_st: string, ipi: string, pis: string, cofins: string, import_tax: string, approximate: ?string},
     *     recipient: array{name: ?string, document: ?string},
     *     protocol: ?string,
     *     nature: ?string,
     * }
     */
    public function read(string $xml): array
    {
        $nfe = new SimpleXMLElement($xml);
        $nfe->registerXPathNamespace('n', 'http://www.portalfiscal.inf.br/nfe');

        $infNFe = $nfe->xpath('//n:infNFe')[0] ?? null;

        if ($infNFe === null) {
            return [
                'items' => [],
                'totals' => ['products' => '0', 'discount' => '0', 'freight' => '0', 'total' => '0'],
                'taxes' => ['icms' => '0', 'icms_st' => '0', 'ipi' => '0', 'pis' => '0', 'cofins' => '0', 'import_tax' => '0', 'approximate' => null],
                'recipient' => ['name' => null, 'document' => null],
                'protocol' => null,
                'nature' => null,
            ];
        }

        $items = [];
        foreach ($infNFe->det as $det) {
            $prod = $det->prod;
            $taxes = [
                'icms' => $this->taxValue($det->imposto, 'vICMS'),
                'icms_st' => $this->taxValue($det->imposto, 'vICMSST'),
                'ipi' => $this->taxValue($det->imposto, 'vIPI'),
                'pis' => $this->taxValue($det->imposto, 'vPIS'),
                'cofins' => $this->taxValue($det->imposto, 'vCOFINS'),
                'import_tax' => $this->taxValue($det->imposto, 'vII'),
                'approximate' => $this->nullable($det->imposto->vTotTrib),
            ];
            $items[] = [
                'number' => (int) $det['nItem'],
                'code' => (string) $prod->cProd,
                'name' => (string) $prod->xProd,
                'ncm' => $this->nullable($prod->NCM),
                'cfop' => $this->nullable($prod->CFOP),
                'unit' => $this->nullable($prod->uCom),
                'quantity' => (string) $prod->qCom,
                'unit_price' => (string) $prod->vUnCom,
                'total' => (string) $prod->vProd,
                'unit_cost_with_taxes' => $this->unitCostWithTaxes((string) $prod->vProd, (string) $prod->qCom, $taxes),
                'taxes' => $taxes,
            ];
        }

        $icmsTot = $infNFe->total->ICMSTot;
        $protocol = $nfe->xpath('//n:protNFe/n:infProt/n:nProt')[0] ?? null;

        return [
            'items' => $items,
            'totals' => [
                'products' => (string) ($icmsTot->vProd ?: '0'),
                'discount' => (string) ($icmsTot->vDesc ?: '0'),
                'freight' => (string) ($icmsTot->vFrete ?: '0'),
                'total' => (string) ($icmsTot->vNF ?: '0'),
            ],
            'taxes' => [
                'icms' => (string) ($icmsTot->vICMS ?: '0'),
                'icms_st' => (string) ($icmsTot->vST ?: '0'),
                'ipi' => (string) ($icmsTot->vIPI ?: '0'),
                'pis' => (string) ($icmsTot->vPIS ?: '0'),
                'cofins' => (string) ($icmsTot->vCOFINS ?: '0'),
                'import_tax' => (string) ($icmsTot->vII ?: '0'),
                'approximate' => $this->nullable($icmsTot->vTotTrib),
            ],
            'recipient' => [
                'name' => $this->nullable($infNFe->dest->xNome),
                'document' => $this->nullable($infNFe->dest->CNPJ) ?? $this->nullable($infNFe->dest->CPF),
            ],
            'protocol' => $protocol === null ? null : (string) $protocol,
            'nature' => $this->nullable($infNFe->ide->natOp),
        ];
    }

    /**
     * Custo unitário = (total do item + IPI + ICMS ST + imposto de importação) / quantidade.
     * ICMS, PIS e COFINS já estão embutidos no valor do produto.
     *
     * @param  array{ipi: string, icms_st: string, import_tax: string}  $taxes
     */
    private function unitCostWithTaxes(string $total, string $quantity, array $taxes): ?string
    {
        if (! is_numeric($quantity) || bccomp($quantity, '0', 4) <= 0) {
            return null;
        }

        $sum = bcadd(bcadd(bcadd($total, $taxes['ipi'], 4), $taxes['icms_st'], 4), $taxes['import_tax'], 4);

        return bcdiv($sum, $quantity, 4);
    }

    /**
     * Lê o valor de um tributo do item, independente do grupo de CST/CSOSN em que ele esteja.
     */
    private function taxValue(SimpleXMLElement $imposto, string $tag): string
    {
        $found = $imposto->xpath('.//*[local-name()="'.$tag.'"]');

        return $found ? (string) $found[0] : '0';
    }

    private function nullable(?SimpleXMLElement $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

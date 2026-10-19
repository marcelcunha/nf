<?php

namespace App\Services;

use App\Models\NfeImport;
use App\Models\Person;
use App\Models\PersonCategory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;

class NfeImporter
{
    /**
     * Importa uma NF-e completa (nfeProc). Retorna null se a chave já foi importada.
     */
    public function import(string $xml): ?NfeImport
    {
        $nfe = new SimpleXMLElement($xml);
        $nfe->registerXPathNamespace('n', 'http://www.portalfiscal.inf.br/nfe');

        $infNFe = $nfe->xpath('//n:infNFe')[0] ?? null;

        if ($infNFe === null) {
            return null;
        }

        $accessKey = substr((string) $infNFe['Id'], 3);

        if (NfeImport::where('access_key', $accessKey)->exists()) {
            return null;
        }

        return DB::transaction(function () use ($infNFe, $accessKey, $xml): NfeImport {
            $supplier = $this->syncSupplier($infNFe->emit);

            foreach ($infNFe->det as $item) {
                $this->syncProduct($supplier, $item->prod);
            }

            return NfeImport::create([
                'access_key' => $accessKey,
                'supplier_id' => $supplier->id,
                'number' => (int) $infNFe->ide->nNF,
                'series' => (int) $infNFe->ide->serie,
                'issued_at' => (string) ($infNFe->ide->dhEmi ?: $infNFe->ide->dEmi),
                'total' => (string) $infNFe->total->ICMSTot->vNF,
                'xml' => $xml,
            ]);
        });
    }

    private function syncSupplier(SimpleXMLElement $emit): Person
    {
        $address = $emit->enderEmit;

        $supplier = Person::updateOrCreate(
            ['document' => (string) ($emit->CNPJ ?: $emit->CPF)],
            [
                'name' => (string) $emit->xNome,
                'trade_name' => $this->nullable($emit->xFant),
                'state_registration' => $this->nullable($emit->IE),
                'phone' => $this->nullable($address->fone),
                'street' => $this->nullable($address->xLgr),
                'number' => $this->nullable($address->nro),
                'complement' => $this->nullable($address->xCpl),
                'district' => $this->nullable($address->xBairro),
                'city' => $this->nullable($address->xMun),
                'state' => $this->nullable($address->UF),
                'zip_code' => $this->nullable($address->CEP),
            ],
        );

        $supplier->categories()->syncWithoutDetaching([PersonCategory::supplier()->id]);

        return $supplier;
    }

    private function syncProduct(Person $supplier, SimpleXMLElement $prod): Product
    {
        $ean = $this->nullable($prod->cEAN);

        return Product::updateOrCreate(
            ['supplier_id' => $supplier->id, 'code' => (string) $prod->cProd],
            [
                'name' => (string) $prod->xProd,
                'ean' => $ean === 'SEM GTIN' ? null : $ean,
                'ncm' => $this->nullable($prod->NCM),
                'cfop' => $this->nullable($prod->CFOP),
                'unit' => $this->nullable($prod->uCom),
                'last_unit_cost' => (string) $prod->vUnCom,
            ],
        );
    }

    private function nullable(SimpleXMLElement $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

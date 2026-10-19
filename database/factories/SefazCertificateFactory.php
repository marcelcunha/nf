<?php

namespace Database\Factories;

use App\Models\SefazCertificate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SefazCertificate>
 */
class SefazCertificateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pfx' => base64_encode(self::makePfx('secret')),
            'password' => 'secret',
            'subject' => 'EMPRESA TESTE LTDA',
            'cnpj' => '12345678000199',
            'valid_from' => now()->subMonth(),
            'valid_to' => now()->addYear(),
        ];
    }

    /**
     * Gera um PKCS#12 autoassinado para uso em testes.
     */
    public static function makePfx(string $password, int $validDays = 365): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $csr = openssl_csr_new(['commonName' => 'EMPRESA TESTE LTDA:12345678000199'], $key);
        $x509 = openssl_csr_sign($csr, null, $key, $validDays);

        openssl_pkcs12_export($x509, $pfx, $key, $password);

        return $pfx;
    }
}

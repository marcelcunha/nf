<?php

namespace App\Services\Sefaz;

use Illuminate\Support\Facades\Process;
use NFePHP\Common\Certificate;
use Throwable;

class PfxNormalizer
{
    /**
     * Devolve um PKCS#12 que o OpenSSL 3 abre sem provider legado.
     *
     * Muitas autoridades certificadoras ainda emitem PFX com RC2-40/3DES, que o
     * OpenSSL 3 recusa. Nesses casos o arquivo é reexportado com algoritmos atuais
     * pelo binário openssl, com a senha passada por variável de ambiente.
     *
     * @throws InvalidPfxException
     */
    public function normalize(string $pfx, string $password): string
    {
        if ($this->opens($pfx, $password)) {
            return $pfx;
        }

        $pemPath = tempnam(sys_get_temp_dir(), 'pfx');

        try {
            $env = ['PFX_PASS' => $password];

            $extracted = Process::env($env)->input($pfx)
                ->run('openssl pkcs12 -legacy -passin env:PFX_PASS -passout env:PFX_PASS');

            if (! $extracted->successful()) {
                throw new InvalidPfxException($this->reasonFor($extracted->errorOutput()));
            }

            file_put_contents($pemPath, $extracted->output());

            $exported = Process::env($env)->run(['openssl', 'pkcs12', '-export', '-in', $pemPath, '-passin', 'env:PFX_PASS', '-passout', 'env:PFX_PASS']);
        } finally {
            @unlink($pemPath);
        }

        if (! $exported->successful() || ! $this->opens($exported->output(), $password)) {
            throw new InvalidPfxException($this->reasonFor($exported->errorOutput()));
        }

        return $exported->output();
    }

    private function opens(string $pfx, string $password): bool
    {
        try {
            Certificate::readPfx($pfx, $password);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function reasonFor(string $errorOutput): string
    {
        return match (true) {
            str_contains($errorOutput, 'mac verify failure'),
            str_contains($errorOutput, 'invalid password') => 'Senha do certificado incorreta.',
            str_contains($errorOutput, 'not enough data'),
            str_contains($errorOutput, 'asn1 encoding'),
            str_contains($errorOutput, 'wrong tag') => 'O arquivo não é um certificado PKCS#12 (.pfx/.p12) válido.',
            default => 'Não foi possível abrir o certificado. Verifique o arquivo e a senha.',
        };
    }
}

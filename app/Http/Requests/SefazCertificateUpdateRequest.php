<?php

namespace App\Http\Requests;

use App\Services\Sefaz\InvalidPfxException;
use App\Services\Sefaz\PfxNormalizer;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use NFePHP\Common\Certificate;

class SefazCertificateUpdateRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'certificate' => ['required', 'file', 'max:64'],
            'password' => ['required', 'string', $this->opensWithPassword(...)],
        ];
    }

    /**
     * Garante que o arquivo é um PKCS#12 legível com a senha informada e ainda válido.
     */
    private function opensWithPassword(string $attribute, mixed $password, Closure $fail): void
    {
        $file = $this->file('certificate');

        if (! $file?->isValid()) {
            return;
        }

        try {
            $pfx = app(PfxNormalizer::class)->normalize($file->getContent(), $password);
        } catch (InvalidPfxException $exception) {
            $fail($exception->getMessage());

            return;
        }

        $certificate = Certificate::readPfx($pfx, $password);

        if (Carbon::instance($certificate->getValidTo())->isPast()) {
            $fail('O certificado informado está vencido.');
        }
    }
}

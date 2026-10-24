<?php

namespace App\Models;

use App\Services\Sefaz\PfxNormalizer;
use Database\Factories\SefazCertificateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use NFePHP\Common\Certificate;

class SefazCertificate extends Model
{
    /** @use HasFactory<SefazCertificateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['pfx', 'password', 'subject', 'cnpj', 'valid_from', 'valid_to'];

    /**
     * @var list<string>
     */
    protected $hidden = ['pfx', 'password'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pfx' => 'encrypted',
            'password' => 'encrypted',
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
        ];
    }

    public static function current(): ?self
    {
        return self::query()->first();
    }

    /**
     * Cadastra (ou substitui) o certificado A1 em uso. O arquivo e a senha são
     * gravados criptografados com a APP_KEY. O registro anterior é apagado, não
     * atualizado, para não exigir a descriptografia de um valor gravado com outra chave.
     */
    public static function replaceWith(string $pfxContents, string $password): self
    {
        $pfxContents = app(PfxNormalizer::class)->normalize($pfxContents, $password);
        $certificate = Certificate::readPfx($pfxContents, $password);

        self::query()->delete();

        $record = new self;
        $record->fill([
            'pfx' => base64_encode($pfxContents),
            'password' => $password,
            'subject' => Str::before($certificate->getCompanyName(), ':'),
            'cnpj' => $certificate->getCnpj() ?: self::cnpjFromCommonName($certificate->getCompanyName()),
            'valid_from' => $certificate->getValidFrom(),
            'valid_to' => $certificate->getValidTo(),
        ])->save();

        return $record;
    }

    /**
     * Certificados ICP-Brasil costumam trazer o CNPJ no CN ("RAZAO SOCIAL:00000000000000").
     */
    private static function cnpjFromCommonName(string $commonName): ?string
    {
        return preg_match('/:(\d{14})$/', $commonName, $matches) ? $matches[1] : null;
    }

    public function toCertificate(): Certificate
    {
        return Certificate::readPfx(base64_decode($this->pfx), $this->password);
    }

    public function isExpired(): bool
    {
        return $this->valid_to->isPast();
    }

    public function daysUntilExpiry(): int
    {
        return (int) floor(now()->diffInDays($this->valid_to, false));
    }
}

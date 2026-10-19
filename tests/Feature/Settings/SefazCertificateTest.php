<?php

namespace Tests\Feature\Settings;

use App\Models\SefazCertificate;
use App\Models\User;
use App\Services\Sefaz\SefazClient;
use Database\Factories\SefazCertificateFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class SefazCertificateTest extends TestCase
{
    use RefreshDatabase;

    private function upload(string $pfx, string $password): TestResponse
    {
        return $this->actingAs(User::factory()->create())
            ->from('/settings/certificate')
            ->post('/settings/certificate', [
                'certificate' => UploadedFile::fake()->createWithContent('cert.pfx', $pfx),
                'password' => $password,
            ]);
    }

    public function test_certificate_is_stored_encrypted(): void
    {
        $pfx = SefazCertificateFactory::makePfx('s3nha-forte');

        $this->upload($pfx, 's3nha-forte')->assertSessionHasNoErrors();

        $certificate = SefazCertificate::current();
        $this->assertSame('EMPRESA TESTE LTDA', $certificate->subject);
        $this->assertSame('12345678000199', $certificate->cnpj);

        $raw = DB::table('sefaz_certificates')->first();
        $this->assertStringNotContainsString('s3nha-forte', $raw->password);
        $this->assertStringNotContainsString(base64_encode($pfx), $raw->pfx);
        $this->assertSame('s3nha-forte', $certificate->password);
        $this->assertSame($pfx, base64_decode($certificate->pfx));
    }

    public function test_uploading_again_replaces_the_certificate(): void
    {
        $this->upload(SefazCertificateFactory::makePfx('a'), 'a');
        $this->upload(SefazCertificateFactory::makePfx('b'), 'b');

        $this->assertSame(1, SefazCertificate::count());
        $this->assertSame('b', SefazCertificate::current()->password);
    }

    public function test_legacy_pfx_is_converted_and_stored(): void
    {
        $legacy = file_get_contents(base_path('tests/Fixtures/legacy.pfx'));
        $this->assertFalse(openssl_pkcs12_read($legacy, $unused, 'legacy'));

        $this->upload($legacy, 'legacy')->assertSessionHasNoErrors();

        $certificate = SefazCertificate::current();
        $this->assertSame('12345678000199', $certificate->cnpj);
        $this->assertNotSame($legacy, base64_decode($certificate->pfx));
        $this->assertNotNull($certificate->toCertificate());
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->upload(SefazCertificateFactory::makePfx('correta'), 'errada')
            ->assertSessionHasErrors(['password' => 'Senha do certificado incorreta.']);

        $this->assertSame(0, SefazCertificate::count());
    }

    public function test_non_pfx_file_is_rejected(): void
    {
        $this->upload('isto não é um certificado', 'x')
            ->assertSessionHasErrors(['password' => 'O arquivo não é um certificado PKCS#12 (.pfx/.p12) válido.']);
    }

    public function test_expired_certificate_is_rejected(): void
    {
        $pfx = SefazCertificateFactory::makePfx('x', 1);
        $this->travel(3)->days();

        $this->upload($pfx, 'x')
            ->assertSessionHasErrors('password');

        $this->assertSame(0, SefazCertificate::count());
    }

    public function test_settings_page_never_exposes_pfx_or_password(): void
    {
        SefazCertificate::factory()->create();

        $response = $this->actingAs(User::factory()->create())->get('/settings/certificate');

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('settings/certificate')
            ->where('certificate.subject', 'EMPRESA TESTE LTDA')
            ->missing('certificate.pfx')
            ->missing('certificate.password'));
        $this->assertStringNotContainsString('secret', $response->getContent());
    }

    public function test_guests_cannot_upload(): void
    {
        $this->post('/settings/certificate')->assertRedirect('/login');
    }

    public function test_client_requires_a_registered_certificate(): void
    {
        $this->expectException(RuntimeException::class);

        app(SefazClient::class)->fetchSince('0');
    }
}

<?php

use App\Models\SefazCertificate;
use App\Models\User;
use App\Services\Sefaz\SefazClient;
use Database\Factories\SefazCertificateFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

function uploadCertificate(string $pfx, string $password): TestResponse
{
    return test()->actingAs(User::factory()->create())
        ->from('/certificado')
        ->post('/certificado', [
            'certificate' => UploadedFile::fake()->createWithContent('cert.pfx', $pfx),
            'password' => $password,
        ]);
}

test('certificate is stored encrypted', function () {
    $pfx = SefazCertificateFactory::makePfx('s3nha-forte');

    uploadCertificate($pfx, 's3nha-forte')->assertSessionHasNoErrors();

    $certificate = SefazCertificate::current();
    expect($certificate->subject)->toBe('EMPRESA TESTE LTDA')
        ->and($certificate->cnpj)->toBe('12345678000199');

    $raw = DB::table('sefaz_certificates')->first();
    expect($raw->password)->not->toContain('s3nha-forte')
        ->and($raw->pfx)->not->toContain(base64_encode($pfx))
        ->and($certificate->password)->toBe('s3nha-forte')
        ->and(base64_decode($certificate->pfx))->toBe($pfx);
});

test('uploading again replaces the certificate', function () {
    uploadCertificate(SefazCertificateFactory::makePfx('a'), 'a');
    uploadCertificate(SefazCertificateFactory::makePfx('b'), 'b');

    expect(SefazCertificate::count())->toBe(1)
        ->and(SefazCertificate::current()->password)->toBe('b');
});

test('legacy pfx is converted and stored', function () {
    $legacy = file_get_contents(base_path('tests/Fixtures/legacy.pfx'));
    expect(openssl_pkcs12_read($legacy, $unused, 'legacy'))->toBeFalse();

    uploadCertificate($legacy, 'legacy')->assertSessionHasNoErrors();

    $certificate = SefazCertificate::current();
    expect($certificate->cnpj)->toBe('12345678000199')
        ->and(base64_decode($certificate->pfx))->not->toBe($legacy)
        ->and($certificate->toCertificate())->not->toBeNull();
});

test('wrong password is rejected', function () {
    uploadCertificate(SefazCertificateFactory::makePfx('correta'), 'errada')
        ->assertSessionHasErrors(['password' => 'Senha do certificado incorreta.']);

    expect(SefazCertificate::count())->toBe(0);
});

test('non pfx file is rejected', function () {
    uploadCertificate('isto não é um certificado', 'x')
        ->assertSessionHasErrors(['password' => 'O arquivo não é um certificado PKCS#12 (.pfx/.p12) válido.']);
});

test('expired certificate is rejected', function () {
    $pfx = SefazCertificateFactory::makePfx('x', 1);
    $this->travel(3)->days();

    uploadCertificate($pfx, 'x')->assertSessionHasErrors('password');

    expect(SefazCertificate::count())->toBe(0);
});

test('settings page never exposes pfx or password', function () {
    SefazCertificate::factory()->create();

    $response = $this->actingAs(User::factory()->create())->get('/certificado');

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('certificate')
        ->where('certificate.subject', 'EMPRESA TESTE LTDA')
        ->missing('certificate.pfx')
        ->missing('certificate.password'));
    expect($response->getContent())->not->toContain('secret');
});

test('guests cannot upload', function () {
    $this->post('/certificado')->assertRedirect('/login');
});

test('client requires a registered certificate', function () {
    $this->expectException(RuntimeException::class);

    app(SefazClient::class)->fetchSince('0');
});

test('client uses company data from the certificate', function () {
    SefazCertificate::factory()->create();

    $tools = app(SefazClient::class)->tools();

    expect($tools->config->cnpj)->toBe('12345678000199')
        ->and($tools->config->razaosocial)->toBe('EMPRESA TESTE LTDA');
});

test('client rejects certificate without cnpj', function () {
    SefazCertificate::factory()->create(['cnpj' => null]);

    $this->expectExceptionMessage('não possui CNPJ');

    app(SefazClient::class)->tools();
});

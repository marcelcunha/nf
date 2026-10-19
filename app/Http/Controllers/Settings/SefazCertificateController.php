<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SefazCertificateUpdateRequest;
use App\Models\SefazCertificate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SefazCertificateController extends Controller
{
    public function edit(): Response
    {
        $certificate = SefazCertificate::current();

        return Inertia::render('settings/certificate', [
            'certificate' => $certificate ? [
                'subject' => $certificate->subject,
                'cnpj' => $certificate->cnpj,
                'valid_from' => $certificate->valid_from->toIso8601String(),
                'valid_to' => $certificate->valid_to->toIso8601String(),
                'is_expired' => $certificate->isExpired(),
                'days_until_expiry' => $certificate->daysUntilExpiry(),
            ] : null,
        ]);
    }

    public function update(SefazCertificateUpdateRequest $request): RedirectResponse
    {
        SefazCertificate::replaceWith(
            $request->file('certificate')->getContent(),
            $request->validated('password'),
        );

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\SefazCertificateUpdateRequest;
use App\Models\SefazCertificate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SefazCertificateController extends Controller
{
    public function edit(): Response
    {
        $certificate = SefazCertificate::current();

        return Inertia::render('certificate', [
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

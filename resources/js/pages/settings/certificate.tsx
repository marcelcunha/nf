import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import HeadingSmall from '@/components/heading-small';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Certificado digital',
        href: '/settings/certificate',
    },
];

interface CertificateInfo {
    subject: string;
    cnpj: string | null;
    valid_from: string;
    valid_to: string;
    is_expired: boolean;
    days_until_expiry: number;
}

const formatDate = (iso: string) => new Date(iso).toLocaleDateString('pt-BR');

export default function Certificate({ certificate }: { certificate: CertificateInfo | null }) {
    const { setData, errors, post, reset, processing, recentlySuccessful } = useForm<{ certificate: File | null; password: string }>({
        certificate: null,
        password: '',
    });

    const [showPassword, setShowPassword] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('certificate.update'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => reset(),
            onError: () => reset('password'),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Certificado digital" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Certificado digital A1"
                        description="Usado para consultar as NF-es na SEFAZ. O arquivo e a senha são armazenados criptografados."
                    />

                    {certificate && (
                        <Alert variant={certificate.is_expired || certificate.days_until_expiry <= 30 ? 'destructive' : 'default'}>
                            <AlertDescription>
                                <p className="font-medium">{certificate.subject}</p>
                                {certificate.cnpj && <p>CNPJ: {certificate.cnpj}</p>}
                                <p>
                                    Válido de {formatDate(certificate.valid_from)} até {formatDate(certificate.valid_to)}
                                    {certificate.is_expired
                                        ? ' (vencido)'
                                        : certificate.days_until_expiry <= 30 && ` (vence em ${certificate.days_until_expiry} dias)`}
                                </p>
                            </AlertDescription>
                        </Alert>
                    )}

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="certificate">Arquivo do certificado (.pfx / .p12)</Label>

                            <Input
                                id="certificate"
                                type="file"
                                accept=".pfx,.p12"
                                onChange={(e) => setData('certificate', e.target.files?.[0] ?? null)}
                            />

                            <InputError message={errors.certificate} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Senha do certificado</Label>

                            <div className="relative">
                                <Input
                                    id="password"
                                    type={showPassword ? 'text' : 'password'}
                                    autoComplete="off"
                                    className="pr-10"
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Senha"
                                />

                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="absolute inset-y-0 right-0 text-muted-foreground"
                                    onClick={() => setShowPassword((visible) => !visible)}
                                    aria-label={showPassword ? 'Ocultar senha' : 'Mostrar senha'}
                                >
                                    {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                                </Button>
                            </div>

                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>{certificate ? 'Substituir certificado' : 'Enviar certificado'}</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Salvo</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}

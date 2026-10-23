import { NfeSummary, decimal, money, taxEntries, type Taxes } from '@/components/nfe-summary';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Fragment } from 'react';

interface Item {
    number: number;
    code: string;
    name: string;
    ncm: string | null;
    cfop: string | null;
    unit: string | null;
    quantity: string;
    unit_price: string;
    total: string;
    taxes: Taxes;
}

interface Props {
    nfe: { id: number; access_key: string; number: number; series: number; issued_at: string; total: string };
    supplier: { id: number; name: string; document: string; city: string | null; state: string | null };
    details: {
        items: Item[];
        totals: { products: string; discount: string; freight: string; total: string };
        taxes: Taxes;
        recipient: { name: string | null; document: string | null };
        protocol: string | null;
        nature: string | null;
    };
}

export default function NfeImportsShow({ nfe, supplier, details }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'NF-es importadas', href: '/nfe-importadas' },
        { title: `NF-e ${nfe.number}/${nfe.series}`, href: `/nfe-importadas/${nfe.id}` },
    ];

    const { taxes } = details;
    const taxRows = taxEntries(taxes);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`NF-e ${nfe.number}/${nfe.series}`} />
            <div className="flex flex-col gap-5 p-4 md:px-8 md:py-7">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-xl font-semibold">
                        NF-e {nfe.number}/{nfe.series}
                    </h1>
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href={`/nfe-importadas/${nfe.id}/precificar`}>Precificar</Link>
                        </Button>
                        <Button asChild>
                            <a href={`/nfe-importadas/${nfe.id}/danfe`} target="_blank" rel="noreferrer">
                                Gerar DANFE
                            </a>
                        </Button>
                    </div>
                </div>

                <NfeSummary nfe={nfe} supplier={supplier} details={details} />

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">#</th>
                                <th className="p-3">Código</th>
                                <th className="p-3">Produto</th>
                                <th className="p-3">NCM</th>
                                <th className="p-3">CFOP</th>
                                <th className="p-3 text-right">Qtd.</th>
                                <th className="p-3">Un.</th>
                                <th className="p-3 text-right">Valor unit.</th>
                                <th className="p-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {details.items.map((item) => (
                                <Fragment key={item.number}>
                                    <tr className="border-t">
                                        <td className="p-3">{item.number}</td>
                                        <td className="p-3 font-mono text-xs">{item.code}</td>
                                        <td className="p-3">{item.name}</td>
                                        <td className="p-3">{item.ncm ?? '—'}</td>
                                        <td className="p-3">{item.cfop ?? '—'}</td>
                                        <td className="p-3 text-right">{decimal(item.quantity)}</td>
                                        <td className="p-3">{item.unit ?? '—'}</td>
                                        <td className="p-3 text-right">{money(item.unit_price)}</td>
                                        <td className="p-3 text-right">{money(item.total)}</td>
                                    </tr>
                                    <tr>
                                        <td colSpan={9} className="text-muted-foreground px-3 pb-3 text-xs">
                                            Tributos:{' '}
                                            {taxEntries(item.taxes)
                                                .map(([label, value]) => `${label} ${money(value)}`)
                                                .join(' · ')}
                                            {item.taxes.approximate !== null && ` · Aprox. ${money(item.taxes.approximate)}`}
                                        </td>
                                    </tr>
                                </Fragment>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex flex-col gap-4 sm:flex-row sm:justify-between">
                    <div className="w-full max-w-xs">
                        <h2 className="mb-2 text-sm font-semibold">Impostos</h2>
                        <dl className="grid grid-cols-2 gap-1 text-sm">
                            {taxRows.map(([label, value]) => (
                                <Fragment key={label}>
                                    <dt className="text-muted-foreground">{label}</dt>
                                    <dd className="text-right">{money(value)}</dd>
                                </Fragment>
                            ))}
                            {details.taxes.approximate !== null && (
                                <>
                                    <dt className="text-muted-foreground">Total aprox. de tributos</dt>
                                    <dd className="text-right">{money(details.taxes.approximate)}</dd>
                                </>
                            )}
                        </dl>
                    </div>
                    <dl className="ml-auto grid w-full max-w-xs grid-cols-2 gap-1 text-sm">
                        <dt className="text-muted-foreground">Produtos</dt>
                        <dd className="text-right">{money(details.totals.products)}</dd>
                        <dt className="text-muted-foreground">Desconto</dt>
                        <dd className="text-right">{money(details.totals.discount)}</dd>
                        <dt className="text-muted-foreground">Frete</dt>
                        <dd className="text-right">{money(details.totals.freight)}</dd>
                        <dt className="font-semibold">Total da nota</dt>
                        <dd className="text-right font-semibold">{money(details.totals.total)}</dd>
                    </dl>
                </div>
            </div>
        </AppLayout>
    );
}

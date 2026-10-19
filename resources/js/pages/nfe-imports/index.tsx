import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

interface NfeImport {
    id: number;
    access_key: string;
    number: number;
    series: number;
    issued_at: string;
    total: string;
    supplier: { id: number; name: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'NF-es importadas', href: '/nfe-imports' }];

export default function NfeImportsIndex({
    imports,
    lastRunAt,
    scheduleTimes,
}: {
    imports: Paginated<NfeImport>;
    lastRunAt: string | null;
    scheduleTimes: string[];
}) {
    const [processing, setProcessing] = useState(false);
    const [queued, setQueued] = useState(false);

    const importNow = () =>
        router.post(
            '/nfe-imports',
            {},
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onSuccess: () => setQueued(true),
                onFinish: () => setProcessing(false),
            },
        );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="NF-es importadas" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <p className="text-muted-foreground text-sm">
                        Importação automática às {scheduleTimes.join(', ')}. Última execução:{' '}
                        {lastRunAt ? new Date(lastRunAt).toLocaleString('pt-BR') : 'nunca'}.
                    </p>
                    <div className="flex items-center gap-3">
                        {queued && <span className="text-sm text-green-600">Importação enviada para a fila.</span>}
                        <Button onClick={importNow} disabled={processing}>
                            Importar agora
                        </Button>
                    </div>
                </div>
                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">Emissão</th>
                                <th className="p-3">Número/Série</th>
                                <th className="p-3">Fornecedor</th>
                                <th className="p-3">Chave de acesso</th>
                                <th className="p-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {imports.data.map((item) => (
                                <tr key={item.id} className="border-t">
                                    <td className="p-3">{new Date(item.issued_at).toLocaleDateString('pt-BR')}</td>
                                    <td className="p-3">
                                        {item.number}/{item.series}
                                    </td>
                                    <td className="p-3">{item.supplier.name}</td>
                                    <td className="p-3 font-mono text-xs">{item.access_key}</td>
                                    <td className="p-3 text-right">{Number(item.total).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</td>
                                </tr>
                            ))}
                            {imports.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="text-muted-foreground p-6 text-center">
                                        Nenhuma NF-e importada ainda.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <Pagination paginator={imports} />
            </div>
        </AppLayout>
    );
}

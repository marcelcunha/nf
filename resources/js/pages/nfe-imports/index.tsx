import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface NfeImport {
    id: number;
    access_key: string;
    number: number;
    series: number;
    issued_at: string;
    total: string;
    items_count: number;
    supplier: { id: number; name: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'NF-es importadas', href: '/nfe-imports' }];

interface Filters {
    access_key: string;
    number: string;
    from: string;
    to: string;
}

export default function NfeImportsIndex({
    imports,
    filters,
    lastRunAt,
    scheduleTimes,
}: {
    imports: Paginated<NfeImport>;
    filters: Filters;
    lastRunAt: string | null;
    scheduleTimes: string[];
}) {
    const [processing, setProcessing] = useState(false);
    const [queued, setQueued] = useState(false);
    const [form, setForm] = useState<Filters>(filters);

    const applyFilters = (values: Filters) =>
        router.get('/nfe-imports', Object.fromEntries(Object.entries(values).filter(([, value]) => value !== '')), { preserveState: true });

    const clearFilters = () => {
        const empty = { access_key: '', number: '', from: '', to: '' };
        setForm(empty);
        applyFilters(empty);
    };

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
                <div className="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h1 className="text-2xl font-bold">NF-es importadas</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Importação automática às {scheduleTimes.join(', ')}. Última execução:{' '}
                            {lastRunAt ? new Date(lastRunAt).toLocaleString('pt-BR') : 'nunca'}.
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        {queued && <span className="text-sm text-green-600">Importação enviada para a fila.</span>}
                        <Button onClick={importNow} disabled={processing}>
                            Importar agora
                        </Button>
                    </div>
                </div>
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start">
                    <div className="flex min-w-0 flex-1 flex-col gap-4">
                        <div className="bg-card overflow-x-auto rounded-xl border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted text-muted-foreground text-left">
                                    <tr>
                                        <th className="p-3">Emissão</th>
                                        <th className="p-3">Número/Série</th>
                                        <th className="p-3">Fornecedor</th>
                                        <th className="p-3 text-right">Itens</th>
                                        <th className="p-3 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {imports.data.map((item) => (
                                        <tr
                                            key={item.id}
                                            className="hover:bg-muted/50 cursor-pointer border-t"
                                            onClick={() => router.visit(`/nfe-imports/${item.id}`)}
                                        >
                                            <td className="p-3">{new Date(item.issued_at).toLocaleDateString('pt-BR')}</td>
                                            <td className="p-3">
                                                <Link
                                                    href={`/nfe-imports/${item.id}`}
                                                    className="text-primary font-semibold underline-offset-4 hover:underline dark:text-teal-300"
                                                >
                                                    {item.number}/{item.series}
                                                </Link>
                                            </td>
                                            <td className="p-3">
                                                {item.supplier.name}
                                                <div className="text-muted-foreground font-mono text-xs" title={item.access_key}>
                                                    {item.access_key.slice(0, 8)}…{item.access_key.slice(-4)}
                                                </div>
                                            </td>
                                            <td className="p-3 text-right">{item.items_count}</td>
                                            <td className="p-3 text-right">
                                                {Number(item.total).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                                            </td>
                                        </tr>
                                    ))}
                                    {imports.data.length === 0 && (
                                        <tr>
                                            <td colSpan={5} className="text-muted-foreground p-6 text-center">
                                                Nenhuma NF-e encontrada.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <Pagination paginator={imports} />
                    </div>
                    <form
                        className="bg-card order-first flex shrink-0 flex-col gap-3 rounded-xl border p-4 lg:order-last lg:w-80"
                        onSubmit={(event) => {
                            event.preventDefault();
                            applyFilters(form);
                        }}
                    >
                        <h2 className="font-semibold">Filtros</h2>
                        <div className="grid gap-1">
                            <Label htmlFor="access_key">Chave de acesso</Label>
                            <Input
                                id="access_key"
                                value={form.access_key}
                                onChange={(event) => setForm({ ...form, access_key: event.target.value })}
                                maxLength={44}
                                className="font-mono"
                            />
                        </div>
                        <div className="grid gap-1">
                            <Label htmlFor="number">Número</Label>
                            <Input
                                id="number"
                                type="number"
                                min={0}
                                value={form.number}
                                onChange={(event) => setForm({ ...form, number: event.target.value })}
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div className="grid gap-1">
                                <Label htmlFor="from">Emissão de</Label>
                                <Input id="from" type="date" value={form.from} onChange={(event) => setForm({ ...form, from: event.target.value })} />
                            </div>
                            <div className="grid gap-1">
                                <Label htmlFor="to">até</Label>
                                <Input id="to" type="date" value={form.to} onChange={(event) => setForm({ ...form, to: event.target.value })} />
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit">Filtrar</Button>
                            <Button type="button" variant="outline" onClick={clearFilters}>
                                Limpar
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}

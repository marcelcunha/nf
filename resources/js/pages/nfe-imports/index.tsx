import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronDown, Filter } from 'lucide-react';
import { useState } from 'react';

interface NfeImport {
    id: number;
    access_key: string;
    number: number;
    series: number;
    issued_at: string;
    total: string;
    items_count: number;
    price_table_published_at: string | null;
    supplier: { id: number; name: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'NF-es importadas', href: '/nfe-importadas' }];

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
    const [filtersOpen, setFiltersOpen] = useState(false);
    const hasActiveFilters = Object.values(filters).some((value) => value !== '');

    const applyFilters = (values: Filters) =>
        router.get('/nfe-importadas', Object.fromEntries(Object.entries(values).filter(([, value]) => value !== '')), { preserveState: true });

    const clearFilters = () => {
        const empty = { access_key: '', number: '', from: '', to: '' };
        setForm(empty);
        applyFilters(empty);
    };

    const importNow = () =>
        router.post(
            '/nfe-importadas',
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
            <div className="flex flex-col gap-5 p-4 md:px-8 md:py-7">
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
                        <div className="bg-card overflow-hidden rounded-xl border">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted text-muted-foreground text-left">
                                        <tr>
                                            <th className="p-3">Emissão</th>
                                            <th className="p-3">Número/Série</th>
                                            <th className="p-3">Fornecedor</th>
                                            <th className="p-3 text-right">Itens</th>
                                            <th className="p-3">Tabela de preço</th>
                                            <th className="p-3 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {imports.data.map((item) => (
                                            <tr
                                                key={item.id}
                                                className="hover:bg-muted/50 cursor-pointer border-t"
                                                onClick={() => router.visit(`/nfe-importadas/${item.id}`)}
                                            >
                                                <td className="p-3">{new Date(item.issued_at).toLocaleDateString('pt-BR')}</td>
                                                <td className="p-3">
                                                    <Link
                                                        href={`/nfe-importadas/${item.id}`}
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
                                                <td className="p-3">
                                                    {item.price_table_published_at ? (
                                                        <span className="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800 dark:bg-green-950 dark:text-green-300">
                                                            Publicada
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted-foreground">—</span>
                                                    )}
                                                </td>
                                                <td className="p-3 text-right">
                                                    {Number(item.total).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                                                </td>
                                            </tr>
                                        ))}
                                        {imports.data.length === 0 && (
                                            <tr>
                                                <td colSpan={6} className="text-muted-foreground p-6 text-center">
                                                    Nenhuma NF-e encontrada.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                            <Pagination paginator={imports} />
                        </div>
                    </div>
                    <div className="bg-card order-first overflow-hidden rounded-xl border lg:order-last lg:w-80 lg:shrink-0">
                        <button
                            type="button"
                            className="flex w-full items-center gap-2 p-4 text-left font-semibold lg:hidden"
                            aria-expanded={filtersOpen}
                            aria-controls="nfe-filters"
                            onClick={() => setFiltersOpen((open) => !open)}
                        >
                            <Filter className="size-4" aria-hidden="true" />
                            Filtros
                            {hasActiveFilters && <span className="bg-primary size-2 rounded-full" aria-label="Filtros aplicados" />}
                            <ChevronDown className={`ml-auto size-4 transition-transform ${filtersOpen ? 'rotate-180' : ''}`} aria-hidden="true" />
                        </button>
                        <div
                            className={`grid transition-[grid-template-rows] duration-200 ease-out motion-reduce:transition-none lg:grid-rows-[1fr] ${filtersOpen ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'}`}
                        >
                            <div className="min-h-0 overflow-hidden">
                                <form
                                    id="nfe-filters"
                                    className={`flex flex-col gap-3 border-t p-4 transition-[visibility] duration-200 lg:border-t-0 ${filtersOpen ? 'visible' : 'invisible lg:visible'}`}
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        applyFilters(form);
                                    }}
                                >
                                    <h2 className="hidden font-semibold lg:block">Filtros</h2>
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
                                            <Input
                                                id="from"
                                                type="date"
                                                value={form.from}
                                                onChange={(event) => setForm({ ...form, from: event.target.value })}
                                            />
                                        </div>
                                        <div className="grid gap-1">
                                            <Label htmlFor="to">até</Label>
                                            <Input
                                                id="to"
                                                type="date"
                                                value={form.to}
                                                onChange={(event) => setForm({ ...form, to: event.target.value })}
                                            />
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
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

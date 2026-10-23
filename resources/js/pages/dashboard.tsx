import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface Kpis {
    count: { value: number; change: number | null };
    total: { value: string; change: number | null };
    suppliers: { active: number; new: number };
    products: { without_price: number; total: number };
}

interface RecentImport {
    id: number;
    issued_at: string;
    number: number;
    series: number;
    supplier: string;
    total: string;
}

interface Props {
    period: 'month' | '3m' | '12m';
    kpis: Kpis;
    months: { month: string; label: string; total: string }[];
    suppliers: { name: string; total: string }[];
    recent: RecentImport[];
    sync: { last_run_at: string | null; next_run_at: string | null; last_nsu: string; max_nsu: string; up_to_date: boolean };
    certificate: { valid_to: string; days_left: number; expired: boolean } | null;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const periods = [
    { value: 'month', label: 'Este mês', previous: 'o mês anterior' },
    { value: '3m', label: '3 meses', previous: 'os 3 meses anteriores' },
    { value: '12m', label: '12 meses', previous: 'os 12 meses anteriores' },
] as const;

const currency = (value: string | number, fractionDigits = 2) =>
    Number(value).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
    });

const dateTime = (value: string | null) => (value ? new Date(value).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : 'nunca');

function Change({ value, previous }: { value: number | null; previous: string }) {
    if (value === null) {
        return <p className="text-muted-foreground text-xs">Sem dados em {previous}</p>;
    }

    const positive = value >= 0;

    return (
        <p className={`text-xs font-medium ${positive ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'}`}>
            {positive ? '▲' : '▼'} {Math.abs(value).toLocaleString('pt-BR')}% vs. {previous}
        </p>
    );
}

function Panel({ title, action, children }: { title: string; action?: React.ReactNode; children: React.ReactNode }) {
    return (
        <section className="bg-card min-w-0 rounded-xl border p-5">
            <div className="mb-4 flex items-center justify-between gap-2">
                <h2 className="font-semibold">{title}</h2>
                {action}
            </div>
            {children}
        </section>
    );
}

export default function Dashboard({ period, kpis, months, suppliers, recent, sync, certificate }: Props) {
    const current = periods.find((item) => item.value === period) ?? periods[0];
    const maxMonth = Math.max(...months.map((month) => Number(month.total)), 1);
    const maxSupplier = Math.max(...suppliers.map((supplier) => Number(supplier.total)), 1);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-5 p-4 md:px-8 md:py-7">
                <div className="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h1 className="text-2xl font-bold">Dashboard</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Resumo das compras importadas.</p>
                    </div>
                    <div className="bg-muted flex gap-1 rounded-lg p-1" role="group" aria-label="Período">
                        {periods.map((item) => (
                            <button
                                key={item.value}
                                type="button"
                                aria-pressed={item.value === period}
                                onClick={() => router.get('/dashboard', { period: item.value }, { preserveScroll: true, preserveState: true })}
                                className={`rounded-md px-3 py-1.5 text-sm ${item.value === period ? 'bg-background font-semibold shadow-xs' : 'text-muted-foreground'}`}
                            >
                                {item.label}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div className="bg-card rounded-xl border p-5">
                        <p className="text-muted-foreground text-sm">NF-es no período</p>
                        <p className="mt-1 text-2xl font-bold">{kpis.count.value}</p>
                        <Change value={kpis.count.change} previous={current.previous} />
                    </div>
                    <div className="bg-card rounded-xl border p-5">
                        <p className="text-muted-foreground text-sm">Valor comprado</p>
                        <p className="mt-1 text-2xl font-bold">{currency(kpis.total.value)}</p>
                        <Change value={kpis.total.change} previous={current.previous} />
                    </div>
                    <div className="bg-card rounded-xl border p-5">
                        <p className="text-muted-foreground text-sm">Fornecedores ativos</p>
                        <p className="mt-1 text-2xl font-bold">{kpis.suppliers.active}</p>
                        <p className="text-muted-foreground text-xs">{kpis.suppliers.new} novos no período</p>
                    </div>
                    <div className="bg-card rounded-xl border p-5">
                        <p className="text-muted-foreground text-sm">Produtos sem preço de venda</p>
                        <p className="mt-1 text-2xl font-bold">{kpis.products.without_price}</p>
                        <p className="text-muted-foreground text-xs">de {kpis.products.total} cadastrados</p>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <Panel title="Compras por mês">
                            <div className="flex h-48 items-end gap-2 border-b">
                                {months.map((month) => (
                                    <div key={month.month} className="flex h-full flex-1 flex-col items-center justify-end gap-1">
                                        <span className="text-muted-foreground text-[11px]">{Math.round(Number(month.total) / 1000)}</span>
                                        <div
                                            className="bg-primary/70 hover:bg-primary w-full rounded-t-md"
                                            style={{ height: `${(Number(month.total) / maxMonth) * 80}%` }}
                                            title={`${month.label}: ${currency(month.total)}`}
                                        />
                                    </div>
                                ))}
                            </div>
                            <div className="mt-1 flex gap-2">
                                {months.map((month) => (
                                    <span key={month.month} className="text-muted-foreground flex-1 text-center text-[11px] capitalize">
                                        {month.label}
                                    </span>
                                ))}
                            </div>
                            <p className="text-muted-foreground mt-2 text-xs">Valores em R$ mil.</p>
                        </Panel>
                    </div>

                    <Panel title="Sincronização SEFAZ">
                        <div className="flex flex-col gap-3 text-sm">
                            <p
                                className={`font-semibold ${sync.up_to_date ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400'}`}
                            >
                                {sync.up_to_date ? 'Em dia' : 'Há notas pendentes de importação'}
                            </p>
                            <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2">
                                <dt className="text-muted-foreground">Última execução</dt>
                                <dd className="text-right font-medium">{dateTime(sync.last_run_at)}</dd>
                                <dt className="text-muted-foreground">Próxima</dt>
                                <dd className="text-right font-medium">{dateTime(sync.next_run_at)}</dd>
                                <dt className="text-muted-foreground">NSU atual / máximo</dt>
                                <dd className="text-right font-medium">
                                    {sync.last_nsu} / {sync.max_nsu}
                                </dd>
                            </dl>
                            {certificate === null ? (
                                <Link
                                    href="/settings/certificate"
                                    className="rounded-lg bg-amber-100 p-3 text-amber-900 dark:bg-amber-950 dark:text-amber-200"
                                >
                                    <b>Nenhum certificado A1 cadastrado.</b> Cadastre para importar notas.
                                </Link>
                            ) : (
                                (certificate.expired || certificate.days_left <= 60) && (
                                    <Link
                                        href="/settings/certificate"
                                        className="rounded-lg bg-amber-100 p-3 text-amber-900 dark:bg-amber-950 dark:text-amber-200"
                                    >
                                        <b>
                                            {certificate.expired ? 'Certificado A1 vencido' : `Certificado A1 vence em ${certificate.days_left} dias`}
                                        </b>
                                        <br />
                                        {certificate.expired ? 'Venceu' : 'Válido'} em {new Date(certificate.valid_to).toLocaleDateString('pt-BR')}
                                    </Link>
                                )
                            )}
                        </div>
                    </Panel>
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-5">
                    <div className="lg:col-span-2">
                        <Panel title="Maiores fornecedores">
                            {suppliers.length === 0 ? (
                                <p className="text-muted-foreground text-sm">Nenhuma compra no período.</p>
                            ) : (
                                <div className="flex flex-col gap-4">
                                    {suppliers.map((supplier) => (
                                        <div key={supplier.name}>
                                            <div className="flex justify-between gap-2 text-sm">
                                                <span className="truncate">{supplier.name}</span>
                                                <b>{currency(supplier.total, 0)}</b>
                                            </div>
                                            <div className="bg-muted mt-1.5 h-2 rounded-full">
                                                <div
                                                    className="bg-primary h-2 rounded-full"
                                                    style={{ width: `${(Number(supplier.total) / maxSupplier) * 100}%` }}
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </Panel>
                    </div>
                    <div className="lg:col-span-3">
                        <Panel
                            title="Últimas NF-es"
                            action={
                                <Link href="/nfe-imports" className="text-sm font-medium underline-offset-4 hover:underline">
                                    Ver todas
                                </Link>
                            }
                        >
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <tbody>
                                        {recent.map((item) => (
                                            <tr
                                                key={item.id}
                                                className="hover:bg-muted/50 cursor-pointer border-t"
                                                onClick={() => router.visit(`/nfe-imports/${item.id}`)}
                                            >
                                                <td className="text-muted-foreground py-2.5 pr-3">
                                                    {new Date(item.issued_at).toLocaleDateString('pt-BR')}
                                                </td>
                                                <td className="py-2.5 pr-3 font-medium">
                                                    <Link href={`/nfe-imports/${item.id}`} className="underline-offset-4 hover:underline">
                                                        {item.number}/{item.series}
                                                    </Link>
                                                </td>
                                                <td className="py-2.5 pr-3">{item.supplier}</td>
                                                <td className="py-2.5 text-right font-semibold">{currency(item.total)}</td>
                                            </tr>
                                        ))}
                                        {recent.length === 0 && (
                                            <tr>
                                                <td className="text-muted-foreground py-4 text-center">Nenhuma NF-e importada ainda.</td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </Panel>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

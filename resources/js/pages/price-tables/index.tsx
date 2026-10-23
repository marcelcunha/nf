import { Pagination } from '@/components/pagination';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface PriceTable {
    id: number;
    number: number;
    series: number;
    issued_at: string;
    price_table_published_at: string;
    supplier: { id: number; name: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Tabelas de preço', href: '/tabelas-de-preco' }];

export default function PriceTablesIndex({ tables }: { tables: Paginated<PriceTable> }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tabelas de preço" />
            <div className="flex flex-col gap-5 p-4 md:px-8 md:py-7">
                <div>
                    <h1 className="text-2xl font-bold">Tabelas de preço</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Consulte produtos e valores finais.</p>
                </div>

                <div className="bg-card overflow-hidden rounded-xl border">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted text-muted-foreground text-left">
                                <tr>
                                    <th className="p-3">Publicada em</th>
                                    <th className="p-3">Fornecedor</th>
                                    <th className="p-3">Nota</th>
                                </tr>
                            </thead>
                            <tbody>
                                {tables.data.map((table) => (
                                    <tr
                                        key={table.id}
                                        className="hover:bg-muted/50 cursor-pointer border-t"
                                        onClick={() => router.visit(`/tabelas-de-preco/${table.id}`)}
                                    >
                                        <td className="p-3">{new Date(table.price_table_published_at).toLocaleDateString('pt-BR')}</td>
                                        <td className="p-3">
                                            <Link
                                                href={`/tabelas-de-preco/${table.id}`}
                                                className="text-primary font-semibold underline-offset-4 hover:underline dark:text-teal-300"
                                            >
                                                {table.supplier.name}
                                            </Link>
                                        </td>
                                        <td className="p-3">
                                            {table.number}/{table.series}
                                        </td>
                                    </tr>
                                ))}
                                {tables.data.length === 0 && (
                                    <tr>
                                        <td colSpan={3} className="text-muted-foreground p-6 text-center">
                                            Nenhuma tabela de preço publicada.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <Pagination paginator={tables} />
                </div>
            </div>
        </AppLayout>
    );
}

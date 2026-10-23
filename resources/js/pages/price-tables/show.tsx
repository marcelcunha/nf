import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    table: { id: number; number: number; series: number; supplier: string; published_at: string };
    items: { code: string; name: string; sale_price: string }[];
}

const money = (value: string) => Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

export default function PriceTablesShow({ table, items }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tabelas de preço', href: '/tabelas-de-preco' },
        { title: table.supplier, href: `/tabelas-de-preco/${table.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Tabela de preço · ${table.supplier}`} />
            <div className="flex flex-col gap-5 p-4 md:px-8 md:py-7">
                <div>
                    <h1 className="text-2xl font-bold">{table.supplier}</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Nota {table.number}/{table.series} · publicada em {new Date(table.published_at).toLocaleDateString('pt-BR')}
                    </p>
                </div>

                <div className="bg-card overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted text-muted-foreground text-left">
                            <tr>
                                <th className="p-3">Código</th>
                                <th className="p-3">Produto</th>
                                <th className="p-3 text-right">Valor final</th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item) => (
                                <tr key={item.code} className="border-t">
                                    <td className="text-muted-foreground p-3">{item.code}</td>
                                    <td className="p-3">{item.name}</td>
                                    <td className="p-3 text-right font-semibold">{money(item.sale_price)}</td>
                                </tr>
                            ))}
                            {items.length === 0 && (
                                <tr>
                                    <td colSpan={3} className="text-muted-foreground p-6 text-center">
                                        Nenhum produto com valor definido nesta tabela.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}

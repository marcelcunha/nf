import { Pagination } from '@/components/pagination';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

interface Product {
    id: number;
    code: string;
    name: string;
    ean: string | null;
    ncm: string | null;
    unit: string | null;
    last_unit_cost: string | null;
    supplier: { id: number; name: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Produtos', href: '/products' }];

export default function ProductsIndex({ products, filters }: { products: Paginated<Product>; filters: { search: string } }) {
    const [search, setSearch] = useState(filters.search);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Produtos" />
            <div className="flex flex-col gap-4 p-4">
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get('/products', { search }, { preserveState: true });
                    }}
                >
                    <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Buscar por nome, código ou EAN" className="max-w-sm" />
                </form>
                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">Código</th>
                                <th className="p-3">Produto</th>
                                <th className="p-3">Fornecedor</th>
                                <th className="p-3">EAN</th>
                                <th className="p-3">NCM</th>
                                <th className="p-3">Un.</th>
                                <th className="p-3 text-right">Último custo</th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.data.map((product) => (
                                <tr key={product.id} className="border-t">
                                    <td className="p-3">{product.code}</td>
                                    <td className="p-3">{product.name}</td>
                                    <td className="p-3">{product.supplier.name}</td>
                                    <td className="p-3">{product.ean ?? '—'}</td>
                                    <td className="p-3">{product.ncm ?? '—'}</td>
                                    <td className="p-3">{product.unit ?? '—'}</td>
                                    <td className="p-3 text-right">
                                        {product.last_unit_cost ? Number(product.last_unit_cost).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '—'}
                                    </td>
                                </tr>
                            ))}
                            {products.data.length === 0 && (
                                <tr>
                                    <td colSpan={7} className="text-muted-foreground p-6 text-center">
                                        Nenhum produto encontrado.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <Pagination paginator={products} />
            </div>
        </AppLayout>
    );
}

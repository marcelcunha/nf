import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Person {
    id: number;
    document: string;
    name: string;
    trade_name: string | null;
    city: string | null;
    state: string | null;
    categories: Category[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pessoas', href: '/people' }];

export default function PeopleIndex({
    people,
    categories,
    filters,
}: {
    people: Paginated<Person>;
    categories: Category[];
    filters: { search: string; category: string };
}) {
    const [search, setSearch] = useState(filters.search);

    const visit = (params: { search: string; category: string }) => router.get('/people', params, { preserveState: true });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pessoas" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap gap-2">
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            visit({ search, category: filters.category });
                        }}
                    >
                        <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Buscar por nome ou documento" className="w-72" />
                    </form>
                    <select
                        className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                        value={filters.category}
                        onChange={(event) => visit({ search, category: event.target.value })}
                    >
                        <option value="">Todas as categorias</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.slug}>
                                {category.name}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">Documento</th>
                                <th className="p-3">Nome</th>
                                <th className="p-3">Cidade/UF</th>
                                <th className="p-3">Categorias</th>
                            </tr>
                        </thead>
                        <tbody>
                            {people.data.map((person) => (
                                <tr key={person.id} className="border-t">
                                    <td className="p-3">{person.document}</td>
                                    <td className="p-3">
                                        {person.name}
                                        {person.trade_name && <span className="text-muted-foreground"> ({person.trade_name})</span>}
                                    </td>
                                    <td className="p-3">{person.city ? `${person.city}/${person.state}` : '—'}</td>
                                    <td className="flex gap-1 p-3">
                                        {person.categories.map((category) => (
                                            <Badge key={category.id} variant="secondary">
                                                {category.name}
                                            </Badge>
                                        ))}
                                    </td>
                                </tr>
                            ))}
                            {people.data.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="text-muted-foreground p-6 text-center">
                                        Nenhuma pessoa encontrada.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <Pagination paginator={people} />
            </div>
        </AppLayout>
    );
}

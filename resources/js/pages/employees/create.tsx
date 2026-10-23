import { EmployeeForm } from '@/components/employee-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Funcionários', href: '/funcionarios' },
    { title: 'Novo funcionário', href: '/funcionarios/criar' },
];

export default function EmployeesCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Novo funcionário" />
            <div className="flex flex-col gap-5 p-4 md:px-8 md:py-7">
                <div>
                    <h1 className="text-2xl font-bold">Novo funcionário</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Uma senha será gerada automaticamente e exibida uma única vez após o cadastro. O funcionário só enxerga as tabelas de preço
                        que você publicar.
                    </p>
                </div>
                <EmployeeForm />
            </div>
        </AppLayout>
    );
}

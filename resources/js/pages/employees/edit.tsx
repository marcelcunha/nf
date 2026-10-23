import { EmployeeForm } from '@/components/employee-form';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';

export default function EmployeesEdit({ employee }: { employee: { id: number; name: string; email: string } }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Funcionários', href: '/funcionarios' },
        { title: employee.name, href: `/funcionarios/${employee.id}/editar` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${employee.name}`} />
            <div className="flex flex-col gap-5 p-4 md:px-8 md:py-7">
                <div>
                    <h1 className="text-2xl font-bold">Editar funcionário</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Para trocar a senha, gere uma nova: ela será exibida uma única vez.</p>
                </div>
                <EmployeeForm employee={employee} />
                <div className="bg-card w-full max-w-2xl rounded-xl border p-5">
                    <h2 className="font-semibold">Senha de acesso</h2>
                    <p className="text-muted-foreground mt-1 text-sm">A senha atual deixa de funcionar assim que uma nova é gerada.</p>
                    <Button
                        type="button"
                        variant="outline"
                        className="mt-3"
                        onClick={() => confirm(`Gerar uma nova senha para ${employee.name}?`) && router.post(`/funcionarios/${employee.id}/senha`)}
                    >
                        Gerar nova senha
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}

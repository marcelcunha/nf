import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface Employee {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Funcionários', href: '/funcionarios' }];

export default function EmployeesIndex({ employees }: { employees: Paginated<Employee> }) {
    const { flash } = usePage<SharedData>().props;

    const [copied, setCopied] = useState(false);

    const copyCredentials = async () => {
        if (!flash.credentials) {
            return;
        }

        await navigator.clipboard.writeText(`E-mail: ${flash.credentials.email}\nSenha: ${flash.credentials.password}`);
        setCopied(true);
    };

    const toggle = (employee: Employee) => {
        const action = employee.is_active ? 'desativar' : 'reativar';

        if (confirm(`Deseja ${action} o acesso de ${employee.name}?`)) {
            router.patch(`/funcionarios/${employee.id}/ativo`, {}, { preserveScroll: true });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Funcionários" />
            <div className="flex flex-col gap-5 p-4 md:px-8 md:py-7">
                <div className="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h1 className="text-2xl font-bold">Funcionários</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Funcionários consultam apenas as tabelas de preço publicadas.</p>
                    </div>
                    <Button asChild>
                        <Link href="/funcionarios/criar">Novo funcionário</Link>
                    </Button>
                </div>

                {flash?.success && <p className="text-sm text-green-700 dark:text-green-400">{flash.success}</p>}

                {flash?.credentials && (
                    <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
                        <p className="font-semibold">Dados de acesso de {flash.credentials.name}</p>
                        <p className="mt-2">
                            E-mail: <b>{flash.credentials.email}</b>
                        </p>
                        <p>
                            Senha: <b className="font-mono text-base">{flash.credentials.password}</b>
                        </p>
                        <p className="mt-2">Esta senha é exibida apenas agora. Copie e envie ao funcionário.</p>
                        <Button type="button" size="sm" variant="outline" className="mt-3" onClick={copyCredentials}>
                            {copied ? 'Copiado' : 'Copiar dados de acesso'}
                        </Button>
                    </div>
                )}

                <div className="bg-card overflow-hidden rounded-xl border">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted text-muted-foreground text-left">
                                <tr>
                                    <th className="p-3">Nome</th>
                                    <th className="p-3">E-mail</th>
                                    <th className="p-3">Acesso</th>
                                    <th className="p-3 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {employees.data.map((employee) => (
                                    <tr key={employee.id} className="border-t">
                                        <td className="p-3 font-medium">{employee.name}</td>
                                        <td className="p-3">{employee.email}</td>
                                        <td className="p-3">
                                            {employee.is_active ? (
                                                <span className="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800 dark:bg-green-950 dark:text-green-300">
                                                    Ativo
                                                </span>
                                            ) : (
                                                <span className="rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-800 dark:bg-red-950 dark:text-red-300">
                                                    Desativado
                                                </span>
                                            )}
                                        </td>
                                        <td className="p-3">
                                            <div className="flex justify-end gap-2">
                                                <Button asChild size="sm" variant="outline">
                                                    <Link href={`/funcionarios/${employee.id}/editar`}>Editar</Link>
                                                </Button>
                                                <Button size="sm" variant="outline" onClick={() => toggle(employee)}>
                                                    {employee.is_active ? 'Desativar' : 'Reativar'}
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {employees.data.length === 0 && (
                                    <tr>
                                        <td colSpan={4} className="text-muted-foreground p-6 text-center">
                                            Nenhum funcionário cadastrado.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <Pagination paginator={employees} />
                </div>
            </div>
        </AppLayout>
    );
}

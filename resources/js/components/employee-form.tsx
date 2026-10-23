import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface EmployeeFormProps {
    employee?: { id: number; name: string; email: string };
}

export function EmployeeForm({ employee }: EmployeeFormProps) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: employee?.name ?? '',
        email: employee?.email ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        if (employee) {
            put(`/funcionarios/${employee.id}`);
        } else {
            post('/funcionarios');
        }
    };

    return (
        <form onSubmit={submit} className="bg-card w-full max-w-2xl space-y-5 rounded-xl border p-5">
            <div className="grid gap-2">
                <Label htmlFor="name">Nome</Label>
                <Input id="name" value={data.name} onChange={(event) => setData('name', event.target.value)} required autoComplete="off" />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="email">E-mail</Label>
                <Input
                    id="email"
                    type="email"
                    value={data.email}
                    onChange={(event) => setData('email', event.target.value)}
                    required
                    autoComplete="off"
                />
                <InputError message={errors.email} />
            </div>

            <div className="flex items-center gap-3">
                <Button type="submit" disabled={processing}>
                    {employee ? 'Salvar alterações' : 'Cadastrar e gerar senha'}
                </Button>
                <Button asChild variant="outline">
                    <Link href="/funcionarios">Cancelar</Link>
                </Button>
            </div>
        </form>
    );
}

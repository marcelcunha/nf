import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Horários de importação', href: '/agendamento' }];

const MAX_TIMES = 3;

export default function ImportSchedule({ times, timezone }: { times: string[]; timezone: string }) {
    const { data, setData, errors, put, processing, recentlySuccessful } = useForm<{ times: string[] }>({ times });

    const updateTime = (index: number, value: string) =>
        setData(
            'times',
            data.times.map((time, i) => (i === index ? value : time)),
        );

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('import-schedule.update'), { preserveScroll: true });
    };

    const errorFor = (index: number) => (errors as Record<string, string | undefined>)[`times.${index}`];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Horários de importação" />

            <div className="flex flex-col gap-5 p-4 md:px-8 md:py-7">
                <div>
                    <h1 className="text-2xl font-bold">Horários de importação</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        As NF-es são importadas da SEFAZ automaticamente nestes horários (fuso {timezone}).
                    </p>
                </div>

                <form onSubmit={submit} className="bg-card w-full max-w-md space-y-5 rounded-xl border p-5">
                    <div className="space-y-3">
                        {data.times.map((time, index) => (
                            <div key={index} className="grid gap-1">
                                <div className="flex items-center gap-2">
                                    <Input type="time" value={time} onChange={(e) => updateTime(index, e.target.value)} required />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remover horário"
                                        disabled={data.times.length === 1}
                                        onClick={() =>
                                            setData(
                                                'times',
                                                data.times.filter((_, i) => i !== index),
                                            )
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                                <InputError message={errorFor(index)} />
                            </div>
                        ))}
                        <InputError message={errors.times} />
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        disabled={data.times.length >= MAX_TIMES}
                        onClick={() => setData('times', [...data.times, ''])}
                    >
                        <Plus className="size-4" /> Adicionar horário
                    </Button>

                    <div className="flex items-center gap-4">
                        <Button disabled={processing}>Salvar</Button>

                        <Transition
                            show={recentlySuccessful}
                            enter="transition ease-in-out"
                            enterFrom="opacity-0"
                            leave="transition ease-in-out"
                            leaveTo="opacity-0"
                        >
                            <p className="text-sm text-neutral-600">Salvo</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

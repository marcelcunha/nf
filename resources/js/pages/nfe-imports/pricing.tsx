import { NfeSummary, type NfeSummaryData, type Taxes, decimal, money } from '@/components/nfe-summary';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface Item {
    number: number;
    code: string;
    name: string;
    quantity: string;
    unit: string | null;
    total: string;
    unit_cost_with_taxes: string | null;
    taxes: Taxes;
    product_id: number | null;
    sale_price: string | null;
}

type Props = NfeSummaryData & { details: NfeSummaryData['details'] & { items: Item[] } };

const addedTaxes = (taxes: Taxes) => Number(taxes.ipi) + Number(taxes.icms_st) + Number(taxes.import_tax);
const round = (value: number) => (Math.round(value * 10000) / 10000).toString();

export default function NfeImportsPricing({ nfe, supplier, details }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'NF-es importadas', href: '/nfe-importadas' },
        { title: `NF-e ${nfe.number}/${nfe.series}`, href: `/nfe-importadas/${nfe.id}` },
        { title: 'Precificar', href: `/nfe-importadas/${nfe.id}/precificar` },
    ];

    const [prices, setPrices] = useState<Record<number, string>>(() =>
        Object.fromEntries(details.items.map((item) => [item.number, item.sale_price ? round(Number(item.sale_price)) : ''])),
    );
    const [selected, setSelected] = useState<Set<number>>(new Set());
    const [bulkMargin, setBulkMargin] = useState('');
    const [processing, setProcessing] = useState(false);
    const [saved, setSaved] = useState(false);

    const margin = (item: Item): string => {
        const cost = Number(item.unit_cost_with_taxes);
        const price = prices[item.number];
        if (!item.unit_cost_with_taxes || cost <= 0 || price === '') {
            return '';
        }
        return (Math.round((Number(price) / cost - 1) * 100 * 100) / 100).toString();
    };

    const setMargin = (item: Item, value: string) => {
        const cost = Number(item.unit_cost_with_taxes);
        setPrices((current) => ({
            ...current,
            [item.number]: value === '' || !item.unit_cost_with_taxes ? '' : round(cost * (1 + Number(value) / 100)),
        }));
    };

    const selectable = details.items.filter((item) => item.product_id !== null && item.unit_cost_with_taxes !== null);
    const allSelected = selectable.length > 0 && selectable.every((item) => selected.has(item.number));

    const toggleAll = (checked: boolean) => setSelected(checked ? new Set(selectable.map((item) => item.number)) : new Set());

    const toggleOne = (item: Item, checked: boolean) =>
        setSelected((current) => {
            const next = new Set(current);
            if (checked) {
                next.add(item.number);
            } else {
                next.delete(item.number);
            }
            return next;
        });

    const applyBulkMargin = () => {
        if (bulkMargin === '') {
            return;
        }
        setPrices((current) => {
            const next = { ...current };
            selectable
                .filter((item) => selected.has(item.number))
                .forEach((item) => {
                    next[item.number] = round(Number(item.unit_cost_with_taxes) * (1 + Number(bulkMargin) / 100));
                });
            return next;
        });
    };

    const save = () =>
        router.put(
            `/nfe-importadas/${nfe.id}/precificar`,
            {
                prices: details.items
                    .filter((item) => item.product_id !== null)
                    .map((item) => ({ product_id: item.product_id, sale_price: prices[item.number] === '' ? null : prices[item.number] })),
            },
            {
                preserveScroll: true,
                onStart: () => {
                    setProcessing(true);
                    setSaved(false);
                },
                onSuccess: () => setSaved(true),
                onFinish: () => setProcessing(false),
            },
        );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Precificar NF-e ${nfe.number}/${nfe.series}`} />
            <div className="flex flex-col gap-5 p-4 md:px-8 md:py-7">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-xl font-semibold">
                        Precificar NF-e {nfe.number}/{nfe.series}
                    </h1>
                    <div className="flex items-center gap-3">
                        {saved && <span className="text-sm text-green-600">Preços salvos.</span>}
                        <Button asChild variant="outline">
                            <Link href={`/nfe-importadas/${nfe.id}`}>Voltar</Link>
                        </Button>
                        <Button onClick={save} disabled={processing}>
                            Salvar preços
                        </Button>
                    </div>
                </div>

                <NfeSummary nfe={nfe} supplier={supplier} details={details} />

                <div className="flex flex-wrap items-center gap-3 rounded-xl border p-3 text-sm">
                    <span className="text-muted-foreground">{selected.size} produto(s) selecionado(s)</span>
                    <Input
                        type="number"
                        step="0.01"
                        placeholder="Margem %"
                        aria-label="Margem em lote"
                        className="w-32 text-right"
                        value={bulkMargin}
                        onChange={(event) => setBulkMargin(event.target.value)}
                    />
                    <Button variant="secondary" onClick={applyBulkMargin} disabled={selected.size === 0 || bulkMargin === ''}>
                        Aplicar margem
                    </Button>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">
                                    <Checkbox
                                        aria-label="Selecionar todos"
                                        checked={allSelected}
                                        disabled={selectable.length === 0}
                                        onCheckedChange={(checked) => toggleAll(checked === true)}
                                    />
                                </th>
                                <th className="p-3">#</th>
                                <th className="p-3">Código</th>
                                <th className="p-3">Produto</th>
                                <th className="p-3 text-right">Qtd.</th>
                                <th className="p-3 text-right">Total</th>
                                <th className="p-3 text-right" title="IPI + ICMS ST + Imposto de importação">
                                    Impostos
                                </th>
                                <th className="p-3 text-right">Custo unit. c/ impostos</th>
                                <th className="p-3 text-right">Margem %</th>
                                <th className="p-3 text-right">Preço de venda</th>
                            </tr>
                        </thead>
                        <tbody>
                            {details.items.map((item) => (
                                <tr key={item.number} className="border-t">
                                    <td className="p-3">
                                        <Checkbox
                                            aria-label={`Selecionar item ${item.number}`}
                                            checked={selected.has(item.number)}
                                            disabled={item.product_id === null || item.unit_cost_with_taxes === null}
                                            onCheckedChange={(checked) => toggleOne(item, checked === true)}
                                        />
                                    </td>
                                    <td className="p-3">{item.number}</td>
                                    <td className="p-3 font-mono text-xs">{item.code}</td>
                                    <td className="p-3">{item.name}</td>
                                    <td className="p-3 text-right">
                                        {decimal(item.quantity)} {item.unit}
                                    </td>
                                    <td className="p-3 text-right">{money(item.total)}</td>
                                    <td className="p-3 text-right">{money(String(addedTaxes(item.taxes)))}</td>
                                    <td className="p-3 text-right font-medium">
                                        {item.unit_cost_with_taxes !== null ? money(item.unit_cost_with_taxes) : '—'}
                                    </td>
                                    <td className="p-3 text-right">
                                        <Input
                                            type="number"
                                            step="0.01"
                                            className="ml-auto w-24 text-right"
                                            value={margin(item)}
                                            disabled={item.unit_cost_with_taxes === null}
                                            onChange={(event) => setMargin(item, event.target.value)}
                                        />
                                    </td>
                                    <td className="p-3 text-right">
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            className="ml-auto w-32 text-right"
                                            value={prices[item.number]}
                                            disabled={item.product_id === null}
                                            onChange={(event) => setPrices((current) => ({ ...current, [item.number]: event.target.value }))}
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}

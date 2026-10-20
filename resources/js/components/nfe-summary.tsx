export interface Taxes {
    icms: string;
    icms_st: string;
    ipi: string;
    pis: string;
    cofins: string;
    import_tax: string;
    approximate: string | null;
}

export interface NfeSummaryData {
    nfe: { id: number; access_key: string; number: number; series: number; issued_at: string; total: string };
    supplier: { id: number; name: string; document: string };
    details: {
        totals: { products: string; discount: string; freight: string; total: string };
        taxes: Taxes;
        recipient: { name: string | null; document: string | null };
        protocol: string | null;
        nature: string | null;
    };
}

export const money = (value: string) => Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
export const decimal = (value: string) => Number(value).toLocaleString('pt-BR', { maximumFractionDigits: 4 });

export const taxEntries = (taxes: Taxes): [string, string][] => [
    ['ICMS', taxes.icms],
    ['ICMS ST', taxes.icms_st],
    ['IPI', taxes.ipi],
    ['PIS', taxes.pis],
    ['COFINS', taxes.cofins],
    ['Imp. importação', taxes.import_tax],
];

export function NfeSummary({ nfe, supplier, details }: NfeSummaryData) {
    const summary: [string, string | null][] = [
        ['Emissão', new Date(nfe.issued_at).toLocaleString('pt-BR')],
        ['Natureza da operação', details.nature],
        ['Fornecedor', `${supplier.name} (${supplier.document})`],
        [
            'Destinatário',
            details.recipient.name ? `${details.recipient.name}${details.recipient.document ? ` (${details.recipient.document})` : ''}` : null,
        ],
        ['Protocolo de autorização', details.protocol],
        ['Chave de acesso', nfe.access_key],
    ];

    return (
        <dl className="grid gap-3 rounded-xl border p-4 text-sm sm:grid-cols-2">
            {summary.map(([label, value]) => (
                <div key={label}>
                    <dt className="text-muted-foreground">{label}</dt>
                    <dd className="break-all">{value ?? '—'}</dd>
                </div>
            ))}
        </dl>
    );
}

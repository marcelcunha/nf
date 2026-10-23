import { cn } from '@/lib/utils';
import { type Paginated } from '@/types';
import { router } from '@inertiajs/react';

const labelFor = (label: string, index: number, last: number): string => {
    if (index === 0) {
        return '‹';
    }

    if (index === last) {
        return '›';
    }

    return label;
};

export function Pagination<T>({ paginator }: { paginator: Paginated<T> }) {
    if (paginator.total === 0) {
        return null;
    }

    const last = paginator.links.length - 1;

    return (
        <nav aria-label="Paginação" className="text-muted-foreground flex flex-wrap items-center justify-between gap-2 border-t p-3 text-sm">
            <p>
                {paginator.from}–{paginator.to} de {paginator.total}
            </p>
            <div className="flex flex-wrap items-center gap-1">
                {paginator.links.map((link, index) => (
                    <button
                        key={index}
                        type="button"
                        disabled={!link.url}
                        aria-current={link.active ? 'page' : undefined}
                        aria-label={index === 0 ? 'Página anterior' : index === last ? 'Próxima página' : `Página ${link.label}`}
                        onClick={() => link.url && router.visit(link.url, { preserveScroll: true })}
                        className={cn(
                            'min-w-8 rounded-md px-2 py-1.5 transition-colors',
                            link.active ? 'bg-primary text-primary-foreground font-semibold' : 'hover:bg-muted',
                            !link.url && 'cursor-default opacity-50 hover:bg-transparent',
                        )}
                    >
                        {labelFor(link.label, index, last)}
                    </button>
                ))}
            </div>
        </nav>
    );
}

import { Button } from '@/components/ui/button';
import { type Paginated } from '@/types';
import { router } from '@inertiajs/react';

export function Pagination<T>({ paginator }: { paginator: Paginated<T> }) {
    if (paginator.total === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-2 pt-4">
            <p className="text-muted-foreground text-sm">
                {paginator.from}–{paginator.to} de {paginator.total}
            </p>
            <div className="flex flex-wrap gap-1">
                {paginator.links.map((link, index) => (
                    <Button
                        key={index}
                        size="sm"
                        variant={link.active ? 'default' : 'outline'}
                        disabled={!link.url}
                        onClick={() => link.url && router.visit(link.url, { preserveScroll: true })}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ))}
            </div>
        </div>
    );
}

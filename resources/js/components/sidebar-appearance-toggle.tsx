import { Appearance, useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { LucideIcon, Monitor, Moon, Sun } from 'lucide-react';

const options: { value: Appearance; icon: LucideIcon; label: string }[] = [
    { value: 'light', icon: Sun, label: 'Tema claro' },
    { value: 'dark', icon: Moon, label: 'Tema escuro' },
    { value: 'system', icon: Monitor, label: 'Tema do sistema' },
];

export function SidebarAppearanceToggle() {
    const { appearance, updateAppearance } = useAppearance();

    return (
        <div
            className="bg-sidebar-accent/60 grid grid-cols-3 gap-1 rounded-lg p-1 group-data-[collapsible=icon]:hidden"
            role="group"
            aria-label="Tema"
        >
            {options.map(({ value, icon: Icon, label }) => (
                <button
                    key={value}
                    type="button"
                    aria-label={label}
                    aria-pressed={appearance === value}
                    title={label}
                    onClick={() => updateAppearance(value)}
                    className={cn(
                        'text-sidebar-foreground flex h-8 items-center justify-center rounded-md transition-colors',
                        appearance === value
                            ? 'bg-sidebar-primary text-sidebar-primary-foreground'
                            : 'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                    )}
                >
                    <Icon className="size-4" />
                </button>
            ))}
        </div>
    );
}

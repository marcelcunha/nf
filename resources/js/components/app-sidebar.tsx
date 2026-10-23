import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { SidebarAppearanceToggle } from '@/components/sidebar-appearance-toggle';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { FileText, LayoutGrid, ShieldCheck, Tags, Users } from 'lucide-react';
import AppLogo from './app-logo';

const priceTablesItem: NavItem = {
    title: 'Tabelas de preço',
    url: '/tabelas-de-preco',
    icon: Tags,
};

const adminNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: '/',
        icon: LayoutGrid,
    },
    {
        title: 'NF-es importadas',
        url: '/nfe-importadas',
        icon: FileText,
    },
    priceTablesItem,
    {
        title: 'Funcionários',
        url: '/funcionarios',
        icon: Users,
    },
    {
        title: 'Certificado digital',
        url: '/certificado',
        icon: ShieldCheck,
    },
];

const employeeNavItems: NavItem[] = [priceTablesItem];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const navItems = auth.user.role === 'admin' ? adminNavItems : employeeNavItems;

    return (
        <Sidebar collapsible="offcanvas" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <SidebarAppearanceToggle />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

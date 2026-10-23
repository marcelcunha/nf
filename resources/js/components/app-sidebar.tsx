import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { SidebarAppearanceToggle } from '@/components/sidebar-appearance-toggle';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { FileText, LayoutGrid, ShieldCheck } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
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
    {
        title: 'Certificado digital',
        url: '/certificado',
        icon: ShieldCheck,
    },
];

export function AppSidebar() {
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
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <SidebarAppearanceToggle />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

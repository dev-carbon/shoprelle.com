import { Link } from '@inertiajs/react';
import {
    ChartNoAxesColumn,
    LayoutGrid,
    MessagesSquare,
    PackageSearch,
    Settings,
    Star,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { statistics } from '@/routes/admin';
import { index as customersIndex } from '@/routes/admin/customers';
import { index as requestsIndex } from '@/routes/admin/requests';
import { index as reviewsIndex } from '@/routes/admin/reviews';
import { show as chatShow } from '@/routes/chat';
import { edit as settings } from '@/routes/profile';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Tableau de bord',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Demandes',
        href: requestsIndex(),
        icon: PackageSearch,
    },
    {
        title: 'Clients',
        href: customersIndex(),
        icon: Users,
    },
    {
        title: 'Avis',
        href: reviewsIndex(),
        icon: Star,
    },
    {
        title: 'Statistiques',
        href: statistics(),
        icon: ChartNoAxesColumn,
    },
    {
        title: 'Paramètres',
        href: settings(),
        icon: Settings,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: "Ouvrir l'assistant",
        href: chatShow(),
        icon: MessagesSquare,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
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
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

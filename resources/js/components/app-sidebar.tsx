import { Link } from '@inertiajs/react';
import { LayoutGrid, PiggyBank, Receipt, Tags, Wallet } from 'lucide-react';
import AppLogo from '@/components/app-logo';
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
import { index as accounts } from '@/routes/accounts';
import { index as budget } from '@/routes/budget';
import { index as categories } from '@/routes/categories';
import { index as transactions } from '@/routes/transactions';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Overview',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Transactions',
        href: transactions(),
        icon: Receipt,
    },
    {
        title: 'Budget',
        href: budget(),
        icon: PiggyBank,
    },
    {
        title: 'Accounts',
        href: accounts(),
        icon: Wallet,
    },
    {
        title: 'Categories',
        href: categories(),
        icon: Tags,
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
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

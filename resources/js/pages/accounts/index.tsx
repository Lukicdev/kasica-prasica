import { Head, Link } from '@inertiajs/react';
import { Plus, Wallet } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { create, show } from '@/routes/accounts';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accounts',
        href: '/accounts',
    },
];

interface Account {
    id: number;
    name: string;
    type: string;
    currency: string;
    initial_balance: string;
    is_shared: boolean;
}

interface AccountsIndexProps {
    accounts: Account[];
}

export default function AccountsIndex({ accounts }: AccountsIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Accounts" />

            <div className="p-4">
                <div className="flex items-center justify-between m-2">
                    <div>
                        <h1 className="text-2xl font-semibold">Accounts</h1>
                        <p className="text-muted-foreground">
                            Manage your financial accounts
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 size-4" />
                            Create Account
                        </Link>
                    </Button>
                </div>

                {accounts.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Wallet className="mb-4 size-12 text-muted-foreground" />
                            <CardTitle className="mb-2">No accounts yet</CardTitle>
                            <CardDescription className="mb-4">
                                Get started by creating your first account
                            </CardDescription>
                            <Button asChild>
                                <Link href={create().url}>
                                    <Plus className="mr-2 size-4" />
                                    Create Account
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {accounts.map((account) => (
                            <Card key={account.id} className="hover:shadow-md transition-shadow">
                                <CardHeader>
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <CardTitle className="text-lg">
                                                {account.name}
                                            </CardTitle>
                                            <CardDescription className="mt-1">
                                                {account.type.charAt(0).toUpperCase() + account.type.slice(1)}
                                                {account.is_shared && (
                                                    <span className="ml-2 text-xs">
                                                        (Shared)
                                                    </span>
                                                )}
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2">
                                        <div className="flex items-baseline justify-between">
                                            <span className="text-sm text-muted-foreground">
                                                Balance
                                            </span>
                                            <span className="text-lg font-semibold">
                                                {parseFloat(account.initial_balance).toLocaleString('en-US', {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2,
                                                })}{' '}
                                                {account.currency}
                                            </span>
                                        </div>
                                        <Button
                                            asChild
                                            variant="outline"
                                            className="w-full mt-4"
                                        >
                                            <Link href={show(account.id).url}>
                                                View Details
                                            </Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

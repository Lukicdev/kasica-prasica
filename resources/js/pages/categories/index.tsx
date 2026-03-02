import { Head, Link } from '@inertiajs/react';
import { Plus, Tag } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { create, show } from '@/routes/categories';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: '/categories',
    },
];

interface Category {
    id: number;
    name: string;
    type: string;
    color: string;
    user_id: number | null;
}

interface CategoriesIndexProps {
    categories: Category[];
}

export default function CategoriesIndex({ categories }: CategoriesIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Categories" />

            <div className="p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Categories</h1>
                        <p className="text-muted-foreground">
                            Manage your transaction categories
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 size-4" />
                            Create Category
                        </Link>
                    </Button>
                </div>

                {categories.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Tag className="mb-4 size-12 text-muted-foreground" />
                            <CardTitle className="mb-2">No categories yet</CardTitle>
                            <CardDescription className="mb-4">
                                Get started by creating your first category
                            </CardDescription>
                            <Button asChild>
                                <Link href={create().url}>
                                    <Plus className="mr-2 size-4" />
                                    Create Category
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 mt-6">
                        {categories.map((category) => (
                            <Card key={category.id} className="hover:shadow-md transition-shadow">
                                <CardHeader>
                                    <div className="flex items-start justify-between">
                                        <div className="flex items-center gap-3">
                                            <div
                                                className="size-4 rounded-full"
                                                style={{ backgroundColor: category.color }}
                                            />
                                            <div>
                                                <CardTitle className="text-lg">
                                                    {category.name}
                                                </CardTitle>
                                                <CardDescription className="mt-1">
                                                    {category.type.charAt(0).toUpperCase() + category.type.slice(1)}
                                                    {category.user_id === null && (
                                                        <span className="ml-2 text-xs">
                                                            (Shared)
                                                        </span>
                                                    )}
                                                </CardDescription>
                                            </div>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="w-full"
                                    >
                                        <Link href={show(category.id).url}>
                                            View Details
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

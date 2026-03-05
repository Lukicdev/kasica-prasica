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

const TYPE_ORDER: readonly string[] = ['income', 'expense'];

function formatTypeName(type: string): string {
    return type.charAt(0).toUpperCase() + type.slice(1);
}

function groupCategoriesByType(categories: Category[]): Map<string, Category[]> {
    const grouped = new Map<string, Category[]>();
    for (const category of categories) {
        const type = category.type.toLowerCase();
        const list = grouped.get(type) ?? [];
        list.push(category);
        grouped.set(type, list);
    }
    return grouped;
}

export default function CategoriesIndex({ categories }: CategoriesIndexProps) {
    const byType = groupCategoriesByType(categories);
    const orderedTypes = [
        ...TYPE_ORDER.filter((t) => byType.has(t)),
        ...[...byType.keys()].filter((t) => !TYPE_ORDER.includes(t)),
    ];

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
                    <div className="mt-6 flex flex-col gap-8">
                        {orderedTypes.map((type) => {
                            const typeCategories = byType.get(type) ?? [];
                            return (
                                <section key={type}>
                                    <h2 className="mb-4 text-lg font-medium text-muted-foreground">
                                        {formatTypeName(type)}
                                    </h2>
                                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                        {typeCategories.map((category) => (
                                            <Card
                                                key={category.id}
                                                className="transition-shadow hover:shadow-md"
                                            >
                                                <CardHeader>
                                                    <div className="flex items-start justify-between">
                                                        <div className="flex items-center gap-3">
                                                            <div
                                                                className="size-4 rounded-full"
                                                                style={{
                                                                    backgroundColor:
                                                                        category.color,
                                                                }}
                                                            />
                                                            <div>
                                                                <CardTitle className="text-lg">
                                                                    {category.name}
                                                                </CardTitle>
                                                                <CardDescription className="mt-1">
                                                                    {category.user_id ===
                                                                        null && (
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
                                                        <Link
                                                            href={show(
                                                                category.id,
                                                            ).url}
                                                        >
                                                            View Details
                                                        </Link>
                                                    </Button>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                </section>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

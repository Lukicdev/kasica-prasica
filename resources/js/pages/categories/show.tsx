import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Edit, Tag, Trash2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { destroy, edit, index } from '@/routes/categories';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: index().url,
    },
];

interface Category {
    id: number;
    name: string;
    type: string;
    color: string;
    user_id: number | null;
    user?: {
        id: number;
        name: string;
    } | null;
}

interface CategoryShowProps {
    category: Category;
}

export default function CategoryShow({ category }: CategoryShowProps) {
    const handleDelete = () => {
        router.delete(destroy(category.id).url, {
            onSuccess: () => {
                router.visit(index().url);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={category.name} />

            <div className="p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={index().url}>
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-semibold">
                                {category.name}
                            </h1>
                            <p className="text-muted-foreground">
                                Category details
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit(category.id).url}>
                                <Edit className="mr-2 size-4" />
                                Edit
                            </Link>
                        </Button>
                        <Dialog>
                            <DialogTrigger asChild>
                                <Button variant="destructive">
                                    <Trash2 className="mr-2 size-4" />
                                    Delete
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Delete Category</DialogTitle>
                                    <DialogDescription>
                                        Are you sure you want to delete this
                                        category? This action cannot be undone.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <Button
                                        variant="destructive"
                                        onClick={handleDelete}
                                    >
                                        Delete Category
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2 mt-6">
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Tag className="size-5" />
                                <CardTitle>Category Information</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <CardDescription>Name</CardDescription>
                                <p className="text-sm font-medium">
                                    {category.name}
                                </p>
                            </div>
                            <div>
                                <CardDescription>Type</CardDescription>
                                <p className="text-sm font-medium">
                                    {category.type.charAt(0).toUpperCase() +
                                        category.type.slice(1)}
                                </p>
                            </div>
                            <div>
                                <CardDescription>Shared Category</CardDescription>
                                <p className="text-sm font-medium">
                                    {category.user_id === null ? 'Yes' : 'No'}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Color</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-4">
                                <div
                                    className="size-16 rounded-lg border-2 border-border"
                                    style={{ backgroundColor: category.color }}
                                />
                                <div>
                                    <p className="text-lg font-semibold">
                                        {category.color}
                                    </p>
                                    <CardDescription className="mt-1">
                                        Hex color code
                                    </CardDescription>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

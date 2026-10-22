export const categoryColorNames = [
    'rose',
    'orange',
    'amber',
    'yellow',
    'lime',
    'green',
    'teal',
    'sky',
    'blue',
    'violet',
    'fuchsia',
    'pink',
] as const;

export type CategoryColorName = (typeof categoryColorNames)[number];

export const categoryColors: {
    value: CategoryColorName;
    label: string;
    className: string;
}[] = [
    { value: 'rose', label: 'Rose', className: 'bg-rose-200 dark:bg-rose-800' },
    {
        value: 'orange',
        label: 'Orange',
        className: 'bg-orange-200 dark:bg-orange-800',
    },
    {
        value: 'amber',
        label: 'Amber',
        className: 'bg-amber-200 dark:bg-amber-800',
    },
    {
        value: 'yellow',
        label: 'Yellow',
        className: 'bg-yellow-200 dark:bg-yellow-800',
    },
    { value: 'lime', label: 'Lime', className: 'bg-lime-200 dark:bg-lime-800' },
    {
        value: 'green',
        label: 'Green',
        className: 'bg-green-200 dark:bg-green-800',
    },
    { value: 'teal', label: 'Teal', className: 'bg-teal-200 dark:bg-teal-800' },
    { value: 'sky', label: 'Sky', className: 'bg-sky-200 dark:bg-sky-800' },
    { value: 'blue', label: 'Blue', className: 'bg-blue-200 dark:bg-blue-800' },
    {
        value: 'violet',
        label: 'Violet',
        className: 'bg-violet-200 dark:bg-violet-800',
    },
    {
        value: 'fuchsia',
        label: 'Fuchsia',
        className: 'bg-fuchsia-200 dark:bg-fuchsia-800',
    },
    { value: 'pink', label: 'Pink', className: 'bg-pink-200 dark:bg-pink-800' },
];

export function categoryColorClass(
    color: string | null | undefined,
): string | null {
    return (
        categoryColors.find((option) => option.value === color)?.className ??
        null
    );
}

export function categoryColorLabel(
    color: string | null | undefined,
): string | null {
    return (
        categoryColors.find((option) => option.value === color)?.label ?? null
    );
}

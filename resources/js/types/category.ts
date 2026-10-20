export type CategorySummary = {
    id: number;
    name: string;
    icon: string | null;
    kind: string;
    description: string | null;
    label: string;
    archived: boolean;
};

export type CategoryGroup = {
    kind: string;
    label: string;
    categories: CategorySummary[];
    archived: CategorySummary[];
};

export type CategoryKindOption = {
    value: string;
    label: string;
};

export type CategoryFormValues = CategorySummary & {
    kind_locked: boolean;
    can_delete: boolean;
    created_by: string | null;
    updated_by: string | null;
};

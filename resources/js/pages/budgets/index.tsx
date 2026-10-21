import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { EntityIcon } from '@/components/entity-icon';
import { MoneyAmount } from '@/components/money-amount';
import { MonthNav } from '@/components/month-nav';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    addAmounts,
    canonicalAmount,
    formatMoney,
    isNegativeAmount,
    subtractAmounts,
} from '@/lib/money';
import { index, store, update } from '@/routes/budgets';
import type {
    BudgetCategory,
    BudgetCopyFrom,
    BudgetFigure,
    BudgetMonth,
} from '@/types/budget';

function pageError(errors: unknown, key: string): string | null {
    if (typeof errors !== 'object' || errors === null || !(key in errors)) {
        return null;
    }

    const message = errors[key as keyof typeof errors];

    return typeof message === 'string' ? message : null;
}

function allocationStatus(baseAmount: string): string {
    if (isNegativeAmount(baseAmount)) {
        return 'Over planned income';
    }

    if (baseAmount === '0.00') {
        return 'Fully allocated';
    }

    return 'Not yet allocated';
}

function rowAmount(
    category: BudgetCategory,
    drafts: Record<number, string>,
): string {
    const draft = drafts[category.id];

    if (draft === undefined) {
        return category.amount;
    }

    return canonicalAmount(draft) ?? category.amount;
}

function totalBase(
    categories: BudgetCategory[],
    drafts: Record<number, string>,
): string {
    return categories.reduce((total, category) => {
        return addAmounts(total, rowAmount(category, drafts)) ?? total;
    }, '0.00');
}

function figureFromBase(baseAmount: string): BudgetFigure {
    return {
        amount: baseAmount,
        currency: 'RSD',
        formatted: formatMoney(baseAmount, 'RSD') ?? `${baseAmount} RSD`,
        negative: isNegativeAmount(baseAmount),
        base_amount: baseAmount,
    };
}

export default function Budget({
    month,
    started,
    editable,
    copy_from,
    categories,
    income,
    budget,
    unallocated,
}: {
    month: BudgetMonth;
    started: boolean;
    editable: boolean;
    copy_from: BudgetCopyFrom | null;
    categories: BudgetCategory[];
    income: BudgetFigure;
    budget: BudgetFigure;
    unallocated: BudgetFigure;
}) {
    const errors = usePage().props.errors;
    const savedKey = `${month.value}|${categories.map((category) => `${category.id}:${category.amount}`).join(',')}`;
    const [draftKey, setDraftKey] = useState(savedKey);
    const [drafts, setDrafts] = useState<Record<number, string>>(() =>
        Object.fromEntries(
            categories.map((category) => [category.id, category.amount]),
        ),
    );
    const [savingId, setSavingId] = useState<number | null>(null);
    const [starting, setStarting] = useState(false);
    const [fieldError, setFieldError] = useState<string | null>(null);

    if (draftKey !== savedKey) {
        setDraftKey(savedKey);
        setDrafts(
            Object.fromEntries(
                categories.map((category) => [category.id, category.amount]),
            ),
        );
        setSavingId(null);
        setFieldError(null);
    }

    const budgetBase = editable
        ? totalBase(categories, drafts)
        : budget.base_amount;
    const unallocatedBase = editable
        ? (subtractAmounts(income.base_amount, budgetBase) ??
          unallocated.base_amount)
        : unallocated.base_amount;
    const budgetFigure = editable ? figureFromBase(budgetBase) : budget;
    const unallocatedFigure = editable
        ? figureFromBase(unallocatedBase)
        : unallocated;

    const start = (copy: boolean): void => {
        router.post(
            store.url(),
            { month: month.value, copy },
            {
                preserveScroll: true,
                onStart: () => setStarting(true),
                onFinish: () => setStarting(false),
            },
        );
    };

    const save = (category: BudgetCategory): void => {
        const raw = drafts[category.id] ?? category.amount;
        const canonical = canonicalAmount(raw);

        if (canonical === null) {
            setFieldError(
                'Enter an amount in dinars with up to two decimal places.',
            );
            setDrafts((current) => ({
                ...current,
                [category.id]: category.amount,
            }));

            return;
        }

        setFieldError(null);
        setDrafts((current) => ({ ...current, [category.id]: canonical }));

        if (canonical === category.amount) {
            return;
        }

        router.put(
            update.url(),
            {
                month: month.value,
                category_id: category.id,
                amount: canonical,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setSavingId(category.id),
                onFinish: () =>
                    setSavingId((current) =>
                        current === category.id ? null : current,
                    ),
            },
        );
    };

    return (
        <>
            <Head title="Budgets" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h1 className="text-lg font-semibold tracking-tight">
                        Budgets
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {month.label}. Expense budgets are stored in RSD.
                        {editable
                            ? ' Amounts update the totals as you type and save when you leave the field.'
                            : ' Switch the display currency to RSD to edit them.'}
                    </p>
                </section>

                <MonthNav
                    value={month.value}
                    previousHref={index({ query: { month: month.previous } })}
                    nextHref={index({ query: { month: month.next } })}
                    hrefFor={(value) => index({ query: { month: value } })}
                />

                <Summary
                    income={income}
                    budget={budgetFigure}
                    unallocated={unallocatedFigure}
                />

                {pageError(errors, 'month') ? (
                    <p className="text-sm text-destructive" role="alert">
                        {pageError(errors, 'month')}
                    </p>
                ) : null}
                {pageError(errors, 'copy') ? (
                    <p className="text-sm text-destructive" role="alert">
                        {pageError(errors, 'copy')}
                    </p>
                ) : null}
                {pageError(errors, 'amount') ||
                pageError(errors, 'category_id') ||
                fieldError ? (
                    <p className="text-sm text-destructive" role="alert">
                        {pageError(errors, 'amount') ??
                            pageError(errors, 'category_id') ??
                            fieldError}
                    </p>
                ) : null}

                {started ? (
                    <ul
                        className="flex flex-col gap-3"
                        aria-label="Expense budgets"
                    >
                        {categories.map((category) => (
                            <BudgetRow
                                key={category.id}
                                category={category}
                                editable={editable}
                                value={drafts[category.id] ?? category.amount}
                                saving={savingId === category.id}
                                onChange={(value) => {
                                    setFieldError(null);
                                    setDrafts((current) => ({
                                        ...current,
                                        [category.id]: value,
                                    }));
                                }}
                                onCommit={() => save(category)}
                            />
                        ))}
                    </ul>
                ) : (
                    <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div className="flex flex-col gap-1">
                            <h2 className="font-medium">
                                {month.label} has no budget yet.
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {copy_from
                                    ? `Copy ${copy_from.label}, or start this month with every category at zero.`
                                    : 'The previous month has no budget. Start this month with every category at zero.'}
                            </p>
                        </div>
                        <div className="flex flex-col gap-2 sm:flex-row">
                            {copy_from ? (
                                <Button
                                    type="button"
                                    className="h-11"
                                    disabled={starting}
                                    data-test="copy-budget"
                                    onClick={() => start(true)}
                                >
                                    Copy from {copy_from.label}
                                </Button>
                            ) : null}
                            <Button
                                type="button"
                                variant={copy_from ? 'outline' : 'default'}
                                className="h-11"
                                disabled={starting}
                                data-test="start-empty-budget"
                                onClick={() => start(false)}
                            >
                                Start empty
                            </Button>
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}

function Summary({
    income,
    budget,
    unallocated,
}: {
    income: BudgetFigure;
    budget: BudgetFigure;
    unallocated: BudgetFigure;
}) {
    const status = allocationStatus(unallocated.base_amount);

    return (
        <section
            aria-label="Planned balance"
            className="grid gap-3 sm:grid-cols-3"
        >
            <SummaryItem
                testId="budget-income"
                label="Income so far"
                figure={income}
                caption="Income recorded this month."
            />
            <SummaryItem
                testId="budget-total"
                label="Total budget"
                figure={budget}
                caption="Sum of the category budgets."
            />
            <SummaryItem
                testId="budget-unallocated"
                label="Unallocated"
                figure={unallocated}
                caption={status}
            />
        </section>
    );
}

function SummaryItem({
    testId,
    label,
    figure,
    caption,
}: {
    testId: string;
    label: string;
    figure: BudgetFigure;
    caption: string;
}) {
    return (
        <div
            className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            data-test={testId}
        >
            <span className="text-sm text-muted-foreground">{label}</span>
            <span className="text-lg font-semibold tracking-tight">
                {figure.formatted}
            </span>
            <span className="text-sm text-muted-foreground">{caption}</span>
        </div>
    );
}

function BudgetRow({
    category,
    editable,
    value,
    saving,
    onChange,
    onCommit,
}: {
    category: BudgetCategory;
    editable: boolean;
    value: string;
    saving: boolean;
    onChange: (value: string) => void;
    onCommit: () => void;
}) {
    const inputId = `budget-${category.id}`;

    return (
        <li
            className="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            data-test="budget-category"
        >
            <div className="flex flex-col gap-1">
                <span className="flex flex-wrap items-center gap-2">
                    <EntityIcon name={category.icon} />
                    {editable ? (
                        <Label htmlFor={inputId} className="font-medium">
                            {category.name}
                        </Label>
                    ) : (
                        <span className="text-sm font-medium">
                            {category.name}
                        </span>
                    )}
                    {category.archived ? (
                        <Badge variant="outline">Archived</Badge>
                    ) : null}
                </span>
                {category.description ? (
                    <span className="text-sm text-muted-foreground">
                        {category.description}
                    </span>
                ) : null}
                {category.updated_by ? (
                    <span className="text-sm text-muted-foreground">
                        Last changed by {category.updated_by}.
                    </span>
                ) : null}
            </div>
            {editable ? (
                <div className="flex flex-col gap-2 sm:max-w-xs">
                    <Input
                        id={inputId}
                        inputMode="decimal"
                        autoComplete="off"
                        value={value}
                        aria-label={`${category.name} budget in RSD`}
                        data-test="budget-amount"
                        className="h-11 md:h-9"
                        onChange={(event) => onChange(event.target.value)}
                        onBlur={onCommit}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                event.currentTarget.blur();
                            }
                        }}
                    />
                    <span className="text-sm text-muted-foreground">
                        {saving ? 'Saving…' : 'RSD'}
                    </span>
                </div>
            ) : (
                <p data-test="budget-display">
                    <MoneyAmount
                        formatted={category.display.formatted}
                        originalFormatted={category.display.original_formatted}
                    />
                </p>
            )}
        </li>
    );
}

Budget.layout = {
    breadcrumbs: [
        {
            title: 'Budgets',
            href: index(),
        },
    ],
};

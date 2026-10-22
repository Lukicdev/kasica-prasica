import { Head, router, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { EntityIcon } from '@/components/entity-icon';
import { MoneyAmount } from '@/components/money-amount';
import { MonthNav } from '@/components/month-nav';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
    InputGroupText,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverDescription,
    PopoverHeader,
    PopoverTitle,
    PopoverTrigger,
} from '@/components/ui/popover';
import { canonicalAmount, isNegativeAmount } from '@/lib/money';
import { cn } from '@/lib/utils';
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
    const [savingId, setSavingId] = useState<number | null>(null);
    const [starting, setStarting] = useState(false);

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

    const save = (category: BudgetCategory, amount: string): void => {
        router.put(
            update.url(),
            {
                month: month.value,
                category_id: category.id,
                amount,
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
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Budgets
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {editable
                                ? 'Expense budgets for the month. Click a category to set its amount in dinars.'
                                : 'Expense budgets for the month. Switch the display currency to RSD to change them.'}
                        </p>
                    </div>
                    <MonthNav
                        value={month.value}
                        previousHref={index({
                            query: { month: month.previous },
                        })}
                        nextHref={index({ query: { month: month.next } })}
                        hrefFor={(value) => index({ query: { month: value } })}
                    />
                </section>

                <Summary
                    income={income}
                    budget={budget}
                    unallocated={unallocated}
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
                pageError(errors, 'category_id') ? (
                    <p className="text-sm text-destructive" role="alert">
                        {pageError(errors, 'amount') ??
                            pageError(errors, 'category_id')}
                    </p>
                ) : null}

                {started && copy_from ? (
                    <CopyOverBanner
                        from={copy_from}
                        month={month.label}
                        starting={starting}
                        onCopy={() => start(true)}
                    />
                ) : null}

                {started ? (
                    <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        <h2 className="px-4 py-3 text-sm font-medium text-muted-foreground">
                            Expenses
                        </h2>
                        {categories.length === 0 ? (
                            <p className="border-t border-sidebar-border/70 px-4 py-6 text-sm text-muted-foreground dark:border-sidebar-border">
                                No expense categories yet.
                            </p>
                        ) : (
                            <ul
                                className="divide-y divide-sidebar-border/70 border-t border-sidebar-border/70 dark:divide-sidebar-border dark:border-sidebar-border"
                                aria-label="Expense budgets"
                            >
                                {categories.map((category) => (
                                    <BudgetRow
                                        key={category.id}
                                        category={category}
                                        editable={editable}
                                        saving={savingId === category.id}
                                        onSave={(amount) =>
                                            save(category, amount)
                                        }
                                    />
                                ))}
                            </ul>
                        )}
                    </section>
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
                                    className="h-11 md:h-9"
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
                                className="h-11 md:h-9"
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

function CopyOverBanner({
    from,
    month,
    starting,
    onCopy,
}: {
    from: BudgetCopyFrom;
    month: string;
    starting: boolean;
    onCopy: () => void;
}) {
    const [open, setOpen] = useState(false);

    return (
        <section className="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border">
            <p className="text-sm text-muted-foreground">
                Copying from {from.label} replaces the budget already on{' '}
                {month}.
            </p>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogTrigger asChild>
                    <Button
                        type="button"
                        variant="outline"
                        className="h-11 md:h-9"
                        disabled={starting}
                        data-test="copy-budget"
                    >
                        Copy from {from.label}
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogTitle>Replace this month&apos;s budget?</DialogTitle>
                    <DialogDescription>
                        Copying from {from.label} replaces every amount on{' '}
                        {month}. Amounts already entered for this month will be
                        lost.
                    </DialogDescription>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="button"
                            disabled={starting}
                            data-test="confirm-copy-budget"
                            onClick={() => {
                                setOpen(false);
                                onCopy();
                            }}
                        >
                            Copy from {from.label}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </section>
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
    saving,
    onSave,
}: {
    category: BudgetCategory;
    editable: boolean;
    saving: boolean;
    onSave: (amount: string) => void;
}) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [open, setOpen] = useState(false);
    const [value, setValue] = useState(category.amount);
    const [error, setError] = useState<string | null>(null);
    const inputId = `budget-${category.id}`;

    const commit = (): boolean => {
        const canonical = canonicalAmount(value);

        if (canonical === null) {
            setError(
                'Enter an amount in dinars with up to two decimal places.',
            );

            return false;
        }

        setError(null);

        if (canonical !== category.amount) {
            onSave(canonical);
        }

        return true;
    };

    const row = (
        <span className="flex min-w-0 flex-1 items-center gap-3">
            <EntityIcon name={category.icon} color={category.color} />
            <span className="flex min-w-0 flex-1 flex-col">
                <span className="flex min-w-0 items-center gap-2">
                    <span className="truncate font-medium">
                        {category.name}
                    </span>
                    {category.archived ? (
                        <Badge variant="outline">Archived</Badge>
                    ) : null}
                </span>
                {category.description ? (
                    <span className="truncate text-sm text-muted-foreground">
                        {category.description}
                    </span>
                ) : null}
            </span>
            <span
                className="shrink-0 text-right font-medium tabular-nums"
                data-test="budget-display"
            >
                {saving ? (
                    <span className="text-sm font-normal text-muted-foreground">
                        Saving…
                    </span>
                ) : (
                    <MoneyAmount
                        formatted={category.display.formatted}
                        originalFormatted={category.display.original_formatted}
                    />
                )}
            </span>
        </span>
    );

    return (
        <li data-test="budget-category">
            {editable ? (
                <Popover
                    open={open}
                    onOpenChange={(next) => {
                        if (next) {
                            setValue(category.amount);
                            setError(null);
                            setOpen(true);

                            return;
                        }

                        if (commit()) {
                            setOpen(false);
                        }
                    }}
                >
                    <PopoverTrigger asChild>
                        <button
                            type="button"
                            className={cn(
                                'flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-accent focus-visible:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset',
                                open && 'bg-accent',
                            )}
                        >
                            {row}
                        </button>
                    </PopoverTrigger>
                    <PopoverContent
                        align="end"
                        className="flex w-64 flex-col gap-3"
                        onOpenAutoFocus={(event) => {
                            event.preventDefault();
                            inputRef.current?.focus();
                            inputRef.current?.select();
                        }}
                        onEscapeKeyDown={(event) => {
                            event.preventDefault();
                            setValue(category.amount);
                            setError(null);
                            setOpen(false);
                        }}
                    >
                        <PopoverHeader>
                            <PopoverTitle>{category.name}</PopoverTitle>
                            <PopoverDescription>
                                Stored in RSD.
                                {category.updated_by
                                    ? ` Last changed by ${category.updated_by}.`
                                    : ''}
                            </PopoverDescription>
                        </PopoverHeader>
                        <form
                            className="flex flex-col gap-3"
                            onSubmit={(event) => {
                                event.preventDefault();

                                if (commit()) {
                                    setOpen(false);
                                }
                            }}
                        >
                            <div className="flex flex-col gap-2">
                                <Label htmlFor={inputId}>Amount</Label>
                                <InputGroup className="h-11 md:h-9">
                                    <InputGroupInput
                                        ref={inputRef}
                                        id={inputId}
                                        inputMode="decimal"
                                        autoComplete="off"
                                        value={value}
                                        aria-label={`${category.name} budget in RSD`}
                                        aria-invalid={error !== null}
                                        data-test="budget-amount"
                                        onChange={(event) => {
                                            setError(null);
                                            setValue(event.target.value);
                                        }}
                                    />
                                    <InputGroupAddon align="inline-end">
                                        <InputGroupText>RSD</InputGroupText>
                                    </InputGroupAddon>
                                </InputGroup>
                                {error ? (
                                    <p
                                        className="text-sm text-destructive"
                                        role="alert"
                                    >
                                        {error}
                                    </p>
                                ) : null}
                            </div>
                            <Button type="submit" className="h-11 md:h-9">
                                Save
                            </Button>
                        </form>
                    </PopoverContent>
                </Popover>
            ) : (
                <div className="flex items-center gap-3 px-4 py-3">{row}</div>
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
